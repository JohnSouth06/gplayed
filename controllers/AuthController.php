<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once dirname(__DIR__) . '/phpmailer/src/Exception.php';
require_once dirname(__DIR__) . '/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/phpmailer/src/SMTP.php';
require_once dirname(__DIR__) . '/google-api/vendor/autoload.php';
// ------------------------

require_once dirname(__DIR__) . '/models/User.php';

class AuthController
{
    private $userModel;

    public function __construct($db)
    {
        $this->userModel = new User($db);
    }

    private function checkCsrf()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Erreur de sécurité : Token CSRF invalide ou manquant.");
        }
    }

    // --- CONNEXION ---
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $user = $this->userModel->login($_POST['username'], $_POST['password']);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = $user['avatar_url'];

                if (!empty($user['language'])) {
                    $_SESSION['lang'] = $user['language'];
                }

                $_SESSION['toast'] = ['msg' => __('toast_welcome') . $user['username'], 'type' => 'success'];
                $_SESSION['force_loader'] = true;
                header("Location: /");
                exit();
            } else {
                header("Location: /?error=invalid");
                exit();
            }
        }
    }

    // --- INSCRIPTION ---
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $lang = $_POST['language'] ?? 'en';
            $result = $this->userModel->register($_POST['username'], $_POST['email'], $_POST['password'], $lang);

            if ($result === "weak_password") {
                header("Location: /?error=weak_password");
            } elseif ($result === "exists") {
                header("Location: /?error=exists");
            } elseif ($result) {
                $user = $this->userModel->login($_POST['username'], $_POST['password']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = null;
                $_SESSION['lang'] = $lang;
                $_SESSION['toast'] = ['msg' => __('toast_register_success'), 'type' => 'success'];
                $_SESSION['force_loader'] = true;
                header("Location: /");
            } else {
                header("Location: /?error=unknown");
            }
            exit();
        }
    }

    // --- LOGIN GOOGLE ---
    private function getGoogleClient() {
        $client = new Google\Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $redirectUrl = $protocol . $_SERVER['HTTP_HOST'] . '/?action=google_callback';
        
        $client->setRedirectUri($redirectUrl);
        $client->addScope("email");
        $client->addScope("profile");
        
        return $client;
    }

    public function loginGoogle() {
        $client = $this->getGoogleClient();
        header('Location: ' . $client->createAuthUrl());
        exit();
    }

    public function googleCallback() {
        if (!isset($_GET['code'])) {
            header("Location: /");
            exit();
        }

        try {
            $client = $this->getGoogleClient();
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            if (isset($token['error'])) {
                throw new Exception("Erreur Token Google");
            }

            $client->setAccessToken($token['access_token']);
            $google_oauth = new Google\Service\Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $user = $this->userModel->loginOrRegisterGoogle($google_account_info);

            if ($user) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = $user['avatar_url'];
                $_SESSION['lang'] = $user['language'] ?? 'fr';

                $_SESSION['toast'] = ['msg' => __('toast_welcome') . $user['username'], 'type' => 'success'];
                $_SESSION['force_loader'] = true;
                header("Location: /");
            } else {
                header("Location: /?error=google_auth_failed");
            }

        } catch (Exception $e) {

            $_SESSION['toast'] = ['msg' => "Erreur de connexion Google", 'type' => 'danger'];
            header("Location: /");
        }
        exit();
    }

    // --- PROFIL ---
    public function profile()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }
        $user = $this->userModel->getById($_SESSION['user_id']);
        $view = dirname(__DIR__) . '/views/profile.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function updateProfile()
    {
        if (!isset($_SESSION['user_id'])) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $newPass = $_POST['new_password'] ?? '';
            $email = $_POST['email'] ?? '';
            $lang = $_POST['language'] ?? 'en';

            $result = $this->userModel->update($_SESSION['user_id'], $email, $newPass, $_FILES, $lang);
            if ($result === "weak_password") {
                $_SESSION['toast'] = ['msg' => __('toast_weak_password'), 'type' => 'danger'];
            } elseif ($result) {
                $updatedUser = $this->userModel->getById($_SESSION['user_id']);
                $_SESSION['avatar'] = $updatedUser['avatar_url'];
                $_SESSION['lang'] = $lang;
                $_SESSION['toast'] = ['msg' => __('toast_profile_updated'), 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => __('toast_update_error'), 'type' => 'danger'];
            }
        }
        header("Location: /profile");
        exit();
    }

    public function deleteAccount()
    {
        if (!isset($_SESSION['user_id'])) return;
        if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $this->checkCsrf();

            $this->userModel->delete($_SESSION['user_id']);
            session_destroy();
            session_start();
            $_SESSION['toast'] = ['msg' => __('toast_account_deleted'), 'type' => 'warning'];
            header("Location: /");
            exit();
        }
        header("Location: /profile");
    }

    public function logout()
    {
        session_destroy();
        header("Location: /");
        exit();
    }

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $email = $_POST['email'];

            if ($this->userModel->emailExists($email)) {
                $token = bin2hex(random_bytes(32));
                $this->userModel->setResetToken($email, $token);

                // Détection HTTPS
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domainName = $_SERVER['HTTP_HOST'];
                $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

                $link = $protocol . $domainName . $scriptDir . "/?action=reset_password&token=" . $token;

                $mail = new PHPMailer(true);

                try {
                    // --- CONFIGURATION IONOS -->

                    $mail->isSMTP();
                    $mail->Host       = 'smtp.ionos.fr';
                    $mail->SMTPAuth   = true;

                    $mail->Username   = $_ENV['MAIL_USER_ID']; 
                    $mail->Password   = $_ENV['MAIL_PASSWORD_ID'];            
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    // --- EXPEDITEUR & DESTINATAIRE ---
                    $mail->setFrom('info@g-played.com', 'GPlayed Support');
                    $mail->addAddress($email);

                    // --- CONTENU ---
                    $mail->isHTML(true); 
                    $mail->Subject = __('mail_reset_subject');
                    
                    $mail->Body    = "
                        <h3>" . __('mail_body_title') . "</h3>
                        <p>" . __('mail_hello') . "</p>
                        <p>" . __('mail_body_intro') . "</p>
                        <p><a href='$link' style='background-color: #4CE5AE; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>" . __('mail_btn_reset') . "</a></p>
                        <p><small>" . __('mail_backup_link') . " $link</small></p>
                        <p>" . __('mail_validity') . "</p>
                    ";
                    
                    $mail->AltBody = __('mail_alt_body') . " " . $link;

                    $mail->send();
                    $_SESSION['toast'] = ['msg' => __('toast_mail_sent'), 'type' => 'success'];

                } catch (Exception $e) {
                    $_SESSION['toast'] = ['msg' => __('toast_mail_error'), 'type' => 'danger'];
                }

            } else {
                $_SESSION['toast'] = ['msg' => __('toast_mail_check'), 'type' => 'info'];
            }
        }
        header("Location: /");
        exit();
    }

    public function showResetForm()
    {
        $token = $_GET['token'] ?? '';
        
        $langCode = $_SESSION['lang'] ?? 'fr';
        if (!in_array($langCode, ['fr', 'en'])) {
            $langCode = 'fr';
        }

        $t = require dirname(__DIR__) . '/lang/' . $langCode . '.php';
        
        echo '<!DOCTYPE html>
        <html lang="' . htmlspecialchars($langCode) . '" data-bs-theme="dark">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Gplayed • ' . $t['reset_page_title'] . '</title>

            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="assets/css/style.css">
            <style>
                .strength-meter { height: 4px; transition: all 0.3s ease; }
                .strength-weak { background-color: #dc3545; width: 25%; }
                .strength-medium { background-color: #ffc107; width: 50%; }
                .strength-good { background-color: #0d6efd; width: 75%; }
                .strength-strong { background-color: #198754; width: 100%; }
            </style>
        </head>

        <body class="bg-body-tertiary">
            <div class="row justify-content-center align-items-center min-vh-100 w-100 m-0">
                <div class="col-md-8 col-lg-6 col-xl-4">
                    
                    <div class="card shadow-lg border-0 rounded-4 bg-body">
                        <div class="card-body p-5">
                            
                            <div class="text-center mb-4">
                                <div class="d-block pb-2 mx-auto w-75">
                                    <svg id="logo-login" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 42">
                                        <path d="M98.93,17.73c.81,0,.81.44.81,1.33,0,5.49-3.92,9.41-9.54,9.41s-9.67-3.95-9.67-9.41,4.05-9.41,9.67-9.41c4.18,0,7.79,2.46,8.94,5.93.18.55-.13.89-.68.89h-4.03c-.42,0-.71-.18-.94-.52-.73-1.05-1.86-1.7-3.27-1.7-2.48,0-4.26,2.01-4.26,4.78s1.78,4.78,4.26,4.78c1.67,0,3.08-.86,3.63-2.14h-2.85c-.5,0-.78-.29-.78-.78v-2.38c0-.5.29-.78.78-.78h7.92ZM54.36,21.6c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM54.36,12.62c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM58.84,17.11c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM49.88,17.11c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95Z" fill="#4CE5AE"/>
                                        <path class="svg-adaptive-fill" d="M110.33,9.91c3.84,0,6.64,2.72,6.64,6.48s-2.8,6.46-6.64,6.46h-3.53v4.57c0,.5-.29.78-.78.78h-3.76c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h8.08ZM109.72,18.35c1.05,0,1.8-.86,1.8-1.88,0-1.07-.76-1.86-1.8-1.86h-2.93v3.74h2.93ZM130.59,23.5c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-10.98c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h3.76c.5,0,.78.29.78.78v12.81h6.43ZM152.08,27.32c.18.55-.05.89-.63.89h-4.13c-.44,0-.76-.21-.89-.65l-.73-2.4h-6.01l-.73,2.4c-.13.44-.44.65-.89.65h-4.13c-.58,0-.81-.34-.63-.89l5.91-16.78c.16-.44.47-.63.91-.63h5.12c.44,0,.76.18.91.63l5.91,16.78ZM140.73,21.67h3.92l-1.96-6.56-1.96,6.56ZM166.22,9.91c.63,0,.84.42.52.94l-6.35,10.35v6.22c0,.5-.29.78-.78.78h-3.76c-.5,0-.78-.29-.78-.78v-6.04l-6.46-10.53c-.31-.52-.1-.94.52-.94h4.31c.42,0,.71.18.91.55l3.32,6.27,3.32-6.27c.21-.37.5-.55.91-.55h4.31ZM173.67,14.62v2.17h6.69c.5,0,.78.29.78.78v2.93c0,.5-.29.78-.78.78h-6.69v2.22h7.22c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-11.77c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h11.77c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-7.22ZM190.59,9.91c5.46,0,9.41,3.84,9.41,9.15s-3.95,9.15-9.41,9.15h-6.51c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h6.51ZM190.54,23.5c2.33,0,4.03-1.88,4.03-4.47s-1.7-4.42-4.03-4.42h-1.91v8.89h1.91ZM54.36.5H18.8c-10.16,0-18.64,8.09-18.8,18.27-.16,10.36,8.21,18.84,18.52,18.84,4.92,0,9.4-1.93,12.72-5.08.3-.29.48-.68.48-1.1v-3.08h0v-3.14c0-.5-.4-.9-.89-.9h-5.4c-.5,0-.9.39-.9.89v2.64c0,.55-.3,1.06-.78,1.32-1.88.98-4.06,1.45-6.37,1.22-5.45-.54-9.79-5.02-10.18-10.5-.47-6.64,4.79-12.19,11.32-12.19.11,0,.23,0,.34,0h14.28s0,0,0,0h6.61s0,0,0,0c-1.52,1.96-2.66,4.22-3.3,6.68-.39,1.5-.6,3.06-.6,4.68,0,.01,0,.02,0,.03h0v21.51c0,.5.4.9.9.9h5.38c.49,0,.9-.4.9-.9v-6.9c3.29,2.57,7.46,4.05,11.98,3.9,10.04-.34,18.05-8.78,17.89-18.84-.16-10.1-8.4-18.26-18.52-18.26ZM55.6,30.36c-7.22.77-13.28-5.3-12.52-12.54.56-5.27,4.79-9.51,10.05-10.07,7.22-.77,13.28,5.3,12.52,12.54-.56,5.27-4.79,9.51-10.05,10.07ZM17.18,25.49h2.69c.33,0,.6-.27.6-.6v-3.89h3.88c.08,0,.15-.02.22-.04.22-.09.38-.3.38-.56v-2.69c0-.33-.27-.6-.6-.6h-3.88v-3.89c0-.33-.27-.6-.6-.6h-2.69c-.33,0-.6.27-.6.6v3.89h-3.88c-.33,0-.6.27-.6.6v2.69c0,.33.27.6.6.6h3.88v3.89c0,.33.27.6.6.6Z" fill="#fff"/>
                                    </svg>
                                </div>
                                <h4 class="fw-bold mt-3">' . $t['reset_title'] . '</h4>
                            </div>

                            <form action="/do_reset" method="POST" id="resetForm">
                                <input type="hidden" name="csrf_token" value="' . ($_SESSION['csrf_token'] ?? '') . '">
                                <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
                                
                                <div class="form-floating mb-2">
                                    <input type="password" name="new_password" class="form-control rounded-3" id="floatingPass" placeholder="********" required>
                                    <label for="floatingPass">' . $t['reset_label_pass'] . '</label>
                                </div>

                                <div class="mb-3">
                                    <div class="progress" role="progressbar" style="height: 4px;">
                                        <div id="strengthBar" class="progress-bar" style="width: 0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted" id="strengthLabel">' . $t['strength_label'] . '</small>
                                        <small class="fw-bold" id="strengthText"></small>
                                    </div>
                                    <div class="form-text small mt-1"><i class="material-icons-outlined align-middle fs-6 me-1">info</i>' . $t['reset_help_text'] . '</div>
                                </div>

                                <div class="form-floating mb-4">
                                    <input type="password" name="confirm_password" class="form-control rounded-3" id="floatingConfirm" placeholder="********" required>
                                    <label for="floatingConfirm">' . $t['reset_label_confirm'] . '</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">' . $t['reset_btn_validate'] . '</button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <a href="/" class="text-secondary text-decoration-none small">' . $t['reset_btn_cancel'] . '</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const passInput = document.getElementById("floatingPass");
                    const strengthBar = document.getElementById("strengthBar");
                    const strengthText = document.getElementById("strengthText");
                    
                    const labels = {
                        0: "' . $t['strength_very_weak'] . '",
                        1: "' . $t['strength_weak'] . '",
                        2: "' . $t['strength_medium'] . '",
                        3: "' . $t['strength_strong'] . '",
                        4: "' . $t['strength_very_strong'] . '"
                    };

                    passInput.addEventListener("input", function() {
                        const val = passInput.value;
                        let score = 0;
                        
                        // Critères de sécurité
                        if (val.length >= 10) score++;
                        if (/[A-Z]/.test(val)) score++;
                        if (/[a-z]/.test(val)) score++;
                        if (/[0-9]/.test(val)) score++;
                        if (/[^A-Za-z0-9]/.test(val)) score++; // Caractère spécial

                        // Normalisation du score pour l\'affichage (max 4 pour l\'index du tableau)
                        let displayScore = 0;
                        if (score <= 2) displayScore = 1;      // Faible
                        else if (score === 3) displayScore = 2; // Moyen
                        else if (score === 4) displayScore = 3; // Fort
                        else if (score === 5) displayScore = 4; // Très fort
                        if (val.length === 0) displayScore = 0;

                        // Mise à jour UI
                        strengthText.textContent = labels[displayScore];
                        
                        // Classes CSS
                        strengthBar.className = "progress-bar"; // Reset
                        if (displayScore <= 1) {
                            strengthBar.classList.add("bg-danger");
                            strengthBar.style.width = "25%";
                        } else if (displayScore === 2) {
                            strengthBar.classList.add("bg-warning");
                            strengthBar.style.width = "50%";
                        } else if (displayScore === 3) {
                            strengthBar.classList.add("bg-info");
                            strengthBar.style.width = "75%";
                        } else if (displayScore === 4) {
                            strengthBar.classList.add("bg-success");
                            strengthBar.style.width = "100%";
                        }
                    });
                });
            </script>
        </body>
        </html>';
        exit();
    }

    public function doReset()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            // Chargement langue
            $langCode = $_SESSION['lang'] ?? 'fr';
            if (!in_array($langCode, ['fr', 'en'])) $langCode = 'fr';
            $t = require dirname(__DIR__) . '/lang/' . $langCode . '.php';

            $token = $_POST['token'];
            $pass = $_POST['new_password'];
            $confirm = $_POST['confirm_password'] ?? '';

            if ($pass !== $confirm) {
                die($t['reset_err_match'] . " <a href='javascript:history.back()'>" . $t['reset_btn_back'] . "</a>");
            }

            $res = $this->userModel->resetPassword($token, $pass);

            if ($res === "weak_password") {
                die($t['reset_weak'] . " <a href='javascript:history.back()'>" . $t['reset_btn_back'] . "</a>");
            } elseif ($res) {
                $_SESSION['toast'] = ['msg' => __('toast_reset_success'), 'type' => 'success'];
                header("Location: /");
            } else {
                $_SESSION['toast'] = ['msg' => __('toast_reset_invalid'), 'type' => 'danger'];
                header("Location: /");
            }
            exit();
        }
    }
}
<?php
// --- AJOUTS PHPMAILER (MANUEL) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Chargement manuel des classes depuis le dossier 'phpmailer' à la racine
// On suppose que le dossier contient le dossier 'src' standard de Github
require_once dirname(__DIR__) . '/phpmailer/src/Exception.php';
require_once dirname(__DIR__) . '/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/phpmailer/src/SMTP.php';
// ------------------------

require_once dirname(__DIR__) . '/models/User.php';

class AuthController
{
    private $userModel;

    public function __construct($db)
    {
        $this->userModel = new User($db);
    }

    /**
     * Vérifie la validité du token CSRF.
     * À appeler au début de chaque traitement de formulaire POST.
     */
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

                $_SESSION['toast'] = ['msg' => "Ravi de vous revoir, " . $user['username'], 'type' => 'success'];
                $_SESSION['force_loader'] = true;
                header("Location: index.php");
                exit();
            } else {
                header("Location: index.php?error=invalid");
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
                header("Location: index.php?error=weak_password");
            } elseif ($result === "exists") {
                header("Location: index.php?error=exists");
            } elseif ($result) {
                $user = $this->userModel->login($_POST['username'], $_POST['password']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = null;
                $_SESSION['lang'] = $lang;
                $_SESSION['toast'] = ['msg' => "Compte créé avec succès !", 'type' => 'success'];
                $_SESSION['force_loader'] = true;
                header("Location: index.php");
            } else {
                header("Location: index.php?error=unknown");
            }
            exit();
        }
    }

    // --- PROFIL ---
    public function profile()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");
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
                $_SESSION['toast'] = ['msg' => "Mot de passe trop faible.", 'type' => 'danger'];
            } elseif ($result) {
                $updatedUser = $this->userModel->getById($_SESSION['user_id']);
                $_SESSION['avatar'] = $updatedUser['avatar_url'];
                $_SESSION['lang'] = $lang;
                $_SESSION['toast'] = ['msg' => "Profil mis à jour.", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de la mise à jour.", 'type' => 'danger'];
            }
        }
        header("Location: index.php?action=profile");
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
            $_SESSION['toast'] = ['msg' => "Votre compte a été supprimé.", 'type' => 'warning'];
            header("Location: index.php");
            exit();
        }
        header("Location: index.php?action=profile");
    }

    public function logout()
    {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    // --- MOT DE PASSE OUBLIÉ (VERSION PHPMAILER MANUEL / IONOS) ---
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
                $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
                $link = $protocol . $domainName . $scriptDir . "/index.php?action=reset_password&token=" . $token;

                $mail = new PHPMailer(true);

                try {
                    // --- CONFIGURATION IONOS ---
                    $mail->isSMTP();
                    $mail->Host = "smtp.domaine.fr";
                    $mail->Host       = 'smtp.ionos.fr';
                    $mail->SMTPAuth   = true;
                    // REMPLACEZ CI-DESSOUS PAR VOS VRAIS IDENTIFIANTS
                    $mail->Username   = 'info@g-played.com'; 
                    $mail->Password   = '34*$vl$7wl5H#5F*D23@';           
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    // --- EXPEDITEUR & DESTINATAIRE ---
                    // IMPORTANT : L'email 'setFrom' doit être identique au 'Username' ci-dessus pour IONOS
                    $mail->setFrom('info@g-played.com', 'GPlayed Support');
                    $mail->addAddress($email);

                    // --- CONTENU ---
                    $mail->isHTML(true);
                    $mail->Subject = 'Réinitialisation de mot de passe - GPlayed';
                    $mail->Body    = "
                        <h3>Demande de réinitialisation</h3>
                        <p>Bonjour,</p>
                        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous :</p>
                        <p><a href='$link' style='background-color: #4CE5AE; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Réinitialiser</a></p>
                        <p><small>Lien de secours : $link</small></p>
                        <p>Valide 1 heure.</p>
                    ";
                    $mail->AltBody = "Lien de reset : " . $link;

                    $mail->send();
                    $_SESSION['toast'] = ['msg' => "Email envoyé (Vérifiez vos spams).", 'type' => 'success'];

                } catch (Exception $e) {
                    // Log l'erreur exacte dans un fichier sur le serveur si besoin
                    // error_log("Mailer Error: " . $mail->ErrorInfo);
                    $_SESSION['toast'] = ['msg' => "Erreur d'envoi. Contactez l'admin.", 'type' => 'danger'];
                }

            } else {
                $_SESSION['toast'] = ['msg' => "Si ce compte existe, un email a été envoyé.", 'type' => 'info'];
            }
        }
        header("Location: index.php");
        exit();
    }

    public function showResetForm()
    {
        $token = $_GET['token'] ?? '';
        
        // Affichage simple du formulaire (insérez votre HTML complet ici ou incluez une vue)
        echo '<!DOCTYPE html>
        <html lang="fr" data-bs-theme="dark">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nouveau mot de passe</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="d-flex align-items-center justify-content-center vh-100 bg-body-tertiary">
            <div class="card p-4 shadow-lg rounded-4 border-0" style="max-width:400px; width:100%">
                <h4 class="text-center mb-4">Nouveau mot de passe</h4>
                <form action="index.php?action=do_reset" method="POST">
                    <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
                    <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="new_password" class="form-control" placeholder="********" required>
                        <div class="form-text small">Min 10 caractères, Maj, Min, Chiffre, Spécial.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Valider</button>
                </form>
                <div class="text-center mt-3"><a href="index.php">Annuler</a></div>
            </div>
        </body>
        </html>';
        exit();
    }

    public function doReset()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $token = $_POST['token'];
            $pass = $_POST['new_password'];

            $res = $this->userModel->resetPassword($token, $pass);

            if ($res === "weak_password") {
                die("Erreur : Mot de passe trop faible. <a href='javascript:history.back()'>Retour</a>");
            } elseif ($res) {
                $_SESSION['toast'] = ['msg' => "Mot de passe modifié avec succès !", 'type' => 'success'];
                header("Location: index.php");
            } else {
                $_SESSION['toast'] = ['msg' => "Lien invalide ou expiré.", 'type' => 'danger'];
                header("Location: index.php");
            }
            exit();
        }
    }
}
<?php
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
            // Arrêt immédiat si le token est absent ou incorrect
            die("Erreur de sécurité : Token CSRF invalide ou manquant.");
        }
    }

    // --- CONNEXION ---
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf(); // Sécurité CSRF

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
            $this->checkCsrf(); // Sécurité CSRF

            $lang = $_POST['language'] ?? 'en';
            $result = $this->userModel->register($_POST['username'], $_POST['email'], $_POST['password'], $lang);

            if ($result === "weak_password") {
                header("Location: index.php?error=weak_password");
            } elseif ($result === "exists") {
                header("Location: index.php?error=exists");
            } elseif ($result) {
                // Connexion automatique après inscription réussie
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
            $this->checkCsrf(); // Sécurité CSRF

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
            $this->checkCsrf(); // Sécurité CSRF

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

    // --- MOT DE PASSE OUBLIÉ (NOUVEAU) ---

    // 1. Traitement du formulaire "Mot de passe oublié"
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf(); // Sécurité CSRF

            $email = $_POST['email'];

            // On vérifie si l'email existe
            if ($this->userModel->emailExists($email)) {
                $token = bin2hex(random_bytes(32)); // Génère un token unique
                // Enregistre le token en BDD
                $this->userModel->setResetToken($email, $token);

                // Lien de réinitialisation
                $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/index.php?action=reset_password&token=" . $token;

                $subject = "Reinitialisation de mot de passe - GameCol";
                $message = "Bonjour,\n\nCliquez ici pour changer votre mot de passe :\n" . $link;
                $headers = "From: no-reply@gamecol.com";

                // TENTATIVE D'ENVOI
                if (@mail($email, $subject, $message, $headers)) {
                    $_SESSION['toast'] = ['msg' => "Email envoyé (Vérifiez vos spams).", 'type' => 'success'];
                } else {
                    // MODE DEBUG (LOCAL)
                    $_SESSION['toast'] = ['msg' => "DEBUG LOCAL : Token généré. Lien visible dans les logs serveur ou console.", 'type' => 'warning'];
                    // die("Mode Debug Local - Lien de reset : <a href='$link'>$link</a>");
                }
            } else {
                // Message générique par sécurité
                $_SESSION['toast'] = ['msg' => "Si ce compte existe, un email a été envoyé.", 'type' => 'info'];
            }
        }
        header("Location: index.php");
        exit();
    }

    // 2. Affichage du formulaire de nouveau mot de passe (via le lien email)
    public function showResetForm()
    {
        $token = $_GET['token'] ?? '';

        // J'ai ajouté l'input caché csrf_token ici aussi pour que doReset fonctionne
        echo '<!DOCTYPE html>
        <html lang="fr" data-bs-theme="dark">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Réinitialisation</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>body{font-family: sans-serif;}</style>
        </head>
        <body class="d-flex align-items-center justify-content-center vh-100 bg-body-tertiary">
            <div class="card p-4 shadow-lg rounded-4 border-0" style="max-width:400px; width:100%">
                <div class="text-center mb-4">
                    <h4 class="fw-bold">Nouveau mot de passe</h4>
                    <p class="text-secondary small">Choisissez un mot de passe sécurisé.</p>
                </div>
                <form action="index.php?action=do_reset" method="POST">
                    <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
                    <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Mot de passe</label>
                        <input type="password" name="new_password" class="form-control rounded-3" placeholder="********" required>
                        <div class="form-text small">Min 10 caractères, Maj, Min, Chiffre, Spécial.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Valider</button>
                </form>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none small text-secondary">Annuler</a>
                </div>
            </div>
        </body>
        </html>';
        exit();
    }

    // 3. Traitement du changement de mot de passe
    public function doReset()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf(); // Sécurité CSRF

            $token = $_POST['token'];
            $pass = $_POST['new_password'];

            // Appel au modèle pour vérifier le token et changer le MDP
            $res = $this->userModel->resetPassword($token, $pass);

            if ($res === "weak_password") {
                die("Erreur : Le mot de passe est trop faible. <a href='javascript:history.back()'>Retour</a>");
            } elseif ($res) {
                $_SESSION['toast'] = ['msg' => "Mot de passe modifié avec succès ! Connectez-vous.", 'type' => 'success'];
                header("Location: index.php");
            } else {
                $_SESSION['toast'] = ['msg' => "Lien invalide ou expiré.", 'type' => 'danger'];
                header("Location: index.php");
            }
            exit();
        }
    }
}

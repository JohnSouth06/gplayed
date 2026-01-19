<?php
require_once dirname(__DIR__) . '/models/User.php';

class AuthController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new User($db);
    }
    
    // ... reste des méthodes login, register, profile, etc. ...
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->userModel->login($_POST['username'], $_POST['password']);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = $user['avatar_url'];
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

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->userModel->register($_POST['username'], $_POST['email'], $_POST['password']);
            
            if ($result === "weak_password") {
                header("Location: index.php?error=weak_password");
            } elseif ($result === "exists") {
                header("Location: index.php?error=exists");
            } elseif ($result) {
                $user = $this->userModel->login($_POST['username'], $_POST['password']); 
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = null;
                $_SESSION['toast'] = ['msg' => "Compte créé avec succès !", 'type' => 'success'];
                $_SESSION['force_loader'] = true;
                header("Location: index.php");
            } else {
                header("Location: index.php?error=unknown");
            }
            exit();
        }
    }

    public function profile() {
        if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
        $user = $this->userModel->getById($_SESSION['user_id']);
        $view = dirname(__DIR__) . '/views/profile.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPass = $_POST['new_password'] ?? '';
            $email = $_POST['email'] ?? '';
            $result = $this->userModel->update($_SESSION['user_id'], $email, $newPass, $_FILES);
            if ($result === "weak_password") {
                $_SESSION['toast'] = ['msg' => "Mot de passe trop faible.", 'type' => 'danger'];
            } elseif ($result) {
                $updatedUser = $this->userModel->getById($_SESSION['user_id']);
                $_SESSION['avatar'] = $updatedUser['avatar_url'];
                $_SESSION['toast'] = ['msg' => "Profil mis à jour.", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de la mise à jour.", 'type' => 'danger'];
            }
        }
        header("Location: index.php?action=profile");
        exit();
    }

    public function deleteAccount() {
        if (!isset($_SESSION['user_id'])) return;
        if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $this->userModel->delete($_SESSION['user_id']);
            session_destroy();
            session_start();
            $_SESSION['toast'] = ['msg' => "Votre compte a été supprimé.", 'type' => 'warning'];
            header("Location: index.php");
            exit();
        }
        header("Location: index.php?action=profile");
    }

    public function logout() {
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
?>
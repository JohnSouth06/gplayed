<?php
require_once dirname(__DIR__) . '/models/User.php';

class CommunityController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new User($db);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

        $users = $this->userModel->getAllUsersExcept($_SESSION['user_id']);
        $following = $this->userModel->getFollowedIds($_SESSION['user_id']);

        $view = dirname(__DIR__) . '/views/community.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function toggleFollow() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit(); }
        
        // --- Sécurité CSRF pour lien GET ---
        // On vérifie que le token est présent dans l'URL
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Action non autorisée (CSRF). Lien invalide.");
        }

        $targetId = $_GET['id'];
        $myId = $_SESSION['user_id'];
        $action = $_GET['do'] ?? 'follow'; 

        if ($action === 'follow') {
            $this->userModel->follow($myId, $targetId);
            $_SESSION['toast'] = ['msg' => "Abonnement ajouté !", 'type' => 'success'];
        } else {
            $this->userModel->unfollow($myId, $targetId);
            $_SESSION['toast'] = ['msg' => "Désabonnement effectué.", 'type' => 'warning'];
        }

        header("Location: index.php?action=community");
        exit();
    }
}
?>
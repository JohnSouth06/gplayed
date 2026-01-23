<?php
require_once dirname(__DIR__) . '/models/User.php';

class CommunityController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new User($db);
    }

    // Page "Communauté" : Liste des membres
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

        $users = $this->userModel->getAllUsersExcept($_SESSION['user_id']);
        $following = $this->userModel->getFollowedIds($_SESSION['user_id']); // IDs des gens qu'on suit déjà

        $view = dirname(__DIR__) . '/views/community.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // Action : Suivre / Ne plus suivre
    public function toggleFollow() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit(); }
        
        $targetId = $_GET['id'];
        $myId = $_SESSION['user_id'];
        $action = $_GET['do'] ?? 'follow'; // 'follow' ou 'unfollow'

        if ($action === 'follow') {
            $this->userModel->follow($myId, $targetId);
            $_SESSION['toast'] = ['msg' => "Abonnement ajouté !", 'type' => 'success'];
        } else {
            $this->userModel->unfollow($myId, $targetId);
            $_SESSION['toast'] = ['msg' => "Désabonnement effectué.", 'type' => 'warning'];
        }

        // Redirection vers la page précédente (ou community par défaut)
        header("Location: index.php?action=community");
        exit();
    }
}
?>
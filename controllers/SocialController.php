<?php
require_once dirname(__DIR__) . '/models/Activity.php';
require_once dirname(__DIR__) . '/models/Comment.php';

class SocialController {
    private $activityModel;
    private $commentModel;

    public function __construct($db) {
        $this->activityModel = new Activity($db);
        $this->commentModel = new Comment($db);
    }

    // --- Sécurité CSRF ---
    private function checkCsrf() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Erreur de sécurité : Token CSRF invalide.");
        }
    }

    public function feed() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        
        $activities = $this->activityModel->getFeed($_SESSION['user_id']);
        
        $view = dirname(__DIR__) . '/views/feed.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function addComment() {
        if (!isset($_SESSION['user_id'])) return;
        
        if (isset($_POST['game_id']) && !empty($_POST['content'])) {
            $this->checkCsrf(); // <--- VERIFICATION

            $this->commentModel->add($_SESSION['user_id'], $_POST['game_id'], $_POST['content']);
            $_SESSION['toast'] = ['msg' => "Commentaire publié !", 'type' => 'success'];
            
            if(isset($_SERVER['HTTP_REFERER'])) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                header("Location: /feed");
            }
        }
        exit();
    }
}
?>
<?php
require_once dirname(__DIR__) . '/models/Activity.php';

class SocialController {
    private $activityModel;

    public function __construct($db) {
        $this->activityModel = new Activity($db);
    }

    public function feed() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        
        $activities = $this->activityModel->getFeed($_SESSION['user_id']);
        
        // Définition des réactions disponibles (Emoji + Label)
        $reactionTypes = [
            'like' => ['icon' => '👍', 'label' => 'J\'aime'],
            'clap' => ['icon' => '👏', 'label' => 'Bravo'],
            'fire' => ['icon' => '🔥', 'label' => 'Intéressant'],
            'laugh' => ['icon' => '😂', 'label' => 'Drôle'],
            'heart' => ['icon' => '❤️', 'label' => 'Adore']
        ];

        $view = dirname(__DIR__) . '/views/feed.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // Nouvelle méthode appelée via AJAX (JavaScript)
    public function react() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['type'], $input['ref_id'], $input['reaction'])) {
            $result = $this->activityModel->toggleReaction(
                $_SESSION['user_id'], 
                $input['type'], 
                $input['ref_id'], 
                $input['reaction']
            );
            echo json_encode(['status' => 'success', 'action' => $result]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Données incomplètes']);
        }
        exit();
    }
}
?>
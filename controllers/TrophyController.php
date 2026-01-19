<?php
require_once dirname(__DIR__) . '/models/Trophy.php';

class TrophyController {
    private $trophyModel;

    public function __construct($db) {
        $this->trophyModel = new Trophy($db);
    }

    public function apiGet() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['game_id'])) exit(json_encode([]));
        
        $trophies = $this->trophyModel->getAllByGame($_GET['game_id']);
        $progress = $this->trophyModel->getProgress($_GET['game_id']);
        
        $percent = ($progress['total'] > 0) ? round(($progress['obtained'] / $progress['total']) * 100) : 0;

        echo json_encode([
            'trophies' => $trophies,
            'progress' => [
                'total' => $progress['total'],
                'obtained' => $progress['obtained'] ?? 0,
                'percent' => $percent
            ]
        ]);
        exit();
    }

    public function apiAdd() {
        if (!isset($_SESSION['user_id'])) exit();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->trophyModel->add($_POST);
        }
        exit(json_encode(['status' => 'success']));
    }

    public function apiToggle() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) exit();
        $this->trophyModel->toggle($_GET['id']);
        exit(json_encode(['status' => 'success']));
    }

    public function apiDelete() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) exit();
        $this->trophyModel->delete($_GET['id']);
        exit(json_encode(['status' => 'success']));
    }
}
?>
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
        // 1. On force l'en-tête JSON pour que le navigateur comprenne bien la réponse
        header('Content-Type: application/json');

        // 2. Vérification de la session
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
            exit();
        }

        // 3. Vérification de la méthode
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 4. Validation : On vérifie que les champs obligatoires sont là
            if (empty($_POST['game_id']) || empty($_POST['title']) || empty($_POST['type'])) {
                echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
                exit();
            }

            // 5. Tentative d'ajout
            if ($this->trophyModel->add($_POST)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'enregistrement SQL']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Méthode invalide']);
        }
        exit();
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
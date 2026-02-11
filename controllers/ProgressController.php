<?php
require_once dirname(__DIR__) . '/models/Progress.php';
require_once dirname(__DIR__) . '/models/Game.php';

class ProgressController {
    private $progressModel;
    private $gameModel;

    public function __construct($db) {
        $this->progressModel = new Progress($db);
        $this->gameModel = new Game($db);
    }

    // --- Sécurité CSRF ---
    private function checkCsrf() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Erreur de sécurité : Token CSRF invalide.");
        }
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }

        $history = $this->progressModel->getAllByUser($_SESSION['user_id']);
        $games = $this->gameModel->getSelectableGames($_SESSION['user_id']);

        $totalHours = 0;
        foreach($history as $h) $totalHours += $h['duration_minutes'];
        $totalHours = round($totalHours / 60, 1);

        $view = dirname(__DIR__) . '/views/progression.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function add() {
        if (!isset($_SESSION['user_id'])) return;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf(); // <--- VERIFICATION

            if ($this->progressModel->add($_POST)) {
                $_SESSION['toast'] = ['msg' => "Progression enregistrée !", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de l'ajout.", 'type' => 'danger'];
            }
        }
        header("Location: /progression");
        exit();
    }

    public function delete() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;
        
        // La suppression par URL (GET) est moins critique ici mais idéalement à protéger aussi
        if ($this->progressModel->delete($_GET['id'])) {
            $_SESSION['toast'] = ['msg' => "Entrée supprimée.", 'type' => 'warning'];
        }
        header("Location: /progression");
        exit();
    }
}
?>
<?php
require_once dirname(__DIR__) . '/models/Progress.php';
require_once dirname(__DIR__) . '/models/Game.php'; // On a besoin de la liste des jeux pour le select

class ProgressController {
    private $progressModel;
    private $gameModel;

    public function __construct($db) {
        $this->progressModel = new Progress($db);
        $this->gameModel = new Game($db);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }

        // Récupérer l'historique complet
        $history = $this->progressModel->getAllByUser($_SESSION['user_id']);
        
        // Récupérer la liste des jeux pour le formulaire d'ajout (uniquement ceux en cours par exemple, ou tous)
        $games = $this->gameModel->getAll($_SESSION['user_id']);

        // Calculs rapides pour les KPIs de la page
        $totalHours = 0;
        foreach($history as $h) $totalHours += $h['duration_minutes'];
        $totalHours = round($totalHours / 60, 1);

        $view = dirname(__DIR__) . '/views/progression.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function add() {
        if (!isset($_SESSION['user_id'])) return;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->progressModel->add($_POST)) {
                $_SESSION['toast'] = ['msg' => "Progression enregistrée !", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de l'ajout.", 'type' => 'danger'];
            }
        }
        header("Location: index.php?action=progression");
        exit();
    }

    public function delete() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;
        
        if ($this->progressModel->delete($_GET['id'])) {
            $_SESSION['toast'] = ['msg' => "Entrée supprimée.", 'type' => 'warning'];
        }
        header("Location: index.php?action=progression");
        exit();
    }
}
?>
<?php
require_once dirname(__DIR__) . '/models/Progress.php';
require_once dirname(__DIR__) . '/models/Game.php';
require_once dirname(__DIR__) . '/models/Playtime.php';

class ProgressController
{
    private $progressModel;
    private $gameModel;
    // NOUVEAU : Variable pour stocker la connexion BDD
    private $db;

    public function __construct($db)
    {
        // NOUVEAU : On assigne $db pour pouvoir l'utiliser dans l'API
        $this->db = $db;
        $this->progressModel = new Progress($db);
        $this->gameModel = new Game($db);
    }

    // --- Sécurité CSRF ---
    private function checkCsrf()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Erreur de sécurité : Token CSRF invalide.");
        }
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }

        $history = $this->progressModel->getAllByUser($_SESSION['user_id']);
        $games = $this->gameModel->getSelectableGames($_SESSION['user_id']);


        $trophyModel = new Trophy($this->db);
        $trophies = $trophyModel->getRecentTrophiesByUser($_SESSION['user_id']);

        $totalHours = 0;
        $timeline = [];

        // 1. Formatage des sessions
        foreach ($history as $h) {
            $totalHours += $h['duration_minutes'];
            $timeline[] = [
                'type'           => 'session',
                'date_display'   => $h['log_date'],   // Date pour l'affichage (YYYY-MM-DD)
                'sort_date'      => $h['created_at'], // Timestamp précis pour le tri
                'game_title'     => $h['game_title'],
                'game_image'     => $h['game_image'],
                'duration'       => $h['duration_minutes'],
                'progress_value' => $h['progress_value'],
                'notes'          => $h['notes'],
                'id'             => $h['id']
            ];
        }
        $totalHours = round($totalHours / 60, 1);

        // 2. Formatage des trophées
        foreach ($trophies as $t) {
            $timeline[] = [
                'type'         => 'trophy',
                'date_display' => $t['earned_date'] ?? $t['created_at'] ?? 'Date inconnue',
                'sort_date'    => $t['earned_date'] ?? $t['created_at'] ?? '0000-00-00 00:00:00',
                'game_title'   => $t['game_title'],
                'game_image'   => $t['game_image'],
                'trophy_name'  => $t['trophy_name'],
                'trophy_type'  => $t['trophy_type']
            ];
        }

        // 3. Tri global de la timeline par date décroissante
        usort($timeline, function ($a, $b) {
            $dateA = strtotime($a['sort_date'] ?? '');
            $dateB = strtotime($b['sort_date'] ?? '');

            return $dateB - $dateA;
        });

        $view = dirname(__DIR__) . '/views/progression.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    public function add()
    {
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

    public function delete()
    {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;

        // La suppression par URL (GET) est moins critique ici mais idéalement à protéger aussi
        if ($this->progressModel->delete($_GET['id'])) {
            $_SESSION['toast'] = ['msg' => "Entrée supprimée.", 'type' => 'warning'];
        }
        header("Location: /progression");
        exit();
    }


    // ==========================================
    //            ROUTES API (MOBILE)
    // ==========================================

    private function apiResponse($success, $message, $data = [], $httpCode = 200)
    {
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit();
    }

    public function apiGetProgress($userId)
    {
        $history = $this->progressModel->getAllByUser($userId);
        $games = $this->gameModel->getSelectableGames($userId);
        $this->apiResponse(true, 'Données récupérées', ['history' => $history, 'games' => $games]);
    }

    public function apiAddProgress($userId)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['game_id'])) {
            $this->apiResponse(false, 'Données manquantes', [], 400);
        }

        // On injecte progress_value pour que le modèle Progress.php ne génère pas d'erreur SQL
        $input['progress_value'] = $input['progress_value'] ?? null;

        if ($this->progressModel->add($input)) {
            // Mise à jour du temps global du jeu dans la table Playtime
            $addedMinutes = ((int)$input['duration_hours'] * 60) + (int)$input['duration_minutes'];
            $addedHoursDecimal = round($addedMinutes / 60, 2);

            $playtimeModel = new Playtime($this->db);
            $existingPlaytime = $playtimeModel->getByGameId($input['game_id']);
            $currentMain = $existingPlaytime ? (float)$existingPlaytime['time_main'] : 0;
            $current100 = $existingPlaytime ? $existingPlaytime['time_100'] : null;

            $newTotal = $currentMain + $addedHoursDecimal;
            $playtimeModel->save($input['game_id'], $newTotal, $current100);

            $this->apiResponse(true, 'Session enregistrée et temps mis à jour !');
        } else {
            $this->apiResponse(false, 'Erreur lors de la sauvegarde de la session.');
        }
    }

    public function apiDeleteProgress($userId)
    {
        $progressId = $_GET['id'] ?? null;
        if ($progressId && $this->progressModel->delete($progressId)) {
            $this->apiResponse(true, 'Session supprimée');
        } else {
            $this->apiResponse(false, 'Erreur lors de la suppression');
        }
    }
}

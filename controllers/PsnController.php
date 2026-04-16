<?php
require_once dirname(__DIR__) . '/models/Psn.php';
require_once dirname(__DIR__) . '/models/User.php';

class PsnController
{
    private $psnModel;
    private $userModel;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->psnModel = new Psn($db);
        $this->userModel = new User($db);
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit();
        }

        $userId = $_SESSION['user_id'];
        $psnGames = $this->psnModel->getUserPsnGamesWithStats($userId);
        $userStats = $this->userModel->getById($userId);

        $view = 'views/psn_trophies.php';
        require ROOT_PATH . '/views/layout.php';
    }

    public function sync()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $psnId = trim($data['psn_id'] ?? '');

        if (empty($psnId)) {
            echo json_encode(['success' => false, 'message' => 'ID PSN manquant.']);
            exit();
        }

        $this->userModel->setPsnId($userId, $psnId);

        $user = $this->userModel->getById($userId);
        if ($user['last_psn_sync']) {
            $lastSync = new DateTime($user['last_psn_sync']);
            $now = new DateTime();
            if (($now->getTimestamp() - $lastSync->getTimestamp()) < 3600) {
                $remaining = 60 - floor(($now->getTimestamp() - $lastSync->getTimestamp()) / 60);
                echo json_encode(['success' => false, 'message' => "Veuillez attendre $remaining minutes."]);
                exit();
            }
        }

        $result = $this->executeSync($userId, $psnId);



        if ($result) {
            $this->userModel->updateLastPsnSync($userId);
            echo json_encode([
                'success' => true,
                'games_count' => $result['games'],
                'trophies_count' => $result['trophies']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Échec de la synchronisation avec le service PSN.']);
        }
        exit();
    }

    public function apiGetTrophies()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id']) || !isset($_GET['psn_game_id'])) {
            echo json_encode(['success' => false]);
            exit();
        }

        $trophies = $this->psnModel->getTrophiesByGame($_GET['psn_game_id']);

        echo json_encode([
            'success' => true,
            'trophies' => $trophies
        ]);
        exit();
    }

    private function executeSync($userId, $psnId)
    {
        $vpsUrl = "http://87.106.8.127:3000/api/psn/trophies/" . urlencode($psnId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $vpsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 240);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return false;

        $data = json_decode($response, true);
        if (!$data || !$data['success']) return false;

        if (isset($data['trophySummary'])) {
            $this->userModel->updatePsnTrophyStats($userId, $data['trophySummary']);
        }

        $stats = ['games' => 0, 'trophies' => 0];

        foreach ($data['games'] as $game) {
            // Insertion/Mise à jour dans la table isolée psn_games
            $psnGameId = $this->psnModel->upsertGame(
                $userId,
                $game['npCommunicationId'],
                $game['titleName'],
                $game['titleIconUrl'] ?? '',
                $game['lastPlayedAt'] ?? null
            );
            $stats['games']++;

            if (isset($game['earnedTrophies']) && is_array($game['earnedTrophies'])) {
                foreach ($game['earnedTrophies'] as $trophy) {
                    $earnedAt = null;
                    if (!empty($trophy['earnedDateTime'])) {
                        $earnedAt = date('Y-m-d H:i:s', strtotime($trophy['earnedDateTime']));
                    }

                    // Insertion/Mise à jour dans la table isolée psn_trophies
                    $this->psnModel->upsertTrophy(
                        $psnGameId,
                        $trophy['trophyId'],
                        $trophy['trophyName'],
                        $trophy['trophyNameFr'] ?? null,
                        strtolower($trophy['trophyType'] ?? 'bronze'),
                        !empty($trophy['earned']),
                        $earnedAt
                    );
                    $stats['trophies']++;
                }
            }
        }
        return $stats;
    }
}

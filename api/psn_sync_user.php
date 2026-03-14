<?php
// api/psn_sync_user.php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/models/Psn.php';
// ... inclure ici votre classe cliente API PSN (Sony API) ...

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
    exit();
}

$db = (new Database())->getConnection();
$psnModel = new Psn($db);

$userId = $_SESSION['user_id'];
$psnId = $_POST['psn_id'] ?? null; 

// Logique d'appel à l'API Sony (simulée ici)
// $psnData = SonyApi::getUserGamesAndTrophies($psnId);
$psnData = []; // Remplacer par vos données brutes récupérées

foreach ($psnData['games'] as $game) {
    // 1. On sauvegarde chaque jeu dans psn_games
    $psnGameId = $psnModel->upsertGame($userId, $game['np_id'], $game['name'], $game['image'], $game['last_played']);
    
    // 2. On sauvegarde ses trophées dans psn_trophies
    foreach ($game['trophies'] as $trophy) {
        $psnModel->upsertTrophy($psnGameId, $trophy['id'], $trophy['name'], $trophy['type'], $trophy['earned'], $trophy['earned_date']);
    }
}

echo json_encode(['status' => 'success', 'message' => 'Profil PSN synchronisé avec succès.']);
exit();
?>
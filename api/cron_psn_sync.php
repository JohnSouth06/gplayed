<?php
// api/cron_psn_sync.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // Autorise le script à tourner 5 minutes

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . "=" . trim($value, "\"' "));
    }
}

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/models/Psn.php';
require_once dirname(__DIR__) . '/models/User.php';

$db = (new Database())->getConnection();
$psnModel = new Psn($db);
$userModel = new User($db);

$stmt = $db->query("SELECT id, username, psn_id FROM users WHERE psn_id IS NOT NULL AND psn_id != ''");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- Début synchro isolée " . date('Y-m-d H:i:s') . " ---\n";

foreach ($users as $user) {
    echo "Sync {$user['username']}... ";
    
    $url = "http://87.106.8.127:3000/api/psn/trophies/" . urlencode($user['psn_id']);
    
    // REMPLACEMENT DE file_get_contents PAR CURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 240); // Timeout de 4 minutes
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 200 && $data && $data['success']) {
        
        // POINT DE CONTRÔLE 1 : Vérification des données reçues du VPS
        if (isset($data['trophySummary'])) {
            echo "   [INFO] Données reçues : P(" . $data['trophySummary']['platinum'] . 
                 ") G(" . $data['trophySummary']['gold'] . 
                 ") S(" . $data['trophySummary']['silver'] . 
                 ") B(" . $data['trophySummary']['bronze'] . ")\n";
            
            // POINT DE CONTRÔLE 2 : Vérification de l'écriture en BDD
            $dbStatus = $userModel->updatePsnTrophyStats($user['id'], $data['trophySummary']);
            if ($dbStatus) {
                echo "   [SUCCESS] Statistiques mises à jour en BDD.\n";
            } else {
                echo "   [ERROR] Échec de l'écriture en BDD (Vérifiez vos colonnes SQL).\n";
            }
        } else {
            echo "   [WARNING] Aucune donnée 'trophySummary' dans la réponse du VPS.\n";
        }

        foreach ($data['games'] as $game) {
            $psnGameId = $psnModel->upsertGame(
                $user['id'], 
                $game['npCommunicationId'], 
                $game['titleName'], 
                $game['titleIconUrl'],
                $game['lastPlayedAt'] ?? null
            );

            foreach ($game['earnedTrophies'] as $t) {
                $psnModel->upsertTrophy(
                    $psnGameId,
                    $t['trophyId'],
                    $t['trophyName'],
                    $t['trophyNameFr'] ?? null,
                    $t['trophyType'],
                    $t['earned'],
                    $t['earnedDateTime'] ? date('Y-m-d H:i:s', strtotime($t['earnedDateTime'])) : null
                );
            }
        }
       echo "   [DONE] Jeux synchronisés.\n";
    } else {
        echo "   [FATAL] Erreur VPS (Code: $httpCode)\n";
    }
    echo "\n";
}
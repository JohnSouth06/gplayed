<?php
// api/cron_psn_sync.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. LECTURE DU FICHIER .env
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die("Erreur : Le fichier .env est introuvable.\n");
}

$envVariables = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $key = trim($parts[0]);
        $value = trim(trim($parts[1]), "\"'");
        $envVariables[$key] = $value;
        // CRUCIAL : On injecte la variable pour que getenv() puisse la lire dans Database.php
        putenv("$key=$value"); 
    }
}

// 2. SÉCURITÉ WEBCRON
$secretToken = $envVariables['PSN_SYNC_CRON_TOKEN'] ?? 'MotDePasseDeSecours123';
if (!isset($_GET['token']) || $_GET['token'] !== $secretToken) {
    http_response_code(403);
    die("Accès refusé. Token invalide ou manquant.");
}

ob_start();
echo "--- Début de la synchro PSN globale du " . date('Y-m-d H:i:s') . " ---\n";

// 3. CHARGEMENT DES DÉPENDANCES
require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/models/Game.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/controllers/GameController.php';

// 4. CONNEXION BDD
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Erreur critique : La connexion à la base de données a échoué.\n");
}

$gameController = new GameController($db);

// 5. RÉCUPÉRATION DES UTILISATEURS
$stmt = $db->query("SELECT id, username, psn_id FROM users WHERE psn_id IS NOT NULL AND psn_id != ''");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "Synchronisation de {$user['username']} ({$user['psn_id']})... ";
    
    // Appel de la méthode que nous avons créée dans le GameController
    $result = $gameController->executePsnSync($user['id'], $user['psn_id']);
    
    if ($result) {
        echo "SUCCÈS ({$result['games']} jeux, {$result['trophies']} trophées)\n";
    } else {
        echo "ÉCHEC (Vérifiez le VPS ou le token NPSSO)\n";
    }
    
    // Pause de 5 secondes pour respecter le Rate Limit de Sony
    sleep(5);
}

echo "--- Fin de la synchronisation ---\n\n";

// 6. LOGS
$logOutput = ob_get_clean();
file_put_contents(__DIR__ . '/cron_psn.log', $logOutput, FILE_APPEND);
echo nl2br($logOutput);
?>
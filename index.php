<?php
// ACTIVATION DU DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
define('ROOT_PATH', __DIR__);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/GameController.php';
require_once ROOT_PATH . '/controllers/ProgressController.php';
require_once ROOT_PATH . '/controllers/CommunityController.php';
require_once ROOT_PATH . '/controllers/TrophyController.php';
require_once ROOT_PATH . '/config/lang.php';

$database = new Database();
$db = $database->getConnection();

$authController = new AuthController($db);
$gameController = new GameController($db);
$progressController = new ProgressController($db);
$communityController = new CommunityController($db);
$trophyController = new TrophyController($db);

$action = $_GET['action'] ?? 'home';

switch ($action) {

    // Auth & Profile
    case 'login':
        $authController->login();
        break;
    case 'register':
        $authController->register();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'profile':
        $authController->profile();
        break;
    case 'update_profile':
        $authController->updateProfile();
        break;
    case 'delete_account':
        $authController->deleteAccount();
        break;

    // Password/Reset
    case 'forgot_password':
        $authController->forgotPassword();
        break;
    case 'reset_password':
        $authController->showResetForm();
        break;
    case 'do_reset':
        $authController->doReset();
        break;

    // Google Login
    case 'login_google':
        $authController->loginGoogle();
        break;
    case 'google_callback':
        $authController->googleCallback();
        break;

    // Discord Login    
    case 'login_discord':
        $authController->loginDiscord();
        break;
    case 'discord_callback':
        $authController->discordCallback();
        break;

    // Games
    case 'save':
        $gameController->save();
        break;
    case 'delete':
        $gameController->delete();
        break;
    case 'stats':
        $gameController->stats();
        break;

    // Wishlist
    case 'wishlist': $gameController->wishlist(); break;
    case 'acquire': $gameController->acquire(); break;

    // --- Prêts (Loans) ---
    case 'loaned':
        $gameController->loaned();
        break;
    case 'loan':
        $gameController->loan();
        break;
    case 'returnGame':
        $gameController->returnGame();
        break;
        
    // Recherche API
    case 'api_search':
        $gameController->apiSearch();
        break;

    // Recherche API Externe (IGDB)
    case 'search_igdb':
        $gameController->searchIgdb();
        break;
    case 'get_igdb_details':
        $gameController->getIgdbDetails();
        break;

    // --- SYNCHRONISATION PSN ---
    case 'api_psn_sync':
        $userId = $currentUser['id'];
        
        // Optionnel : Récupérer le vrai PSN ID de l'utilisateur (si vous avez créé la colonne)
        // Pour l'instant, on utilise "me" car c'est votre propre token sur le VPS
        $psnId = "me"; 

        // 1. Appel de votre micro-service VPS
        $vpsIp = "http://87.106.8.127:3000"; 
        $url = $vpsIp . "/api/psn/trophies/" . urlencode($psnId);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            sendJson(false, 'Erreur de communication avec le serveur PSN relai.');
        }

        $data = json_decode($response, true);
        if (!$data || !$data['success']) {
            sendJson(false, 'Erreur API PSN: ' . ($data['message'] ?? 'Inconnue'));
        }

        // 2. Traitement des données reçues
        require_once ROOT_PATH . '/models/Trophy.php';
        require_once ROOT_PATH . '/models/Game.php';
        
        $trophyModel = new Trophy($db);
        $gameModel = new Game($db);

        $syncedGames = 0;
        $syncedTrophies = 0;

        foreach ($data['games'] as $psnGame) {
            // On cherche le jeu dans la bibliothèque de l'utilisateur
            $localGame = $gameModel->findPlayStationGameByTitle($userId, $psnGame['titleName']);
            
            if ($localGame) {
                $gameId = $localGame['id'];
                $syncedGames++;
                
                // On boucle sur les trophées du jeu
                if (isset($psnGame['earnedTrophies']) && is_array($psnGame['earnedTrophies'])) {
                    foreach ($psnGame['earnedTrophies'] as $trophy) {
                        // Les trophées cachés non obtenus n'ont parfois pas de nom exposé par l'API
                        $title = $trophy['trophyName'] ?? 'Trophée masqué';
                        $type = strtolower($trophy['trophyType'] ?? 'bronze'); 
                        $isObtained = !empty($trophy['earned']);

                        $trophyModel->syncPsnTrophy($gameId, $title, $type, $isObtained);
                        $syncedTrophies++;
                    }
                }
            }
        }

        sendJson(true, 'Synchronisation terminée avec succès', [
            'games_synced' => $syncedGames,
            'trophies_processed' => $syncedTrophies
        ]);
        break;

    // Import Steam
    case 'steam_login':
    $gameController->steamLogin();
    break;

    case 'steam_callback':
    $gameController->steamCallback();
    break;

    case 'api_steam_games':
        $gameController->apiGetSteamGames();
        break;
    case 'api_steam_import_single':
        $gameController->apiImportSingleSteamGame();
        break;
    case 'api_steam_complete':
        $gameController->steamImportComplete();
        break;

    case 'update_steam_playtime':
        $gameController->updateSteamPlaytime();
        break;

    // Import/Export
    case 'export_json':
        $gameController->export();
        break;
    case 'import_json':
        $gameController->import();
        break;

    // Progression
    case 'progression':
        $progressController->index();
        break;
    case 'add_progress':
        $progressController->add();
        break;
    case 'delete_progress':
        $progressController->delete();
        break;

    // --- Community
    case 'community':
        $communityController->index();
        break;
    case 'toggle_follow':
        $communityController->toggleFollow();
        break;

    // Profil Public
    case 'share':
        $gameController->share();
        break;

    // Legal
    case 'legal':
        $view = 'views/legal.php';
        require ROOT_PATH . '/views/layout.php';
        break;

    // Wheel
    case 'api_roulette_games':
        $gameController->apiRouletteGames();
        break;
    case 'api_start_game':
        $gameController->apiStartGame();
        break;

    // Trophy
    case 'api_add_trophy':
        require_once 'controllers/TrophyController.php';
        $controller = new TrophyController($db);
        $controller->apiAdd();
        break;

    case 'api_get_trophies':
        $trophyController->apiGet();
        break;
    case 'api_add_trophy':
        $trophyController->apiAdd();
        break;
    case 'api_toggle_trophy':
        $trophyController->apiToggle();
        break;
    case 'api_delete_trophy':
        $trophyController->apiDelete();
        break;

    case 'js_lang':
        header('Content-Type: application/javascript');

        $jsTranslations = [];
        if (isset($GLOBALS['translations'])) {
            foreach ($GLOBALS['translations'] as $key => $value) {
                // 1. On transfert TOUTES les clés telles quelles (pour status_playing, etc.)
                $jsTranslations[$key] = $value;

                // 2. Rétrocompatibilité : on crée un double sans le "js_" pour vos autres fichiers JS
                if (strpos($key, 'js_') === 0) {
                    $cleanKey = substr($key, 3);
                    $jsTranslations[$cleanKey] = $value;
                }
            }
        }

        echo 'const LANG = ' . json_encode($jsTranslations) . ';';
        exit;

    case 'home':
    default:
        $gameController->index();
        break;
}

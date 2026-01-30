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
require_once ROOT_PATH . '/controllers/SocialController.php';
require_once ROOT_PATH . '/controllers/TrophyController.php';
require_once ROOT_PATH . '/config/lang.php';

$database = new Database();
$db = $database->getConnection();

$authController = new AuthController($db);
$gameController = new GameController($db);
$progressController = new ProgressController($db);
$communityController = new CommunityController($db);
$socialController = new SocialController($db);
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

    //Password/Reset
    case 'forgot_password':
        $authController->forgotPassword();
        break;
    case 'reset_password':
        $authController->showResetForm();
        break;
    case 'do_reset':
        $authController->doReset();
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

    // Recherche API
    case 'api_search':
        $gameController->apiSearch();
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

    // --- Social
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

    // Actus
    case 'feed':
        $socialController->feed();
        break;
    case 'add_comment':
        $socialController->addComment();
        break;

    case 'legal':
        $view = 'views/legal.php';
        require ROOT_PATH . '/views/layout.php';
        break;

    // Comments
    case 'add_comment':
        if (isset($socialController)) {
            $socialController->addComment();
        }
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

    // Générateur de fichier de langue JS
    case 'js_lang':
        header('Content-Type: application/javascript');
        // On filtre uniquement les clés commençant par 'js_' pour la sécurité et la performance
        $jsTranslations = [];
        if (isset($GLOBALS['translations'])) {
            foreach ($GLOBALS['translations'] as $key => $value) {
                if (strpos($key, 'js_') === 0) {
                    // On enlève le préfixe 'js_' pour avoir des clés propres en JS (ex: 'js_btn_edit' devient 'btn_edit')
                    $cleanKey = substr($key, 3);
                    $jsTranslations[$cleanKey] = $value;
                }
            }
        }
        // On affiche le code JS
        echo 'const LANG = ' . json_encode($jsTranslations) . ';';
        exit;

        // Default
    case 'home':
    default:
        $gameController->index();
        break;
}

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

$database = new Database();
$db = $database->getConnection();

$authController = new AuthController($db);
$gameController = new GameController($db);
$progressController = new ProgressController($db);
$communityController = new CommunityController($db);
$socialController = new SocialController($db);

$action = $_GET['action'] ?? 'home';

switch ($action) {
    // Auth & Profile
    case 'login': $authController->login(); break;
    case 'register': $authController->register(); break;
    case 'logout': $authController->logout(); break;
    case 'profile': $authController->profile(); break;
    case 'update_profile': $authController->updateProfile(); break;
    case 'delete_account': $authController->deleteAccount(); break;

    // Games
    case 'save': $gameController->save(); break;
    case 'delete': $gameController->delete(); break;
    case 'stats': $gameController->stats(); break;
    
    // Import/Export
    case 'export_json': $gameController->export(); break;
    case 'import_json': $gameController->import(); break;
    
    // Progression
    case 'progression': $progressController->index(); break;
    case 'add_progress': $progressController->add(); break;
    case 'delete_progress': $progressController->delete(); break;

    // --- Social
    case 'community': $communityController->index(); break;
    case 'toggle_follow': $communityController->toggleFollow(); break;

    // --- Actus
    case 'feed': $socialController->feed(); break;
    case 'add_comment': $socialController->addComment(); break;
    
    // Default
    case 'home':
    default: $gameController->index(); break;
}
?>
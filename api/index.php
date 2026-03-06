<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. AJOUT DU SESSION_START() OBLIGATOIRE POUR LE OAUTH STEAM
session_start();

define('ROOT_PATH', dirname(__DIR__));

if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/GameController.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

function sendJson($success, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// 3. Récupérer et vérifier le Token
$headers = null;
if (isset($_SERVER['Authorization'])) {
    $headers = trim($_SERVER["Authorization"]);
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    if (isset($requestHeaders['Authorization'])) {
        $headers = trim($requestHeaders['Authorization']);
    }
}

$token = null;
// Essayer de lire depuis le Header
if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
    $token = $matches[1];
} 
// MODIFICATION : Essayer de lire depuis l'URL (GET) pour api_steam_login
elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
}

$currentUser = null;
if ($token) {
    $currentUser = $userModel->getUserByToken($token);
}

// 4. Routage API
$action = $_GET['action'] ?? '';

// --- ROUTES PUBLIQUES (Ne nécessitent pas de token) ---
$publicRoutes = ['api_login', 'api_register', 'api_steam_callback'];

if ($action === 'api_login') {
    $authController = new AuthController($db);
    $authController->apiLogin();
    exit;
}
if ($action === 'api_register') {
    $authController = new AuthController($db);
    $authController->apiRegister();
    exit;
}

// --- VERIFICATION PROTEGEE ---
// MODIFICATION : On autorise le callback Steam car il n'a pas de Token
if (!in_array($action, $publicRoutes) && !$currentUser) {
    sendJson(false, 'Non autorisé. Token invalide ou manquant.', [], 401);
}

switch ($action) {

    // --- MISE À JOUR DU PROFIL ---
    case 'api_update_profile':
        $userId = $currentUser['id'];
        $username = $_POST['username'] ?? $currentUser['username'];
        $email = $_POST['email'] ?? $currentUser['email'];
        $language = $_POST['language'] ?? $currentUser['language'];
        $newPassword = !empty($_POST['new_password']) ? $_POST['new_password'] : null;
        
        // Gestion de l'upload d'avatar (comme sur User.php)
        $avatarUrl = $currentUser['avatar_url'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_avatar.' . $fileExt;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $fileName)) {
                $avatarUrl = 'uploads/avatars/' . $fileName;
            }
        }

        if ($userModel->updateProfile($userId, $username, $email, $language, $avatarUrl, $newPassword)) {
            sendJson(true, 'Profil mis à jour', ['new_avatar' => $avatarUrl]);
        } else {
            sendJson(false, 'Erreur lors de la mise à jour');
        }
        break;

    // --- EXPORT JSON ---
    case 'api_export_json':
        $gameController = new GameController($db);
        $gameController->apiExportJson($currentUser['id']);
        break;

    // --- IMPORT JSON ---
    case 'api_import_json':
        $gameController = new GameController($db);
        $gameController->apiImportJson($currentUser['id']);
        break;

    // --- SUPPRESSION DU COMPTE ---
    case 'api_delete_account':
        $userId = $currentUser['id'];
        if ($userModel->delete($userId)) {
            sendJson(true, 'Compte supprimé');
        } else {
            sendJson(false, 'Erreur lors de la suppression');
        }
        break;

    case 'api_get_games':
        $gameController = new GameController($db);
        $gameController->apiGetGames($currentUser['id']);
        break;

    case 'api_search_igdb':
        $gameController = new GameController($db);
        $query = $_GET['q'] ?? '';
        $gameController->apiSearchIgdb($currentUser['id'], $query);
        break;

    case 'api_save_game':
        $gameController = new GameController($db);
        $gameController->apiSaveGame($currentUser['id']);
        break;

    case 'api_update_game':
        $gameController = new GameController($db);
        $gameController->apiUpdateGame($currentUser['id']);
        break;

    case 'api_delete_game':
        $gameController = new GameController($db);
        $gameController->apiDeleteGame($currentUser['id']);
        break;

    // 1. DÉMARRAGE DE LA CONNEXION STEAM OPENID
    case 'api_steam_login':
        $redirectUrl = $_GET['redirect'] ?? '';
        
        // On sauvegarde l'URL de l'application Expo et l'ID utilisateur en session PHP
        $_SESSION['expo_redirect'] = $redirectUrl;
        $_SESSION['api_user_id'] = $currentUser['id']; // Utilise $currentUser !

        // Préparation de l'URL OpenID de Steam
        $steam_login_url = "https://steamcommunity.com/openid/login" .
            "?openid.ns=http://specs.openid.net/auth/2.0" .
            "&openid.mode=checkid_setup" .
            "&openid.return_to=" . urlencode("https://www.g-played.com/api/index.php?action=api_steam_callback") .
            "&openid.realm=" . urlencode("https://www.g-played.com") .
            "&openid.identity=http://specs.openid.net/auth/2.0/identifier_select" .
            "&openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select";

        header("Location: " . $steam_login_url);
        exit;

    // 2. RETOUR DE STEAM APRÈS AUTHENTIFICATION (Route Publique)
    case 'api_steam_callback':
        if (isset($_GET['openid_claimed_id'])) {
            preg_match('/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/', $_GET['openid_claimed_id'], $matches);
            $steamId = $matches[1] ?? null;
            $userId = $_SESSION['api_user_id'] ?? null;
            $redirectUrl = $_SESSION['expo_redirect'] ?? 'gplayed://'; 

            if ($steamId && $userId) {
                $stmt = $db->prepare("UPDATE users SET steam_id = ? WHERE id = ?");
                $stmt->execute([$steamId, $userId]);
            }

            unset($_SESSION['api_user_id']);
            unset($_SESSION['expo_redirect']);

            header("Location: " . $redirectUrl);
            exit;
        }
        die('Échec de la connexion avec Steam.');


    // 3. RÉCUPÉRATION DE LA LISTE DES JEUX VIA L'API STEAM
    case 'api_steam_games':
        $userId = $currentUser['id'];

        $stmt = $db->prepare("SELECT steam_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $steamId = $stmt->fetchColumn();

        if (!$steamId) {
            sendJson(false, 'Aucun compte Steam lié.', [], 400);
        }

        $steamApiKey = $_ENV['STEAM_API_KEY'] ?? ''; 
        $url = "http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$steamApiKey}&steamid={$steamId}&format=json&include_appinfo=1";
        
        // Utilisation de cURL (comme sur le site web) pour éviter les blocages
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['response']['games'])) {
             sendJson(false, 'Profil Steam privé ou vide.', [], 400);
        }

        // On récupère les jeux déjà possédés pour éviter les doublons
        $stmt = $db->prepare("SELECT title FROM games WHERE user_id = ? AND platform = 'PC'");
        $stmt->execute([$userId]);
        $ownedGames = array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));

        // Liste noire pour ignorer les bêtas, serveurs, etc. (repris du site web)
        $blacklist = ['beta', 'alpha', 'server', 'dedicated server', 'test', 'test server', 'public test', 'demo', 'sdk'];

        $gamesToImport = [];
        foreach ($data['response']['games'] as $game) {
            $gameName = $game['name'];
            $shouldIgnore = false;

            foreach ($blacklist as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $gameName)) {
                    $shouldIgnore = true;
                    break;
                }
            }

            if ($shouldIgnore) continue;

            if (!in_array(strtolower($gameName), $ownedGames)) {
                $gamesToImport[] = [
                    'appid' => $game['appid'],
                    'name' => $gameName,
                    'playtime_forever' => $game['playtime_forever'] ?? 0,
                    'rtime_last_played' => $game['rtime_last_played'] ?? 0
                ];
            }
        }

        sendJson(true, 'Jeux récupérés', ['games' => $gamesToImport]);
        break;


    // 4. IMPORTATION D'UN JEU UNIQUE
    case 'api_steam_import_single':
        $userId = $currentUser['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input && isset($input['appid'])) {
            // On inclut le modèle Playtime comme sur le web
            require_once ROOT_PATH . '/models/Playtime.php';
            $playtimeModel = new Playtime($db);

            $name = $input['name'];
            $playtimeMinutes = $input['playtime_forever'] ?? 0;
            $lastPlayedTimestamp = $input['rtime_last_played'] ?? 0;
            $oneYearAgo = time() - (365 * 24 * 60 * 60);

            // Logique du site web : détermination automatique du statut
            $status = 'not_started';
            if ($playtimeMinutes > 0) {
                $status = 'playing';
                if ($lastPlayedTimestamp > 0 && $lastPlayedTimestamp < $oneYearAgo) {
                    $status = 'dropped';
                }
            }

            // URL de l'image (Cloudflare comme sur le web)
            $imageUrl = "https://cdn.cloudflare.steamstatic.com/steam/apps/" . $input['appid'] . "/header.jpg";

            // CORRECTION CRITIQUE : Les colonnes sont `title` et `image_url`
            $stmt = $db->prepare("INSERT INTO games (user_id, title, platform, format, image_url, status, created_at) VALUES (?, ?, 'PC', 'digital', ?, ?, NOW())");
            $success = $stmt->execute([$userId, $name, $imageUrl, $status]);

            // Enregistrement du temps de jeu
            if ($success) {
                $newGameId = $db->lastInsertId();
                if ($playtimeMinutes > 0 && $newGameId) {
                    $playtimeModel->save($newGameId, round($playtimeMinutes / 60, 1), null);
                }
            }

            sendJson($success, 'Importation terminée');
        } else {
            sendJson(false, 'Données manquantes', [], 400);
        }
        break;


    // 5. FINALISATION DE LA SYNCHRONISATION
    case 'api_steam_complete':
        $userId = $currentUser['id'];
        
        // Optionnel : Enregistrer la date de la dernière synchronisation
        // $stmt = $db->prepare("UPDATE users SET last_steam_sync = NOW() WHERE id = ?");
        // $stmt->execute([$userId]);
        
        sendJson(true, 'Synchronisation clôturée');
        break;
        
    default:
        sendJson(false, 'Route inconnue', [], 404);
}
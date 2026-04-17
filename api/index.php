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
require_once ROOT_PATH . '/controllers/ProgressController.php';
require_once ROOT_PATH . '/models/Game.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$gameModel = new Game($db);

function sendJson($success, $message, $data = [], $httpCode = 200)
{
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
$publicRoutes = [
    'api_login',
    'api_register',
    'api_steam_callback',
    'api_mobile_verify',
    'api_forgot_password',
    'api_reset_password',
    'app_bounce'
];

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
if ($action === 'api_mobile_verify') {
    $authController = new AuthController($db);
    $authController->apiMobileVerify();
    exit;
}
if ($action === 'api_forgot_password') {
    $authController = new AuthController($db);
    $authController->apiForgotPassword();
    exit;
}
if ($action === 'api_reset_password') {
    $authController = new AuthController($db);
    $authController->apiResetPassword();
    exit;
}

if ($action === 'app_bounce') {
    $target = $_GET['target'] ?? '';
    if (!empty($target)) {
        // Redirige le navigateur vers exp:// ou gplayed://
        header("Location: " . $target);
        exit;
    }
    // Secours si le paramètre est vide
    header("Location: /");
    exit;
}

// --- VERIFICATION PROTEGEE ---
// MODIFICATION : On autorise le callback Steam car il n'a pas de Token
if (!in_array($action, $publicRoutes) && !$currentUser) {
    sendJson(false, 'Non autorisé. Token invalide ou manquant.', [], 401);
}

switch ($action) {

    // --- NOM UTILISATEUR ---
    case 'api_get_profile':
        sendJson(true, 'Profil récupéré', ['user' => [
            'id' => $currentUser['id'],
            'username' => $currentUser['username'],
            'email' => $currentUser['email'],
            'avatar_url' => $currentUser['avatar_url']
        ]]);
        break;

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
        $userId = $currentUser['id'];

        $games = $gameModel->getAll($userId);

        if ($games !== false) {
            foreach ($games as &$game) {
                $game['description'] = $game['summary'] ?? '';

                // 1. Correction du nom de la clé : game_id au lieu de igdb_id
                if (empty($game['screenshots']) && !empty($game['game_id'])) {
                    $game['screenshots'] = $gameController->getOrFetchScreenshots($game['id'], $game['game_id']);
                }

                // 2. CONVERSION CRITIQUE : String -> Array pour le mobile
                if (!empty($game['screenshots']) && is_string($game['screenshots'])) {
                    $game['screenshots'] = explode(',', $game['screenshots']);
                } else {
                    // On renvoie un tableau vide plutôt qu'une chaîne vide
                    $game['screenshots'] = [];
                }
            }
            sendJson(true, 'Collection récupérée avec succès.', ['data' => $games]);
        }
        break;

    case 'api_search_igdb':
        $gameController = new GameController($db);
        $query = $_GET['q'] ?? '';
        $gameController->apiSearchIgdb($currentUser['id'], $query);
        break;

    case 'api_save_game':
        $userId = $currentUser['id'];
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data) {
            // Préparation des screenshots (fusion screenshots + artworks si nécessaire)
            $screenshots = isset($data['screenshots']) && is_array($data['screenshots']) ? implode(',', $data['screenshots']) : '';

            $gameData = [
                'rawg_id'        => $data['rawg_id'] ?? null,
                'title'          => $data['title'] ?? 'Titre inconnu',
                'platform'       => $data['platform'] ?? '',
                'platforms_list' => $data['platforms_list'] ?? '',
                'status'         => $data['status'] ?? 'not_started',
                'format'         => $data['format'] ?? 'physical',
                'image_url'      => $data['background_image'] ?? null,
                'metacritic'     => $data['metacritic'] ?? null,
                'genres'         => $data['genres'] ?? '',
                'release_date'   => $data['released'] ?? null,
                'description'    => $data['description'] ?? '',
                'developer'      => $data['developer'] ?? null,
                'publisher'      => $data['publisher'] ?? null,
                'screenshots'    => $screenshots,
                'comment'        => '',
                'estimated_price' => null
            ];

            $success = $gameModel->importEntry($gameData, $userId);
            sendJson($success, $success ? 'Jeu ajouté' : 'Erreur lors de l\'ajout');
        }
        break;

    case 'api_update_game':
        $gameController = new GameController($db);
        $gameController->apiUpdateGame($currentUser['id']);
        break;

    case 'api_delete_game':
        $gameController = new GameController($db);
        $gameController->apiDeleteGame($currentUser['id']);
        break;

    // --- PROGRESSION ---
    case 'api_get_progress':
        $progressController = new ProgressController($db);
        $progressController->apiGetProgress($currentUser['id']);
        break;

    case 'api_add_progress':
        $progressController = new ProgressController($db);
        $progressController->apiAddProgress($currentUser['id']);
        break;

    case 'api_delete_progress':
        $progressController = new ProgressController($db);
        $progressController->apiDeleteProgress($currentUser['id']);
        break;

    // --- RÉCUPÉRATION D'UNE COLLECTION PUBLIQUE ---
    case 'api_get_public_collection':
        $username = $_GET['username'] ?? '';
        if (empty($username)) {
            sendJson(false, 'Pseudo manquant', [], 400);
        }

        // Récupérer l'ID de l'utilisateur à partir du pseudo
        $stmt = $db->prepare("SELECT id, username, avatar_url, created_at FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$owner) {
            sendJson(false, 'Utilisateur introuvable', [], 404);
        }

        // Récupérer les jeux (uniquement ceux qui ne sont pas en wishlist/prêtés selon votre logique web)
        $gameController = new GameController($db);

        // NOUVELLE REQUÊTE : Jointure entre user_games et games
        $stmtGames = $db->prepare("
            SELECT 
                ug.*, 
                g.title, 
                g.cover_url AS image_url, 
                g.genres, 
                g.release_date,
                g.platforms_list,
                g.summary,
                g.developer,
                g.publisher,
                g.screenshots
            FROM user_games ug 
            JOIN games g ON ug.game_id = g.id 
            WHERE ug.user_id = ? AND ug.status NOT IN ('wishlist', 'loaned') 
            ORDER BY ug.created_at DESC
        ");
        $stmtGames->execute([$owner['id']]);
        $games = $stmtGames->fetchAll(PDO::FETCH_ASSOC);

        // Vérifier si l'utilisateur actuel suit ce profil
        $isFollowing = false;
        if ($currentUser) {
            $followingIds = $userModel->getFollowedIds($currentUser['id']);
            $isFollowing = in_array($owner['id'], $followingIds);
        }

        sendJson(true, 'Collection récupérée', [
            'owner' => $owner,
            'games' => $games,
            'isFollowing' => $isFollowing
        ]);
        break;

    // --- RÉCUPÉRATION DES UTILISATEURS ---
    case 'api_get_community':
        $users = $userModel->getAllUsersExcept($currentUser['id']);
        $following = $userModel->getFollowedIds($currentUser['id']);

        sendJson(true, 'Liste récupérée', [
            'users' => $users,
            'following' => $following
        ]);
        break;

    // --- GESTION DES ABONNEMENTS (FOLLOW/UNFOLLOW) ---
    case 'api_toggle_follow':
        $targetId = $_GET['id'] ?? null;
        $do = $_GET['do'] ?? 'follow'; // 'follow' ou 'unfollow'

        if (!$targetId) {
            sendJson(false, 'ID utilisateur cible manquant', [], 400);
        }

        if ($do === 'follow') {
            $success = $userModel->follow($currentUser['id'], $targetId);
            $msg = "Abonnement ajouté !";
        } else {
            $success = $userModel->unfollow($currentUser['id'], $targetId);
            $msg = "Désabonnement effectué.";
        }

        sendJson($success, $success ? $msg : "Erreur lors de l'action");
        break;

    // --- PSN MOBILE API ---
    case 'api_get_psn_games':
        require_once ROOT_PATH . '/models/Psn.php';
        $psnModel = new Psn($db);
        $psnGames = $psnModel->getUserPsnGamesWithStats($currentUser['id']);
        sendJson(true, 'Jeux PSN récupérés', ['games' => $psnGames]);
        break;

    case 'api_get_psn_trophies':
        require_once ROOT_PATH . '/models/Psn.php';
        $psnModel = new Psn($db);
        $psnGameId = $_GET['psn_game_id'] ?? null;
        if (!$psnGameId) sendJson(false, 'ID du jeu manquant');

        $trophies = $psnModel->getTrophiesByGame($psnGameId);
        sendJson(true, 'Trophées récupérés', ['trophies' => $trophies]);
        break;

    case 'api_psn_sync':
        require_once ROOT_PATH . '/models/Psn.php';
        $userId = $currentUser['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        $psnId = trim($input['psn_id'] ?? '');

        if (empty($psnId)) {
            sendJson(false, 'ID PSN manquant.');
        }

        $userModel->setPsnId($userId, $psnId);
        $user = $userModel->getById($userId);

        // Vérification de la limite de 1 heure
        if ($user['last_psn_sync']) {
            $lastSync = new DateTime($user['last_psn_sync']);
            $now = new DateTime();
            if (($now->getTimestamp() - $lastSync->getTimestamp()) < 3600) {
                $remaining = 60 - floor(($now->getTimestamp() - $lastSync->getTimestamp()) / 60);
                sendJson(false, "Veuillez attendre $remaining minutes avant la prochaine synchronisation.");
            }
        }

        // Exécution de la synchronisation via la logique du Controller
        require_once ROOT_PATH . '/controllers/PsnController.php';
        $psnController = new PsnController($db);

        // Utilisation de la ReflectionClass pour appeler la méthode privée executeSync du Controller
        $reflection = new ReflectionClass('PsnController');
        $method = $reflection->getMethod('executeSync');
        $method->setAccessible(true);
        $result = $method->invoke($psnController, $userId, $psnId);

        if ($result) {
            $userModel->updateLastPsnSync($userId);
            sendJson(true, 'Synchronisation réussie', [
                'games_count' => $result['games'],
                'trophies_count' => $result['trophies']
            ]);
        } else {
            sendJson(false, 'Échec de la synchronisation avec le service PSN.');
        }
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
        $stmt = $db->prepare("
            SELECT g.title 
            FROM user_games ug 
            JOIN games g ON ug.game_id = g.id 
            WHERE ug.user_id = ? AND ug.platform = 'PC'
        ");
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
            require_once ROOT_PATH . '/models/Playtime.php';
            require_once ROOT_PATH . '/models/Game.php'; // On charge le modèle

            $playtimeModel = new Playtime($db);
            $gameModel = new Game($db);

            $name = $input['name'];
            $appId = $input['appid'];
            $playtimeMinutes = $input['playtime_forever'] ?? 0;
            $lastPlayedTimestamp = $input['rtime_last_played'] ?? 0;
            $oneYearAgo = time() - (365 * 24 * 60 * 60);

            $status = 'not_started';
            if ($playtimeMinutes > 0) {
                $status = 'playing';
                if ($lastPlayedTimestamp > 0 && $lastPlayedTimestamp < $oneYearAgo) {
                    $status = 'dropped';
                }
            }

            $imageUrl = "https://cdn.cloudflare.steamstatic.com/steam/apps/" . $appId . "/header.jpg";

            // LOGIQUE ADAPTÉE À LA NOUVELLE ARCHITECTURE
            $gameData = [
                'rawg_id' => $appId,
                'title' => $name,
                'image_url' => $imageUrl,
                'status' => $status,
                'platform' => 'PC',
                'platforms_list' => 'PC',
                'format' => 'digital',
                'comment' => '',
                'estimated_price' => null
            ];

            // importEntry s'occupe de renseigner 'games' ET 'user_games'
            $success = $gameModel->importEntry($gameData, $userId);

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

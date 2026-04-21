<?php
// Fichier : api/update_screenshots.php
session_start();

require_once '../config/Database.php';

// 1. Chargement des variables d'environnement
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (empty($line) || strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

$igdbClientId = $_ENV['IGDB_CLIENT_ID'] ?? getenv('IGDB_CLIENT_ID');
$igdbClientSecret = $_ENV['IGDB_CLIENT_SECRET'] ?? getenv('IGDB_CLIENT_SECRET');

if (!$igdbClientId || !$igdbClientSecret) {
    die("Erreur : Les identifiants IGDB sont manquants dans le .env");
}

// 2. Génération du Token Twitch
function getTwitchAccessToken($clientId, $clientSecret) {
    $ch = curl_init('https://id.twitch.tv/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'client_credentials'
    ]));
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    return $data['access_token'] ?? null;
}

// 3. Récupération spécifique des screenshots par ID IGDB
function fetchScreenshotsById($gameId, $clientId, $accessToken) {
    $ch = curl_init();
    
    // On cible directement l'ID du jeu
    $query = "fields screenshots.image_id; where id = $gameId;";

    curl_setopt($ch, CURLOPT_URL, "https://api.igdb.com/v4/games");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Client-ID: $clientId",
        "Authorization: Bearer $accessToken",
        "Accept: application/json"
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200) {
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0]['screenshots'])) {
            return implode(', ', array_map(function($s) {
                return "https://images.igdb.com/igdb/image/upload/t_720p/" . $s['image_id'] . ".jpg";
            }, $data[0]['screenshots']));
        }
    }
    return null;
}

// === EXÉCUTION PAR LOTS ===

$igdbAccessToken = getTwitchAccessToken($igdbClientId, $igdbClientSecret);
if (!$igdbAccessToken) die("Erreur : Jeton Twitch introuvable.");

$database = new Database();
$db = $database->getConnection();

$limit = 20; // On peut monter à 20 ici car la requête par ID est très rapide
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    // Compte total pour la progression
    $totalQuery = $db->query("SELECT COUNT(*) FROM games");
    $totalGames = $totalQuery->fetchColumn();

    // On récupère les jeux qui n'ont pas encore de screenshots
    $stmt = $db->prepare("SELECT id, title FROM games LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($games)) {
        echo "<h2 style='color:green;'>Mise à jour des screenshots terminée !</h2>";
        exit;
    }

    echo "<h3>Mise à jour des captures d'écran...</h3>";
    echo "Progression : " . min(($offset + $limit), $totalGames) . " / $totalGames jeux.<br><br>";

    foreach ($games as $game) {
        $screenshots = fetchScreenshotsById($game['id'], $igdbClientId, $igdbAccessToken);

        if ($screenshots) {
            $update = $db->prepare("UPDATE games SET screenshots = ? WHERE id = ?");
            $update->execute([$screenshots, $game['id']]);
            echo "✅ " . htmlspecialchars($game['title']) . " : Screenshots ajoutés.<br>";
        } else {
            echo "⚪ " . htmlspecialchars($game['title']) . " : Aucune capture trouvée.<br>";
        }
        
        // Petite pause pour respecter le Rate Limit
        usleep(150000); 
    }

    $next = $offset + $limit;
    echo "<hr>Redirection vers le lot suivant...";
    echo "<script>setTimeout(() => { window.location.href = '?offset=$next'; }, 1000);</script>";

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
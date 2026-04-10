<?php
// Fichier : api/restore_games_igdb.php
session_start();

require_once '../config/Database.php';

// --- CONFIGURATION DEBUG ---
define('DEBUG_MODE', true); // Mettez à true pour voir les erreurs détaillées d'IGDB

// Initialisation des variables de session
if (!isset($_GET['offset']) || $_GET['offset'] == 0) {
    $_SESSION['migration_not_found'] = [];
    $_SESSION['migration_steam_ignored'] = 0;
    $_SESSION['migration_restored'] = 0;
    $_SESSION['migration_catalog'] = 0;
}

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
    die("Erreur : Les identifiants IGDB (CLIENT_ID ou CLIENT_SECRET) sont manquants dans le .env");
}

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

function fetchIgdbGame($title, $clientId, $accessToken) {
    // Nettoyage ultra-simplifié pour éviter de casser la requête
    $searchTerm = trim($title);
    $searchTerm = str_replace(['"', '\\'], '', $searchTerm);
    
    if (empty($searchTerm)) return null;

    $ch = curl_init();
    
    // On retire le filtre category de la requête IGDB pour être sûr de trouver quelque chose
    // On fera le tri sur la catégorie et le titre directement en PHP
    $query = "search \"$searchTerm\"; fields id, name, category, cover.image_id, genres.name, first_release_date, summary, involved_companies.company.name, involved_companies.developer, involved_companies.publisher, total_rating; limit 10;";

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
        $results = json_decode($response, true);
        if (empty($results)) return null;

        $bestMatch = null;
        $maxScore = -1;

        foreach ($results as $game) {
            $score = 0;
            $gameName = $game['name'];
            $category = $game['category'] ?? -1;

            // 1. Calcul de la ressemblance textuelle (0 à 100)
            similar_text(mb_strtolower($title), mb_strtolower($gameName), $simPercent);
            $score += $simPercent;

            // 2. Bonus si c'est un "Main Game" (Catégorie 0)
            if ($category === 0) $score += 50; 
            
            // 3. Malus si c'est un DLC ou un Bundle (pour éviter les erreurs sur Sea of Stars)
            if ($category === 1 || $category === 3) $score -= 40;

            if ($score > $maxScore) {
                $maxScore = $score;
                $bestMatch = $game;
            }
        }
        return $bestMatch;
    } elseif (DEBUG_MODE) {
        echo "<div style='color:orange;'>Erreur API pour '$title' : Code $httpcode | Réponse : $response</div>";
    }
    return null;
}

// --- EXECUTION ---
$igdbAccessToken = getTwitchAccessToken($igdbClientId, $igdbClientSecret);
if (!$igdbAccessToken) die("Erreur : Impossible de récupérer le Token Twitch.");

$database = new Database();
$db = $database->getConnection();

$limit = 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $totalQuery = $db->query("SELECT COUNT(*) FROM old_games");
    $totalGames = $totalQuery->fetchColumn();

    $stmtOld = $db->prepare("SELECT * FROM old_games LIMIT :limit OFFSET :offset");
    $stmtOld->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtOld->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtOld->execute();
    $oldGames = $stmtOld->fetchAll(PDO::FETCH_ASSOC);

    if (empty($oldGames)) {
        echo "<h2 style='color:green;'>Migration Terminée !</h2>";
        // ... (Rapport final identique aux versions précédentes)
        exit;
    }

    echo "<h3>Migration en cours (Offset : $offset / $totalGames)...</h3>";

    foreach ($oldGames as $oldGame) {
        $title = $oldGame['title'];
        $comment = $oldGame['comment'] ?? '';

        if (stripos($comment, 'steam') !== false) {
            $_SESSION['migration_steam_ignored']++;
            echo "SKIPPED : $title (Steam)<br>";
            continue;
        }

        $igdbData = fetchIgdbGame($title, $igdbClientId, $igdbAccessToken);

        if ($igdbData) {
            $igdbId = $igdbData['id'];

            // Insertion Catalogue games
            $check = $db->prepare("SELECT id FROM games WHERE id = ?");
            $check->execute([$igdbId]);
            if (!$check->fetch()) {
                $cover = isset($igdbData['cover']['image_id']) ? "https://images.igdb.com/igdb/image/upload/t_720p/{$igdbData['cover']['image_id']}.jpg" : null;
                $genres = isset($igdbData['genres']) ? implode(', ', array_column($igdbData['genres'], 'name')) : null;
                $date = isset($igdbData['first_release_date']) ? date('Y-m-d', $igdbData['first_release_date']) : null;
                
                $dev = null; $pub = null;
                if (isset($igdbData['involved_companies'])) {
                    foreach ($igdbData['involved_companies'] as $comp) {
                        if ($comp['developer']) $dev = $comp['company']['name'];
                        if ($comp['publisher']) $pub = $comp['company']['name'];
                    }
                }

                $ins = $db->prepare("INSERT INTO games (id, title, cover_url, genres, release_date, summary, developer, publisher, rating, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
                $ins->execute([$igdbId, $igdbData['name'], $cover, $genres, $date, $igdbData['summary'] ?? null, $dev, $pub, $igdbData['total_rating'] ?? null]);
                $_SESSION['migration_catalog']++;
            }

            // Insertion user_games
            $checkUg = $db->prepare("SELECT id FROM user_games WHERE user_id = ? AND game_id = ? AND platform = ?");
            $checkUg->execute([$oldGame['user_id'], $igdbId, $oldGame['platform']]);
            if (!$checkUg->fetch()) {
                $insUg = $db->prepare("INSERT INTO user_games (user_id, game_id, platform, format, status, comment, dominant_color, estimated_price) VALUES (?,?,?,?,?,?,?,?)");
                $insUg->execute([$oldGame['user_id'], $igdbId, $oldGame['platform'], $oldGame['format'], $oldGame['status'], $comment, $oldGame['dominant_color'], $oldGame['estimated_price']]);
                $_SESSION['migration_restored']++;
            }

            echo "OK : <strong>$title</strong> &rarr; " . htmlspecialchars($igdbData['name']) . "<br>";
        } else {
            $_SESSION['migration_not_found'][] = ['user_id' => $oldGame['user_id'], 'title' => $title];
            echo "<span style='color:red;'>NOT FOUND : $title</span><br>";
        }
        usleep(250000);
    }

    $next = $offset + $limit;
    echo "<script>setTimeout(() => { window.location.href = '?offset=$next'; }, 1500);</script>";

} catch (Exception $e) {
    die("Erreur Fatale : " . $e->getMessage());
}
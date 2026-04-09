<?php
// Fichier : api/restore_games_igdb.php
session_start();

require_once '../config/Database.php';

// Initialisation des variables de session
if (!isset($_GET['offset']) || $_GET['offset'] == 0) {
    $_SESSION['migration_not_found'] = [];
    $_SESSION['migration_steam_ignored'] = 0;
    $_SESSION['migration_restored'] = 0;
    $_SESSION['migration_catalog'] = 0;
}

// 1. Fonction pour charger les variables du fichier .env
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

$igdbClientId = $_ENV['IGDB_CLIENT_ID'] ?? getenv('IGDB_CLIENT_ID');
$igdbClientSecret = $_ENV['IGDB_CLIENT_SECRET'] ?? getenv('IGDB_CLIENT_SECRET');

if (!$igdbClientId || !$igdbClientSecret) {
    die("Erreur : Les identifiants IGDB sont introuvables dans le fichier .env.");
}

// 2. Fonction pour générer le jeton d'accès Twitch
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
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// 3. Fonction pour interroger l'API IGDB
function fetchIgdbGame($title, $clientId, $accessToken) {
    $cleanTitle = trim(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $title));
    $cleanTitle = preg_replace('/\s+/', ' ', $cleanTitle);

    $ch = curl_init();
    $query = 'search "' . addslashes($cleanTitle) . '"; fields id, name, cover.image_id, genres.name, first_release_date, summary, involved_companies.company.name, involved_companies.developer, involved_companies.publisher, total_rating; limit 1;';

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

    if ($httpcode == 200 && $response) {
        $data = json_decode($response, true);
        return !empty($data) ? $data[0] : null;
    }
    return null;
}

// === DEBUT DU SCRIPT BATCH ===

$igdbAccessToken = getTwitchAccessToken($igdbClientId, $igdbClientSecret);
if (!$igdbAccessToken) {
    die("Erreur : Impossible de générer le jeton d'accès Twitch.");
}

$database = new Database();
$db = $database->getConnection();

$limit = 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $totalQuery = $db->query("SELECT COUNT(*) FROM old_games");
    $totalGames = $totalQuery->fetchColumn();

    $queryOld = "SELECT * FROM old_games LIMIT :limit OFFSET :offset"; 
    $stmtOld = $db->prepare($queryOld);
    $stmtOld->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtOld->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtOld->execute();
    
    $oldGames = $stmtOld->fetchAll(PDO::FETCH_ASSOC);

    // --- FIN DE LA MIGRATION ---
    if (count($oldGames) === 0) {
        echo "<h2 style='color: green;'>Migration totalement terminée !</h2>";
        echo "<ul>";
        echo "<li>Jeux uniques ajoutés au catalogue : <strong>" . $_SESSION['migration_catalog'] . "</strong></li>";
        echo "<li>Jeux restaurés dans les collections : <strong>" . $_SESSION['migration_restored'] . "</strong></li>";
        echo "<li>Jeux Steam ignorés (non traités) : <strong>" . $_SESSION['migration_steam_ignored'] . "</strong></li>";
        echo "</ul>";

        if (!empty($_SESSION['migration_not_found'])) {
            echo "<hr><h3>⚠️ Liste des jeux non trouvés par IGDB :</h3>";
            echo "<ul>";
            
            $groupedNotFound = [];
            foreach ($_SESSION['migration_not_found'] as $nf) {
                $groupedNotFound[$nf['user_id']][] = $nf['title'];
            }
            
            foreach ($groupedNotFound as $uid => $titles) {
                $stmtUser = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmtUser->execute([$uid]);
                $username = $stmtUser->fetchColumn() ?: "Utilisateur Inconnu (ID $uid)";
                
                echo "<li><strong>" . htmlspecialchars($username) . "</strong> : " . implode(' <strong>|</strong> ', array_map('htmlspecialchars', $titles)) . "</li>";
            }
            echo "</ul>";
        }

        unset($_SESSION['migration_not_found'], $_SESSION['migration_steam_ignored'], $_SESSION['migration_restored'], $_SESSION['migration_catalog']);
        exit;
    }

    // --- EN COURS DE MIGRATION ---
    echo "<h3>Migration en cours... Ne fermez pas cette page.</h3>";
    echo "Progression : " . min(($offset + $limit), $totalGames) . " sur $totalGames jeux traités.<br><br>";

    foreach ($oldGames as $oldGame) {
        $userId = $oldGame['user_id'];
        $oldTitle = $oldGame['title'];
        $comment = $oldGame['comment'] ?? '';
        
        // 1. GESTION DES JEUX STEAM (Correction avec stripos pour correspondre à tout mot "steam")
        if (stripos($comment, 'steam') !== false) {
            $_SESSION['migration_steam_ignored']++;
            echo "<span style='color:blue;'>Ignoré (Steam) : " . htmlspecialchars($oldTitle) . "</span><br>";
            continue; 
        }

        // 2. RECHERCHE IGDB
        $igdbData = fetchIgdbGame($oldTitle, $igdbClientId, $igdbAccessToken);
        
        if (!$igdbData || !isset($igdbData['id'])) {
            $_SESSION['migration_not_found'][] = [
                'user_id' => $userId,
                'title' => $oldTitle
            ];
            echo "<span style='color:red;'>Non trouvé sur IGDB : " . htmlspecialchars($oldTitle) . "</span><br>";
            continue; 
        }

        $igdbId = $igdbData['id'];

        // 3. CATALOGUE GLOBAL
        $checkCatalog = "SELECT id FROM games WHERE id = :id LIMIT 1";
        $stmtCat = $db->prepare($checkCatalog);
        $stmtCat->execute([':id' => $igdbId]);
        
        if ($stmtCat->rowCount() == 0) {
            $coverUrl = isset($igdbData['cover']['image_id']) ? 'https://images.igdb.com/igdb/image/upload/t_720p/' . $igdbData['cover']['image_id'] . '.jpg' : ($oldGame['image_url'] ?? null);
            $genres = isset($igdbData['genres']) ? implode(', ', array_column($igdbData['genres'], 'name')) : ($oldGame['genres'] ?? null);
            $releaseDate = isset($igdbData['first_release_date']) ? date('Y-m-d', $igdbData['first_release_date']) : ($oldGame['release_date'] ?? null);
            $summary = $igdbData['summary'] ?? ($oldGame['description'] ?? null);
            $rating = isset($igdbData['total_rating']) ? round($igdbData['total_rating']) : ($oldGame['metacritic_score'] ?? null);
            
            $developer = null;
            $publisher = null;
            if (isset($igdbData['involved_companies'])) {
                foreach ($igdbData['involved_companies'] as $company) {
                    if (isset($company['developer']) && $company['developer']) $developer = $company['company']['name'];
                    if (isset($company['publisher']) && $company['publisher']) $publisher = $company['company']['name'];
                }
            }

            $insertCatalog = "INSERT INTO games (id, title, cover_url, genres, release_date, summary, developer, publisher, rating, created_at) 
                              VALUES (:id, :title, :cover_url, :genres, :release_date, :summary, :developer, :publisher, :rating, NOW())";
            $stmtInsertCat = $db->prepare($insertCatalog);
            $stmtInsertCat->execute([
                ':id'           => $igdbId,
                ':title'        => $igdbData['name'],
                ':cover_url'    => $coverUrl,
                ':genres'       => $genres,
                ':release_date' => $releaseDate,
                ':summary'      => $summary,
                ':developer'    => $developer,
                ':publisher'    => $publisher,
                ':rating'       => $rating
            ]);
            $_SESSION['migration_catalog']++;
        }
        
        // 4. COLLECTION UTILISATEUR
        $checkUserGame = "SELECT id FROM user_games WHERE user_id = :uid AND game_id = :gid AND platform = :plat LIMIT 1";
        $stmtUg = $db->prepare($checkUserGame);
        $stmtUg->execute([
            ':uid'  => $userId,
            ':gid'  => $igdbId,
            ':plat' => $oldGame['platform']
        ]);
        
        if ($stmtUg->rowCount() == 0) {
            $insertUserGame = "INSERT INTO user_games 
                (user_id, game_id, platform, format, status, comment, dominant_color, estimated_price) 
                VALUES (:uid, :gid, :platform, :format, :status, :comment, :color, :price)";
            
            $stmtInsertUg = $db->prepare($insertUserGame);
            $stmtInsertUg->execute([
                ':uid'      => $userId,
                ':gid'      => $igdbId,
                ':platform' => $oldGame['platform'],
                ':format'   => $oldGame['format'] ?? 'digital',
                ':status'   => $oldGame['status'] ?? 'not_started',
                ':comment'  => $comment,
                ':color'    => $oldGame['dominant_color'] ?? null,
                ':price'    => $oldGame['estimated_price'] ?? null
            ]);
            $_SESSION['migration_restored']++;
        }

        echo "Traité : " . htmlspecialchars($oldTitle) . "<br>";
        usleep(250000); 
    }

    echo "<hr>Lot terminé. Redirection vers le lot suivant dans 2 secondes...";

    $nextOffset = $offset + $limit;
    echo "<script>
        setTimeout(function() {
            window.location.href = '?offset=$nextOffset';
        }, 2000);
    </script>";

} catch (PDOException $e) {
    echo "Erreur lors de la migration BDD : " . $e->getMessage();
}
?>
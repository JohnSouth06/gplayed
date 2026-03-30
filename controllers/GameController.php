<?php
require_once dirname(__DIR__) . '/models/Game.php';
require_once dirname(__DIR__) . '/models/User.php';

class GameController
{
    private $gameModel;
    private $userModel;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->gameModel = new Game($db);
        $this->userModel = new User($db);
    }

    // ==========================================
    //            API (MOBILE)
    // ==========================================

    private function apiResponse($success, $message, $data = [], $httpCode = 200)
    {
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit();
    }

    public function apiGetGames($userId)
    {
        $games = $this->gameModel->getAll($userId);
        if ($games !== false) {
            $this->apiResponse(true, 'Collection récupérée avec succès.', ['data' => $games]);
        } else {
            $this->apiResponse(false, 'Erreur lors de la récupération des jeux.', [], 500);
        }
    }

    public function apiSearchIgdb($userId, $query)
    {
        if (strlen(trim($query)) < 2) {
            $this->apiResponse(false, 'La recherche doit contenir au moins 2 caractères.', ['data' => []]);
        }

        // 1. AJOUT DE "total_rating" dans la requête IGDB
        $body = 'search "' . str_replace('"', '', $query) . '"; fields name, cover.url, first_release_date, platforms.name, total_rating, genres.name; limit 15;';
        $results = $this->callIgdb('games', $body);

        $formatted = [];
        if ($results && is_array($results)) {
            foreach ($results as $game) {
                $img = isset($game['cover']['url']) ? 'https:' . str_replace('t_thumb', 't_cover_big', $game['cover']['url']) : '';
                $date = isset($game['first_release_date']) ? date('Y', $game['first_release_date']) : '';

                // 2. EXTRACTION DES PLATEFORMES
                $platforms = [];
                if (isset($game['platforms']) && is_array($game['platforms'])) {
                    foreach ($game['platforms'] as $plat) {
                        if (isset($plat['name'])) {
                            $platforms[] = $plat['name'];
                        }
                    }
                }

                // 3. EXTRACTION DES GENRES
                $genres = [];
                if (isset($game['genres']) && is_array($game['genres'])) {
                    foreach ($game['genres'] as $gen) {
                        if (isset($gen['name'])) {
                            $genres[] = $gen['name'];
                        }
                    }
                }

                // 4. ENVOI DES PLATEFORMES AU MOBILE
                $formatted[] = [
                    'id' => $game['id'],
                    'name' => $game['name'],
                    'released' => $date,
                    'background_image' => $img,
                    'platforms' => $platforms,
                    'metacritic' => isset($game['total_rating']) ? round($game['total_rating']) : null,
                    'genres' => $genres
                ];
            }
        }

        $this->apiResponse(true, 'Recherche IGDB terminée.', ['data' => $formatted]);
    }

    // --- SAUVEGARDER/AJOUTER UN JEU (CORRIGÉ) ---
    public function apiSaveGame($userId)
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // L'ID de l'API (IGDB) est désormais LA source de vérité obligatoire
        if (empty($input['rawg_id']) || empty($input['status'])) {
            $this->apiResponse(false, 'L\'identifiant du jeu (ID IGDB) et le statut sont obligatoires.');
        }

        $gameData = [
            'game_id' => '',
            'rawg_id' => $input['rawg_id'],
            'title' => $input['title'] ?? 'Titre inconnu',
            'status' => $input['status'],
            'format' => $input['format'] ?? 'physical',
            'platform' => $input['platform'] ?? 'PC',
            'platform_custom' => $input['platform_custom'] ?? '',
            'user_rating' => $input['user_rating'] ?? null,
            'comment' => $input['comment'] ?? '',
            'image_url_hidden' => $input['background_image'] ?? '', // L'URL qui ira dans la table catalogue
            'metacritic' => $input['metacritic'] ?? null,
            'genres' => is_array($input['genres']) ? implode(', ', $input['genres']) : ($input['genres'] ?? null),
            'release_date' => $input['released'] ?? null
        ];

        // Vérification des doublons
        $platformToCheck = ($gameData['platform'] === 'Multiplateforme' && !empty($gameData['platform_custom']))
            ? $gameData['platform_custom']
            : $gameData['platform'];

        if ($this->gameModel->checkDuplicate($userId, $gameData['rawg_id'], $gameData['title'], $platformToCheck)) {
            $this->apiResponse(false, 'Ce jeu existe déjà dans votre collection sur cette plateforme.');
        }

        // On passe `null` à la place du fichier pour l'upload d'image
        if ($this->gameModel->save($gameData, null, $userId)) {
            $this->apiResponse(true, 'Le jeu a bien été sauvegardé !');
        } else {
            $this->apiResponse(false, "Erreur lors de l'enregistrement en base de données.", [], 500);
        }
    }

    // --- METTRE À JOUR UN JEU (NOUVEAU) ---
    // --- METTRE À JOUR UN JEU (NOUVEAU) ---
    public function apiUpdateGame($userId)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $gameId = $input['id'] ?? null; // C'est l'ID de la table user_games

        if (empty($gameId)) {
            $this->apiResponse(false, 'L\'ID du jeu est obligatoire pour la mise à jour.', [], 400);
        }

        $existingGame = $this->gameModel->getOne($gameId, $userId);
        if (!$existingGame) {
            $this->apiResponse(false, 'Jeu introuvable dans votre collection.', [], 404);
        }

        // On prépare les données (fusion des nouvelles valeurs avec celles existantes)
        $gameData = [
            'game_id' => $gameId, 
            'rawg_id' => $existingGame['rawg_id'] ?? $existingGame['igdb_id'] ?? null, // Conservation de l'ID IGDB
            'title' => $input['title'] ?? $existingGame['title'],
            'status' => $input['status'] ?? $existingGame['status'],
            'platform' => $input['platform'] ?? $existingGame['platform'],
            'format' => $input['format'] ?? $existingGame['format'],
            'release_date' => $input['release_date'] ?? $existingGame['release_date'],
            'metacritic' => $input['metacritic_score'] ?? $existingGame['metacritic_score'],
            'user_rating' => $input['user_rating'] ?? $existingGame['user_rating'],
            'comment' => $input['comment'] ?? $existingGame['comment'],
            'description' => $input['description'] ?? $existingGame['description'],
            'genres' => $input['genres'] ?? $existingGame['genres'],
            'estimated_price' => $input['estimated_price'] ?? $existingGame['estimated_price'],
            'image_url_hidden' => $existingGame['image_url'] // On garde l'image de IGDB
        ];

        if (isset($input['platform_custom']) && $input['platform'] === 'Multiplateforme') {
            $gameData['platform_custom'] = $input['platform_custom'];
        }

        // --- SAUVEGARDE DU TEMPS DE JEU (PLAYTIME) ---
        if (isset($input['playtime'])) {
            require_once dirname(__DIR__) . '/models/Playtime.php';
            $playtimeModel = new Playtime($this->db);
            $existingPlaytime = $playtimeModel->getByGameId($gameId);
            $time100 = $existingPlaytime ? $existingPlaytime['time_100'] : null;
            $cleanPlaytime = str_replace(',', '.', $input['playtime']);
            $playtimeModel->save($gameId, floatval($cleanPlaytime), $time100);
        }

        // Sauvegarde principale (sans fichier image)
        if ($this->gameModel->save($gameData, null, $userId)) {
            $this->apiResponse(true, 'Le jeu a bien été mis à jour !');
        } else {
            $this->apiResponse(false, 'Erreur lors de la mise à jour en base de données.', [], 500);
        }
    }

    // --- NOUVEAU : SUPPRIMER UN JEU ---
    public function apiDeleteGame($userId)
    {
        // On récupère l'ID passé dans l'URL (ex: ?action=api_delete_game&id=5)
        $gameId = $_GET['id'] ?? null;

        if (!$gameId) {
            $this->apiResponse(false, 'L\'ID du jeu à supprimer est manquant.', [], 400);
        }

        // On utilise la méthode de suppression existante de ton modèle
        if ($this->gameModel->delete($gameId, $userId)) {
            $this->apiResponse(true, 'Le jeu a été retiré de votre collection.');
        } else {
            $this->apiResponse(false, 'Erreur lors de la suppression du jeu.', [], 500);
        }
    }

    public function apiExportJson($userId)
    {
        $games = $this->gameModel->getAll($userId);

        $exportData = array_map(function ($game) {
            unset($game['user_id']);
            unset($game['id']);
            return $game;
        }, $games);

        // Renvoie directement le JSON pur sans forcer le téléchargement au navigateur
        header('Content-Type: application/json');
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function apiImportJson($userId)
    {
        $jsonContent = file_get_contents('php://input');
        $games = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($games)) {
            $this->apiResponse(false, 'Fichier JSON invalide', [], 400);
        }

        $count = 0;
        foreach ($games as $gameData) {
            if ($this->gameModel->importEntry($gameData, $userId)) {
                $count++;
            }
        }
        $this->apiResponse(true, "$count jeux importés avec succès !", ['count' => $count]);
    }

    // ==========================================
    //           API (MOBILE)
    // ==========================================

    private function checkCsrf()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Erreur de sécurité : Token CSRF invalide ou manquant.");
        }
    }

    public function index()
{
    if (!isset($_SESSION['user_id'])) {
        $view = dirname(__DIR__) . '/views/auth.php';
        require dirname(__DIR__) . '/views/layout.php';
        return;
    }

    $games = $this->gameModel->getAll($_SESSION['user_id']);

    $totalGames = count($games);
    $playingCount = count(array_filter($games, fn($g) => $g['status'] === 'playing'));
    $finishedCount = count(array_filter($games, fn($g) => in_array($g['status'], ['finished', 'completed'])));

    $view = dirname(__DIR__) . '/views/dashboard.php';
    require dirname(__DIR__) . '/views/layout.php';
    }


    public function apiSearch()
    {
        // 1. Sécurité : Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Non autorisé']);
            exit();
        }

        // 2. Récupération du terme de recherche
        $term = isset($_GET['q']) ? trim($_GET['q']) : '';

        // 3. Appel au modèle
        if ($term === '') {
            // Si la recherche est vide, on renvoie tout (ou une liste vide selon votre préférence)
            $games = $this->gameModel->getAll($_SESSION['user_id']);
        } else {
            $games = $this->gameModel->searchGames($_SESSION['user_id'], $term);
        }

        // 4. Renvoi de la réponse en JSON
        header('Content-Type: application/json');
        echo json_encode($games);
        exit();
    }

    // --- Stats ---
    public function stats()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }
        $games = $this->gameModel->getAll($_SESSION['user_id']);
        $view = dirname(__DIR__) . '/views/stats.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // Affichage de la page Wishlist
    public function wishlist()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }
        // On récupère uniquement les jeux en wishlist
        $games = $this->gameModel->getWishlist($_SESSION['user_id']);

        // On charge une nouvelle vue spécifique
        $view = dirname(__DIR__) . '/views/wishlist.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // Action "Acquérir"
    public function acquire()
    {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;

        if ($this->gameModel->acquireGame($_GET['id'], $_SESSION['user_id'])) {
            $_SESSION['toast'] = ['msg' => "Jeu ajouté à votre collection !", 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['msg' => "Erreur lors de l'acquisition.", 'type' => 'danger'];
        }

        // On reste sur la wishlist pour voir le jeu disparaître (ou on redirige vers l'accueil si on préfère)
        header("Location: /wishlist");
        exit();
    }

    // --- Save (Ajout/Modif) ---
    public function saveGame()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // L'utilisateur doit être connecté
            if (!isset($_SESSION['user_id'])) {
                header("Location: /");
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            
            // On récupère directement les données du formulaire
            $gameData = [
                'game_id' => $_POST['game_id'] ?? '', // Rempli si c'est une mise à jour
                'rawg_id' => $_POST['rawg_id'] ?? null, // C'est l'ID IGDB
                'title' => $_POST['title'] ?? 'Titre inconnu',
                'status' => $_POST['status'] ?? 'not_started',
                'format' => $_POST['format'] ?? 'digital',
                'platform' => $_POST['platform'] ?? 'PC',
                'platform_custom' => $_POST['platform_custom'] ?? '',
                'user_rating' => $_POST['user_rating'] ?? null,
                'comment' => $_POST['comment'] ?? '',
                'image_url_hidden' => $_POST['image_url_hidden'] ?? '', // L'URL IGDB
                'metacritic' => $_POST['metacritic'] ?? null,
                'genres' => $_POST['genres'] ?? null,
                'release_date' => $_POST['release_date'] ?? null,
                'description' => $_POST['description'] ?? '',
                'estimated_price' => $_POST['estimated_price'] ?? null
            ];

            // On appelle le modèle en passant `null` pour le paramètre $file (plus d'upload)
            if ($this->gameModel->save($gameData, null, $userId)) {
                $_SESSION['toast'] = ['msg' => "Le jeu a été sauvegardé !", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de l'enregistrement.", 'type' => 'danger'];
            }
            
            // Redirection vers le dashboard après traitement
            header("Location: /dashboard");
            exit;
        }
    }

    // --- DELETE ---
    public function delete()
    {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;
        // Note: Pour sécuriser la suppression (GET), il faudrait aussi passer le token dans l'URL
        if ($this->gameModel->delete($_GET['id'], $_SESSION['user_id'])) {
            $_SESSION['toast'] = ['msg' => "Supprimé.", 'type' => 'warning'];
        }
        header("Location: /");
        exit();
    }

    // --- EXPORT JSON ---
    public function export()
    {
        if (!isset($_SESSION['user_id'])) return;

        $games = $this->gameModel->getAll($_SESSION['user_id']);

        $exportData = array_map(function ($game) {
            unset($game['user_id']);
            unset($game['id']);
            return $game;
        }, $games);

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'game_collection_' . date('Y-m-d') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $json;
        exit();
    }

    // --- IGDB API HELPER ---
    private function getIgdbToken()
    {
        if (isset($_SESSION['igdb_token']) && isset($_SESSION['igdb_expiry']) && time() < $_SESSION['igdb_expiry']) {
            return $_SESSION['igdb_token'];
        }

        // REMPLACEZ PAR VOS CLÉS SI ELLES NE SONT PAS DANS LE .ENV
        $clientId = $_ENV['IGDB_CLIENT_ID'] ?? 'VOTRE_CLIENT_ID';
        $clientSecret = $_ENV['IGDB_CLIENT_SECRET'] ?? 'VOTRE_CLIENT_SECRET';

        $url = 'https://id.twitch.tv/oauth2/token';
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($result, true);

        if (isset($json['access_token'])) {
            $_SESSION['igdb_token'] = $json['access_token'];
            $_SESSION['igdb_expiry'] = time() + $json['expires_in'];
            return $json['access_token'];
        }

        return null;
    }

    private function callIgdb($endpoint, $body)
    {
        $token = $this->getIgdbToken();
        if (!$token) return null;

        $clientId = $_ENV['IGDB_CLIENT_ID'] ?? 'VOTRE_CLIENT_ID';

        $ch = curl_init('https://api.igdb.com/v4/' . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Client-ID: ' . $clientId,
            'Authorization: Bearer ' . $token,
            'Content-Type: text/plain'
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function searchIgdb()
    {
        if (!isset($_SESSION['user_id'])) exit(json_encode([]));

        $query = isset($_GET['q']) ? $_GET['q'] : '';
        if (strlen($query) < 2) exit(json_encode([]));

        $body = 'search "' . str_replace('"', '', $query) . '"; fields name, cover.url, first_release_date; limit 10;';
        $results = $this->callIgdb('games', $body);

        $formatted = [];
        if ($results && is_array($results)) {
            foreach ($results as $game) {
                $img = isset($game['cover']['url']) ? 'https:' . str_replace('t_thumb', 't_cover_big', $game['cover']['url']) : '';
                $date = isset($game['first_release_date']) ? date('Y', $game['first_release_date']) : '';

                $formatted[] = [
                    'id' => $game['id'],
                    'name' => $game['name'],
                    'released' => $date,
                    'background_image' => $img
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $formatted]);
        exit();
    }

    public function getIgdbDetails()
    {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) exit();

        $id = intval($_GET['id']);
        $body = "fields name, cover.url, first_release_date, total_rating, summary, genres.name, platforms.name; where id = {$id};";

        $results = $this->callIgdb('games', $body);
        $data = ($results && isset($results[0])) ? $results[0] : null;

        if ($data) {
            $genres = [];
            if (isset($data['genres'])) {
                foreach ($data['genres'] as $g) $genres[] = $g['name'];
            }

            $img = isset($data['cover']['url']) ? 'https:' . str_replace('t_thumb', 't_720p', $data['cover']['url']) : '';

            $response = [
                'name' => $data['name'],
                'released' => isset($data['first_release_date']) ? date('Y-m-d', $data['first_release_date']) : '',
                'metacritic' => isset($data['total_rating']) ? round($data['total_rating']) : '',
                'background_image' => $img,
                'description_raw' => $data['summary'] ?? '',
                'genres_list' => implode(', ', $genres)
            ];

            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        exit();
    }

    // --- 1IMPORT STEAM ---
    public function steamLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit();
        }

        $siteUrl = 'http://' . $_SERVER['HTTP_HOST'];
        $returnTo = $siteUrl . '/steam_callback';

        $steamLoginUrl = 'https://steamcommunity.com/openid/login' .
            '?openid.ns=http://specs.openid.net/auth/2.0' .
            '&openid.mode=checkid_setup' .
            '&openid.return_to=' . urlencode($returnTo) .
            '&openid.realm=' . urlencode($siteUrl) .
            '&openid.identity=http://specs.openid.net/auth/2.0/identifier_select' .
            '&openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select';

        header("Location: " . $steamLoginUrl);
        exit();
    }

    public function steamCallback()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }

        if (isset($_GET['openid_mode']) && $_GET['openid_mode'] == 'id_res' && isset($_GET['openid_claimed_id'])) {
            preg_match('/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/', $_GET['openid_claimed_id'], $matches);
            $steamId = $matches[1] ?? null;

            if ($steamId) {
                // NOUVEAU : On sauvegarde définitivement le Steam ID dans la table users
                $stmt = $this->db->prepare("UPDATE users SET steam_id = :steamId WHERE id = :userId");
                $stmt->execute([':steamId' => $steamId, ':userId' => $_SESSION['user_id']]);

                $_SESSION['pending_steam_id'] = $steamId;
                header("Location: /profile?importing=steam");
                exit();
            }
        }

        $_SESSION['toast'] = ['msg' => "Erreur ou annulation de la connexion Steam.", 'type' => 'danger'];
        header("Location: /profile");
        exit();
    }

    public function apiGetSteamGames()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['pending_steam_id'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit();
        }

        $steamId = $_SESSION['pending_steam_id'];
        $apiKey = $_ENV['STEAM_API_KEY'] ?? '';

        $url = "http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$apiKey}&steamid={$steamId}&format=json&include_appinfo=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['response']['games'])) {
            echo json_encode(['success' => false, 'error' => 'Aucun jeu trouvé ou profil Steam privé.']);
            exit();
        }

        $gamesToImport = [];

        $blacklist = ['beta', 'alpha', 'server', 'dedicated server', 'test', 'test server', 'public test', 'demo', 'sdk'];

        foreach ($data['response']['games'] as $steamGame) {
            $gameName = $steamGame['name'];
            $shouldIgnore = false;

            foreach ($blacklist as $word) {

                if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $gameName)) {
                    $shouldIgnore = true;
                    break;
                }
            }

            if ($shouldIgnore) {
                continue;
            }

            if (!$this->gameModel->checkDuplicate($_SESSION['user_id'], null, $gameName, 'PC')) {
                $gamesToImport[] = $steamGame;
            }
        }

        echo json_encode(['success' => true, 'games' => $gamesToImport]);
        exit();
    }

    public function apiImportSingleSteamGame()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false]));

        // Récupération des données envoyées par JS
        $steamGame = json_decode(file_get_contents('php://input'), true);
        if (!$steamGame || !isset($steamGame['appid'])) exit(json_encode(['success' => false]));

        require_once dirname(__DIR__) . '/models/Playtime.php';
        $playtimeModel = new Playtime($this->db);

        $title = $steamGame['name'];
        $playtimeMinutes = $steamGame['playtime_forever'] ?? 0;
        $lastPlayedTimestamp = $steamGame['rtime_last_played'] ?? 0;
        $oneYearAgo = time() - (365 * 24 * 60 * 60);

        // Revérification du doublon par sécurité
        if ($this->gameModel->checkDuplicate($_SESSION['user_id'], null, $title, 'PC')) {
            echo json_encode(['success' => true]);
            exit();
        }

        $status = 'not_started';
        if ($playtimeMinutes > 0) {
            $status = 'playing';
            if ($lastPlayedTimestamp > 0 && $lastPlayedTimestamp < $oneYearAgo) {
                $status = 'dropped';
            }
        }

        $appId = $steamGame['appid'];
        $imageUrl = "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/header.jpg";

        $gameData = [
            'title' => $title,
            'platform' => 'PC',
            'format' => 'digital',
            'status' => $status,
            'release_date' => null,
            'metacritic_score' => null,
            'user_rating' => null,
            'comment' => 'Importé depuis Steam',
            'image_url' => $imageUrl,
            'description' => '',
            'genres' => '',
            'dominant_color' => 'rgb(30, 30, 30)',
            'estimated_price' => null
        ];

        if ($this->gameModel->importEntry($gameData, $_SESSION['user_id'])) {
            $newGameId = $this->db->lastInsertId();
            if ($playtimeMinutes > 0 && $newGameId) {
                $playtimeModel->save($newGameId, round($playtimeMinutes / 60, 1), null);
            }
        }
        echo json_encode(['success' => true]);
        exit();
    }

    public function steamImportComplete()
    {
        unset($_SESSION['pending_steam_id']);
        $_SESSION['toast'] = ['msg' => "Importation Steam terminée avec succès !", 'type' => 'success'];
        echo json_encode(['success' => true]);
        exit();
    }

    // --- MISE À JOUR RAPIDE DES TEMPS DE JEU ---
    public function updateSteamPlaytime()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit();
        }

        // 1. On vérifie si l'utilisateur a déjà lié son compte Steam
        $stmt = $this->db->prepare("SELECT steam_id FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || empty($user['steam_id'])) {
            $_SESSION['toast'] = ['msg' => "Veuillez d'abord lier votre compte en cliquant sur 'Importer mes jeux'.", 'type' => 'warning'];
            header("Location: /profile");
            exit();
        }

        $steamId = $user['steam_id'];
        $apiKey = $_ENV['STEAM_API_KEY'] ?? '';

        // 2. Appel à l'API Steam (Très rapide, pas d'images à télécharger)
        $url = "http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$apiKey}&steamid={$steamId}&format=json&include_appinfo=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['response']['games'])) {
            $_SESSION['toast'] = ['msg' => "Erreur de communication avec Steam.", 'type' => 'danger'];
            header("Location: /profile");
            exit();
        }

        require_once dirname(__DIR__) . '/models/Playtime.php';
        $playtimeModel = new Playtime($this->db);

        $updatedCount = 0;
        $oneYearAgo = time() - (365 * 24 * 60 * 60);

        $stmtFindGame = $this->db->prepare("SELECT id, status FROM user_games WHERE user_id = :uid AND title = :title AND platform = 'PC' LIMIT 1");
        $stmtUpdateStatus = $this->db->prepare("UPDATE user_games SET status = :status WHERE id = :id");

        foreach ($data['response']['games'] as $steamGame) {
            $playtimeMinutes = $steamGame['playtime_forever'] ?? 0;
            if ($playtimeMinutes == 0) continue;

            // On cherche le jeu dans la bibliothèque locale de l'utilisateur
            $stmtFindGame->execute([':uid' => $_SESSION['user_id'], ':title' => $steamGame['name']]);
            $dbGame = $stmtFindGame->fetch();

            if ($dbGame) {
                // Mise à jour du temps de jeu
                $hours = round($playtimeMinutes / 60, 1);
                $playtimeModel->save($dbGame['id'], $hours, null);
                $updatedCount++;

                // Ajustement du statut si nécessaire (Non commencé -> En cours, ou Abandonné)
                $newStatus = $dbGame['status'];
                $lastPlayed = $steamGame['rtime_last_played'] ?? 0;

                if ($dbGame['status'] === 'not_started') {
                    $newStatus = 'playing';
                }
                if ($lastPlayed > 0 && $lastPlayed < $oneYearAgo && $newStatus === 'playing') {
                    $newStatus = 'dropped';
                }

                if ($newStatus !== $dbGame['status']) {
                    $stmtUpdateStatus->execute([':status' => $newStatus, ':id' => $dbGame['id']]);
                }
            }
        }

        $_SESSION['toast'] = ['msg' => "Temps de jeu actualisés pour $updatedCount jeux !", 'type' => 'success'];
        header("Location: /profile");
        exit();
    }

    // --- SYNCHRONISATION PSN ---
    public function executePsnSync($userId, $psnId)
    {
        $vpsIp = "http://87.106.8.127:3000";
        $url = $vpsIp . "/api/psn/trophies/" . urlencode($psnId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // On augmente le timeout pour les gros volumes
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return false;

        $data = json_decode($response, true);
    if (!$data || !$data['success']) return false;

    require_once dirname(__DIR__) . '/models/Trophy.php';
    $trophyModel = new Trophy($this->db);

    $stats = ['games' => 0, 'trophies' => 0];

    foreach ($data['games'] as $psnGame) {
        $localGame = $this->gameModel->findPlayStationGameByTitle($userId, $psnGame['titleName']);
        
        if ($localGame) {
            if (isset($localGame['status']) && $localGame['status'] === 'completed') continue;

            $gameId = $localGame['id'];
            $stats['games']++;
            
            if (isset($psnGame['earnedTrophies']) && is_array($psnGame['earnedTrophies'])) {
                foreach ($psnGame['earnedTrophies'] as $trophy) {
                    $title = $trophy['trophyName'] ?? 'Trophée Inconnu';
                    $type = strtolower($trophy['trophyType'] ?? 'bronze'); 
                    $isObtained = !empty($trophy['earned']);
                    
                    $rawDate = $trophy['earnedDateTime'] ?? $trophy['earnedDate'] ?? $trophy['date'] ?? null;
                    $earnedAt = null;

                    if ($rawDate && is_string($rawDate)) {
                        $parsed = strtotime($rawDate);
                        if ($parsed) {
                            $earnedAt = date('Y-m-d H:i:s', $parsed);
                        }
                    }

                    if ($isObtained && !isset($GLOBALS['debug_trophy_logged'])) {
                        file_put_contents(dirname(__DIR__) . '/api/cron_psn.log', "DEBUG EARNED TROPHY: " . json_encode($trophy) . "\n", FILE_APPEND);
                        $GLOBALS['debug_trophy_logged'] = true; // Empêche de spammer le fichier log
                    }

                    $trophyModel->syncPsnTrophy($gameId, $title, $type, $isObtained, $earnedAt);
                    $stats['trophies']++;
                }
            }
        }
    }
    return $stats;
    }

    public function apiPsnSync()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Non autorisé']));

        $userId = $_SESSION['user_id'];

        // Récupération du PSN ID envoyé par le bouton de synchro
        $data = json_decode(file_get_contents('php://input'), true);
        $psnId = trim($data['psn_id'] ?? '');

        if (empty($psnId)) {
            exit(json_encode(['success' => false, 'message' => 'Veuillez saisir un ID PSN.']));
        }

        // 1. Sauvegarder l'ID PSN immédiatement
        $this->userModel->setPsnId($userId, $psnId);

        // 2. Vérifier la limite d'une heure
        $user = $this->userModel->getById($userId);
        if ($user['last_psn_sync']) {
            $lastSync = new DateTime($user['last_psn_sync']);
            $now = new DateTime();
            $diff = $now->getTimestamp() - $lastSync->getTimestamp();

            if ($diff < 3600) {
                $remaining = 60 - floor($diff / 60);
                exit(json_encode(['success' => false, 'message' => "Veuillez attendre $remaining minutes avant la prochaine synchronisation."]));
            }
        }

        // 3. Lancer la synchronisation (méthode executePsnSync créée précédemment)
        $result = $this->executePsnSync($userId, $psnId);

        if ($result) {
            $this->userModel->updateLastPsnSync($userId);
            echo json_encode([
                'success' => true,
                'games_synced' => $result['games'],
                'trophies_processed' => $result['trophies']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la synchronisation.']);
        }
        exit();
    }



    // --- IMPORT JSON ---
    public function import()
    {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
            $this->checkCsrf();

            $file = $_FILES['json_file'];

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($ext !== 'json') {
                $_SESSION['toast'] = ['msg' => "Le fichier doit être un .json", 'type' => 'danger'];
                header("Location: /profile");
                exit();
            }

            $jsonContent = file_get_contents($file['tmp_name']);
            $games = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($games)) {
                $_SESSION['toast'] = ['msg' => "Fichier JSON invalide.", 'type' => 'danger'];
                header("Location: /profile");
                exit();
            }

            $count = 0;
            foreach ($games as $gameData) {
                if ($this->gameModel->importEntry($gameData, $_SESSION['user_id'])) {
                    $count++;
                }
            }

            $_SESSION['toast'] = ['msg' => "$count jeux importés avec succès !", 'type' => 'success'];
        }
        header("Location: /profile");
        exit();
    }

    // --- WHEEL ---
    public function apiRouletteGames()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Non autorisé']);
            exit();
        }

        // On appelle la fonction modifiée (sans limite)
        $games = $this->gameModel->getGamesByStatusRandom($_SESSION['user_id'], 'not_started');

        header('Content-Type: application/json');
        echo json_encode(['games' => $games]);
        exit();
    }

    public function apiStartGame()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            exit();
        }

        // Récupération des données JSON envoyées par fetch()
        $data = json_decode(file_get_contents('php://input'), true);
        $gameId = $data['game_id'] ?? null;

        if ($gameId && $this->gameModel->updateGameStatus($gameId, $_SESSION['user_id'], 'playing')) {
            // On prépare un message de succès pour le rechargement de la page
            $_SESSION['toast'] = ['msg' => "C'est parti ! Le jeu est maintenant en cours.", 'type' => 'success'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
        }
        exit();
    }

    // --- GESTION DES PRÊTS ---
    public function loaned()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /");
            exit();
        }
        $games = $this->gameModel->getLoanedGames($_SESSION['user_id']);

        // Nous allons utiliser une nouvelle vue spécifique pour ça
        $view = dirname(__DIR__) . '/views/loaned.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // Traiter le formulaire de prêt
    public function loan()
    {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $gameId = $_POST['game_id'] ?? null;
            $loanedTo = $_POST['loaned_to'] ?? '';
            $loanedDate = $_POST['loaned_date'] ?? date('Y-m-d');

            if ($gameId && !empty($loanedTo)) {
                if ($this->gameModel->loanGame($gameId, $_SESSION['user_id'], $loanedTo, $loanedDate)) {
                    $_SESSION['toast'] = ['msg' => "Le jeu a été marqué comme prêté !", 'type' => 'success'];
                } else {
                    $_SESSION['toast'] = ['msg' => "Erreur lors de l'enregistrement du prêt.", 'type' => 'danger'];
                }
            }
        }
        header("Location: /");
        exit();
    }

    // Marquer un jeu comme "Retourné"
    public function returnGame()
    {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;

        if ($this->gameModel->returnLoanedGame($_GET['id'], $_SESSION['user_id'])) {
            $_SESSION['toast'] = ['msg' => "Le jeu est de retour dans votre collection !", 'type' => 'success'];
        }

        // On redirige vers la liste des jeux prêtés
        header("Location: /loaned");
        exit();
    }


    // --- VUE PARTAGÉE (PROFIL PUBLIC) ---
    public function share()
    {
        $targetUser = null;

        // 1. Priorité à l'ID (ce que votre lien de partage utilise)
        if (isset($_GET['id'])) {
            $targetUser = $this->userModel->getById($_GET['id']);
        }
        // 2. Fallback sur le pseudo (si vous voulez des liens du type ?user=John)
        elseif (isset($_GET['user'])) {
            $targetUser = $this->userModel->getIdByUsername($_GET['user']);
        }

        // Si aucun utilisateur n'est trouvé ou spécifié, retour à l'accueil
        if (!$targetUser) {
            header("Location: /");
            exit();
        }

        // Récupération des jeux de cet utilisateur
        $games = $this->gameModel->getAll($targetUser['id']);

        // On définit $owner pour que la vue puisse afficher les infos du profil
        $owner = $targetUser;

        // Note: $db est nécessaire si vous utilisez User dans la vue pour suivre/ne plus suivre
        $db = $this->db;

        $view = dirname(__DIR__) . '/views/public_collection.php';
        require dirname(__DIR__) . '/views/layout.php';
    }
}

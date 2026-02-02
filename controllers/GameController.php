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

    // --- Sécurité CSRF ---
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
        public function save()
        {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf(); 

            // 1. Définir si c'est un nouveau jeu
            $isNewGame = empty($_POST['game_id']);

            // 2. Si c'est un nouvel ajout, on vérifie les doublons
            if ($isNewGame) {
                $rawgId = $_POST['rawg_id'] ?? null;
                $title = $_POST['title'] ?? '';

                $platform = ($_POST['platform'] === 'Multiplateforme' && !empty($_POST['platform_custom']))
                    ? $_POST['platform_custom']
                    : $_POST['platform'];

                if ($this->gameModel->checkDuplicate($_SESSION['user_id'], $rawgId, $title, $platform)) {
                    $_SESSION['toast'] = ['msg' => "Ce jeu existe déjà dans votre collection !", 'type' => 'warning'];
                    header("Location: /"); 
                    exit();
                }
            }

            // 3. Sauvegarde normale
            if ($this->gameModel->save($_POST, $_FILES, $_SESSION['user_id'])) {
                $_SESSION['toast'] = ['msg' => "Enregistré !", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de l'enregistrement.", 'type' => 'danger'];
            }

            // --- MODIFICATION ICI ---
            // Si c'est un nouvel ajout, on redirige selon le statut
            if ($isNewGame) {
                // Si le statut envoyé est 'wishlist', on redirige vers /wishlist, sinon vers l'accueil
                $redirectTo = (isset($_POST['status']) && $_POST['status'] === 'wishlist') ? "/wishlist" : "/?open_add=1";
                header("Location: " . $redirectTo);
                exit();
            }
        }
        header("Location: /");
        exit();
    }

    // --- Delete ---
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

    // --- VUE PARTAGÉE (PROFIL PUBLIC) ---
    public function share()
    {
        $username = $_GET['user'] ?? null;
        if (!$username) {
            header("Location: /");
            exit();
        }

        $targetUser = $this->userModel->getIdByUsername($username);

        if (!$targetUser) {
            echo "Utilisateur introuvable.";
            exit();
        }

        $games = $this->gameModel->getAll($targetUser['id']);
        $owner = $targetUser;

        $db = $this->db;

        $view = dirname(__DIR__) . '/views/public_collection.php';
        require dirname(__DIR__) . '/views/layout.php';
    }
}

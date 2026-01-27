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
            header("Location: index.php");
            exit();
        }
        $games = $this->gameModel->getAll($_SESSION['user_id']);
        $view = dirname(__DIR__) . '/views/stats.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // --- Save (Ajout/Modif) ---
    public function save()
    {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf(); // <--- VERIFICATION

            // 1. Définir si c'est un nouveau jeu
            $isNewGame = empty($_POST['game_id']);

            // 2. Si c'est un nouvel ajout, on vérifie les doublons
            if ($isNewGame) {
                // Récupération des données pour la vérification
                $rawgId = $_POST['rawg_id'] ?? null;
                $title = $_POST['title'] ?? '';

                // Gérer le cas particulier du champ "Autre" pour la plateforme
                $platform = ($_POST['platform'] === 'Multiplateforme' && !empty($_POST['platform_custom']))
                    ? $_POST['platform_custom']
                    : $_POST['platform'];

                // Appel au modèle pour vérifier si le jeu existe déjà
                // (Vérifie d'abord via rawg_id, sinon via Titre + Plateforme)
                if ($this->gameModel->checkDuplicate($_SESSION['user_id'], $rawgId, $title, $platform)) {
                    $_SESSION['toast'] = ['msg' => "Ce jeu existe déjà dans votre collection !", 'type' => 'warning'];
                    header("Location: index.php"); // On redirige sans sauvegarder
                    exit();
                }
            }

            // 3. Sauvegarde normale si tout est bon
            if ($this->gameModel->save($_POST, $_FILES, $_SESSION['user_id'])) {
                $_SESSION['toast'] = ['msg' => "Enregistré !", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de l'enregistrement.", 'type' => 'danger'];
            }

            // Si c'est un nouvel ajout, on redirige avec le paramètre pour rouvrir l'accordéon
            if ($isNewGame) {
                header("Location: index.php?open_add=1");
                exit();
            }
        }
        header("Location: index.php");
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
        header("Location: index.php");
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

    // --- IMPORT JSON ---
    public function import()
    {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
            $this->checkCsrf(); // <--- VERIFICATION

            $file = $_FILES['json_file'];

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($ext !== 'json') {
                $_SESSION['toast'] = ['msg' => "Le fichier doit être un .json", 'type' => 'danger'];
                header("Location: index.php?action=profile");
                exit();
            }

            $jsonContent = file_get_contents($file['tmp_name']);
            $games = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($games)) {
                $_SESSION['toast'] = ['msg' => "Fichier JSON invalide.", 'type' => 'danger'];
                header("Location: index.php?action=profile");
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
        header("Location: index.php?action=profile");
        exit();
    }

    // --- VUE PARTAGÉE (PROFIL PUBLIC) ---
    public function share()
    {
        $username = $_GET['user'] ?? null;
        if (!$username) {
            header("Location: index.php");
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

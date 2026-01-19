<?php
require_once dirname(__DIR__) . '/models/Game.php';
require_once dirname(__DIR__) . '/models/User.php'; // Nécessaire pour vérifier le profil public

class GameController {
    private $gameModel;
    private $userModel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->gameModel = new Game($db);
        $this->userModel = new User($db);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $view = dirname(__DIR__) . '/views/auth.php';
            require dirname(__DIR__) . '/views/layout.php';
            return;
        }
        $games = $this->gameModel->getAll($_SESSION['user_id']);
        $view = dirname(__DIR__) . '/views/dashboard.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // --- Stats ---
    public function stats() {
        if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
        $games = $this->gameModel->getAll($_SESSION['user_id']);
        $view = dirname(__DIR__) . '/views/stats.php';
        require dirname(__DIR__) . '/views/layout.php';
    }

    // --- Save (Ajout/Modif) ---
    public function save() {
        if (!isset($_SESSION['user_id'])) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->gameModel->save($_POST, $_FILES, $_SESSION['user_id'])) {
                $_SESSION['toast'] = ['msg' => "Enregistré !", 'type' => 'success'];
            } else {
                $_SESSION['toast'] = ['msg' => "Erreur lors de l'enregistrement.", 'type' => 'danger'];
            }
        }
        header("Location: index.php"); exit();
    }

    // --- Delete ---
    public function delete() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) return;
        if ($this->gameModel->delete($_GET['id'], $_SESSION['user_id'])) {
            $_SESSION['toast'] = ['msg' => "Supprimé.", 'type' => 'warning'];
        }
        header("Location: index.php"); exit();
    }
    
    // --- EXPORT JSON ---
    public function export() {
        if (!isset($_SESSION['user_id'])) return;

        $games = $this->gameModel->getAll($_SESSION['user_id']);
        
        // Nettoyage des données pour l'export (retrait des IDs user spécifiques)
        $exportData = array_map(function($game) {
            unset($game['user_id']); // On retire l'ID user pour l'anonymat
            unset($game['id']); // On retire l'ID du jeu pour éviter les conflits à l'import
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
    public function import() {
        if (!isset($_SESSION['user_id'])) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
            $file = $_FILES['json_file'];
            
            // Vérification extension
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
                // On utilise une méthode dédiée pour insérer sans passer par la validation $_POST classique
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
    public function share() {
            $username = $_GET['user'] ?? null;
            if (!$username) {
                header("Location: index.php");
                exit();
            }

            // Récupérer les infos de l'utilisateur cible
            $targetUser = $this->userModel->getIdByUsername($username);
            
            if (!$targetUser) {
                echo "Utilisateur introuvable.";
                exit();
            }

            // Récupérer SES jeux
            $games = $this->gameModel->getAll($targetUser['id']);
            $owner = $targetUser;

            // <--- 3. AJOUT CRUCIAL : On rend $db accessible pour la vue
            $db = $this->db; 

            // Charger la vue
            $view = dirname(__DIR__) . '/views/public_collection.php';
            require dirname(__DIR__) . '/views/layout.php';
        }
}
?>
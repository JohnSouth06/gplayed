<?php
class Playtime {
    private $conn;
    private $table = 'playtime';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer les temps pour un jeu spécifique
    public function getByGameId($gameId) {
        $query = "SELECT * FROM " . $this->table . " WHERE game_id = :game_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Ajouter ou Mettre à jour les temps
    public function save($gameId, $main, $full) {
        // Vérifier si une entrée existe déjà
        $existing = $this->getByGameId($gameId);

        if ($existing) {
            $query = "UPDATE " . $this->table . " SET time_main = :main, time_100 = :full WHERE game_id = :game_id";
        } else {
            $query = "INSERT INTO " . $this->table . " (game_id, time_main, time_100) VALUES (:game_id, :main, :full)";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->bindParam(':main', $main);
        $stmt->bindParam(':full', $full);

        return $stmt->execute();
    }

    // Suppression (géré par ON DELETE CASCADE en SQL, mais utile au cas où)
    public function deleteByGameId($gameId) {
        $query = "DELETE FROM " . $this->table . " WHERE game_id = :game_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        return $stmt->execute();
    }
}
?>
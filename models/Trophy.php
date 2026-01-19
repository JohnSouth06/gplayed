<?php
class Trophy {
    private $conn;
    private $table = 'game_trophies';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllByGame($gameId) {
        // Tri par type (Platine en premier) puis par statut
        $query = "SELECT * FROM " . $this->table . " WHERE game_id = :game_id ORDER BY 
                  CASE type 
                    WHEN 'platinum' THEN 1 
                    WHEN 'gold' THEN 2 
                    WHEN 'silver' THEN 3 
                    WHEN 'bronze' THEN 4 
                  END, is_obtained DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function add($data) {
        $query = "INSERT INTO " . $this->table . " (game_id, title, type) VALUES (:game_id, :title, :type)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $data['game_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':type', $data['type']);
        return $stmt->execute();
    }

    public function toggle($id) {
        $query = "UPDATE " . $this->table . " SET is_obtained = NOT is_obtained WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Stats pour la barre de progression
    public function getProgress($gameId) {
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_obtained = 1 THEN 1 ELSE 0 END) as obtained
            FROM " . $this->table . " WHERE game_id = :game_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
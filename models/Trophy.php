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

    public function syncPsnTrophy($gameId, $title, $type, $isObtained) {
        // 1. On vérifie si le trophée existe déjà pour ce jeu
        $query = "SELECT id, is_obtained FROM " . $this->table . " WHERE game_id = :game_id AND title = :title LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':game_id' => $gameId, ':title' => $title]);
        $existing = $stmt->fetch();

        if ($existing) {
            // 2. S'il existe et que le statut a changé, on le met à jour
            if ($existing['is_obtained'] != $isObtained) {
                $update = "UPDATE " . $this->table . " SET is_obtained = :is_obtained WHERE id = :id";
                $updStmt = $this->conn->prepare($update);
                $updStmt->execute([':is_obtained' => $isObtained ? 1 : 0, ':id' => $existing['id']]);
            }
        } else {
            // 3. S'il n'existe pas, on l'ajoute
            $insert = "INSERT INTO " . $this->table . " (game_id, title, type, is_obtained) VALUES (:game_id, :title, :type, :is_obtained)";
            $insStmt = $this->conn->prepare($insert);
            $insStmt->execute([
                ':game_id' => $gameId,
                ':title' => $title,
                ':type' => $type,
                ':is_obtained' => $isObtained ? 1 : 0
            ]);
        }
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

    public function getSummaryByGame($gameId) {
        $query = "SELECT 
                    SUM(CASE WHEN type = 'platinum' AND is_obtained = 1 THEN 1 ELSE 0 END) as plat,
                    SUM(CASE WHEN type = 'gold' AND is_obtained = 1 THEN 1 ELSE 0 END) as gold,
                    SUM(CASE WHEN type = 'silver' AND is_obtained = 1 THEN 1 ELSE 0 END) as silver,
                    SUM(CASE WHEN type = 'bronze' AND is_obtained = 1 THEN 1 ELSE 0 END) as bronze,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_obtained = 1 THEN 1 ELSE 0 END) as obtained
                  FROM " . $this->table . " WHERE game_id = :game_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucun trophée n'existe pour ce jeu, total vaudra 0
        if (!$result || $result['total'] == 0) return null;
        return $result;
    }
}
?>
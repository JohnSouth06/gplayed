<?php
class Trophy
{
    private $conn;
    private $table = 'game_trophies';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAllByGame($gameId)
    {
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

    public function add($data)
    {
        $query = "INSERT INTO " . $this->table . " (game_id, title, type) VALUES (:game_id, :title, :type)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $data['game_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':type', $data['type']);
        return $stmt->execute();
    }

    public function toggle($id)
    {
        $query = "UPDATE " . $this->table . " SET is_obtained = NOT is_obtained WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getRecentTrophiesByUser($userId)
    {
        $sql = "SELECT t.id, t.title as trophy_name, t.type as trophy_type, t.earned_at as earned_date,
                   g.title as game_title, g.image_url as game_image
            FROM " . $this->table . " t
            JOIN games g ON t.game_id = g.id
            WHERE g.user_id = :uid AND t.is_obtained = 1
            ORDER BY t.earned_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncPsnTrophy($gameId, $title, $type, $isObtained, $earnedAt = null)
    {
        $query = "SELECT id, is_obtained, earned_at FROM " . $this->table . " WHERE game_id = :game_id AND title = :title LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':game_id' => $gameId, ':title' => $title]);
        $existing = $stmt->fetch();

        if ($existing) {
            // NOUVEAU : On gère le cas où la BDD contient "0000-00-00 00:00:00" (que PHP ne voit pas comme vide)
            $isDateEmpty = empty($existing['earned_at']) || strpos($existing['earned_at'], '0000') !== false;

            // Mise à jour si le statut a changé, OU si la date était vide en base et qu'on a enfin une date valide
            if ($existing['is_obtained'] != $isObtained || ($isObtained && $isDateEmpty && $earnedAt !== null)) {
                $update = "UPDATE " . $this->table . " SET is_obtained = :is_obtained, earned_at = :earned_at WHERE id = :id";
                $updStmt = $this->conn->prepare($update);
                $updStmt->execute([
                    ':is_obtained' => $isObtained ? 1 : 0,
                    ':earned_at' => $earnedAt,
                    ':id' => $existing['id']
                ]);
            }
        } else {
            $insert = "INSERT INTO " . $this->table . " (game_id, title, type, is_obtained, earned_at) VALUES (:game_id, :title, :type, :is_obtained, :earned_at)";
            $insStmt = $this->conn->prepare($insert);
            $insStmt->execute([
                ':game_id' => $gameId,
                ':title' => $title,
                ':type' => $type,
                ':is_obtained' => $isObtained ? 1 : 0,
                ':earned_at' => $earnedAt
            ]);
        }
    }

    // Stats pour la barre de progression
    public function getProgress($gameId)
    {
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_obtained = 1 THEN 1 ELSE 0 END) as obtained
            FROM " . $this->table . " WHERE game_id = :game_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getSummaryByGame($gameId)
    {
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

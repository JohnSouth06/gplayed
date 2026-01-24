<?php
class Comment {
    private $conn;
    private $table = 'game_comments';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function add($userId, $gameId, $content) {
        $query = "INSERT INTO " . $this->table . " (user_id, game_id, content) VALUES (:uid, :gid, :content)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':gid', $gameId);
        $stmt->bindParam(':content', $content);
        return $stmt->execute();
    }

    public function getByGame($gameId) {
        $query = "SELECT c.*, u.username, u.avatar_url 
                  FROM " . $this->table . " c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.game_id = :gid
                  ORDER BY c.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':gid', $gameId);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
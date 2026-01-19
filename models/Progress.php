<?php
class Progress {
    private $conn;
    private $table = 'game_progress';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer toutes les entrées de journal pour un utilisateur (via ses jeux)
    public function getAllByUser($userId) {
        $query = "SELECT gp.*, g.title as game_title, g.image_url as game_image 
                  FROM " . $this->table . " gp
                  JOIN games g ON gp.game_id = g.id
                  WHERE g.user_id = :uid
                  ORDER BY gp.log_date DESC, gp.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Ajouter une entrée
    public function add($data) {
        $query = "INSERT INTO " . $this->table . " (game_id, log_date, duration_minutes, progress_value, notes) 
                  VALUES (:game_id, :log_date, :duration, :progress, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage et binding
        $duration = (int)$data['duration_hours'] * 60 + (int)$data['duration_minutes'];
        
        $stmt->bindParam(':game_id', $data['game_id']);
        $stmt->bindParam(':log_date', $data['log_date']);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':progress', $data['progress_value']);
        $stmt->bindParam(':notes', $data['notes']);

        return $stmt->execute();
    }

    // Supprimer une entrée
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
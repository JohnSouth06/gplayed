<?php
class Activity {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getFeed($userId) {
        // Cette requête récupère :
        // 1. Les jeux ajoutés par les gens qu'on suit
        // 2. Les progressions enregistrées par les gens qu'on suit
        // Et les trie par date la plus récente.
        
        $query = "
            SELECT 
                'new_game' as type,
                g.id as ref_id,
                g.title as title,
                g.image_url as image,
                g.user_rating as extra_info,
                u.username,
                u.avatar_url,
                g.created_at as time_posted
            FROM games g
            JOIN user_follows uf ON g.user_id = uf.followed_id
            JOIN users u ON g.user_id = u.id
            WHERE uf.follower_id = :uid

            UNION ALL

            SELECT 
                'progress' as type,
                gp.game_id as ref_id,
                g.title as title,
                g.image_url as image,
                gp.progress_value as extra_info,
                u.username,
                u.avatar_url,
                gp.created_at as time_posted
            FROM game_progress gp
            JOIN games g ON gp.game_id = g.id
            JOIN user_follows uf ON g.user_id = uf.followed_id
            JOIN users u ON g.user_id = u.id
            WHERE uf.follower_id = :uid

            ORDER BY time_posted DESC
            LIMIT 50
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
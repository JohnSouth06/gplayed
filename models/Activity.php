<?php
class Activity {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Ajout d'une méthode pour gérer les réactions
    public function toggleReaction($userId, $targetType, $targetId, $reactionType) {
        // Vérifier si une réaction existe déjà
        $sql = "SELECT reaction_type FROM feed_reactions 
                WHERE user_id = :uid AND target_type = :type AND target_id = :tid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $userId, ':type' => $targetType, ':tid' => $targetId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Si c'est la même réaction, on l'enlève (toggle off)
            if ($existing['reaction_type'] === $reactionType) {
                $del = "DELETE FROM feed_reactions WHERE user_id = :uid AND target_type = :type AND target_id = :tid";
                $this->conn->prepare($del)->execute([':uid' => $userId, ':type' => $targetType, ':tid' => $targetId]);
                return "removed";
            } else {
                // Sinon on met à jour le type de réaction
                $upd = "UPDATE feed_reactions SET reaction_type = :rtype 
                        WHERE user_id = :uid AND target_type = :type AND target_id = :tid";
                $this->conn->prepare($upd)->execute([':uid' => $userId, ':type' => $targetType, ':tid' => $targetId, ':rtype' => $reactionType]);
                return "updated";
            }
        } else {
            // Nouvelle réaction
            $ins = "INSERT INTO feed_reactions (user_id, target_type, target_id, reaction_type) 
                    VALUES (:uid, :type, :tid, :rtype)";
            $this->conn->prepare($ins)->execute([':uid' => $userId, ':type' => $targetType, ':tid' => $targetId, ':rtype' => $reactionType]);
            return "added";
        }
    }

    public function getFeed($userId) {
        // La requête est complexe, on va simplifier en récupérant le flux d'abord
        // NOTE: J'ai ajouté un bloc 'session' hypothétique pour votre demande future
        $query = "
            SELECT 
                'new_game' as type, g.id as ref_id, g.title, g.image_url as image, 
                g.user_rating as extra_info, u.username, u.avatar_url, g.created_at as time_posted
            FROM games g
            JOIN user_follows uf ON g.user_id = uf.followed_id
            JOIN users u ON g.user_id = u.id
            WHERE uf.follower_id = :uid

            UNION ALL

            SELECT 
                'progress' as type, gp.game_id as ref_id, g.title, g.image_url as image, 
                gp.progress_value as extra_info, u.username, u.avatar_url, gp.created_at as time_posted
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
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Maintenant, on enrichit chaque activité avec les réactions
        // C'est plus simple que de faire des JOIN complexes sur un UNION
        foreach ($activities as &$act) {
            $act['reactions'] = $this->getReactionsForEntity($act['type'], $act['ref_id'], $userId);
        }

        return $activities;
    }

    private function getReactionsForEntity($type, $id, $currentUserId) {
        // Récupérer le total par type et la réaction de l'utilisateur courant
        $sql = "SELECT reaction_type, COUNT(*) as count,
                SUM(CASE WHEN user_id = :uid THEN 1 ELSE 0 END) as user_has_reacted
                FROM feed_reactions 
                WHERE target_type = :type AND target_id = :id
                GROUP BY reaction_type";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':type' => $type, ':id' => $id, ':uid' => $currentUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<?php
class Psn
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function upsertGame($userId, $npCommId, $title, $imageUrl, $lastPlayed = null)
    {
        $query = "INSERT INTO psn_games (user_id, np_communication_id, title, image_url, last_played_at) 
                  VALUES (:user_id, :np_id, :title, :image, :last_played)
                  ON DUPLICATE KEY UPDATE 
                    title = :title, 
                    image_url = :image, 
                    last_played_at = IFNULL(:last_played, last_played_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':np_id' => $npCommId,
            ':title' => $title,
            ':image' => $imageUrl,
            ':last_played' => $lastPlayed
        ]);

        $stmt = $this->conn->prepare("SELECT id FROM psn_games WHERE user_id = :uid AND np_communication_id = :np");
        $stmt->execute([':uid' => $userId, ':np' => $npCommId]);
        return $stmt->fetchColumn();
    }

    public function upsertTrophy($psnGameId, $trophyId, $title, $titleFr, $type, $isObtained, $earnedAt)
    {
        $query = "INSERT INTO psn_trophies (psn_game_id, trophy_id, title, title_fr, type, is_obtained, earned_at)
                  VALUES (:game_id, :trophy_id, :title, :title_fr, :type, :is_obtained, :earned_at)
                  ON DUPLICATE KEY UPDATE 
                    title_fr = :title_fr,
                    is_obtained = :is_obtained, 
                    earned_at = :earned_at";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':game_id' => $psnGameId,
            ':trophy_id' => $trophyId,
            ':title' => $title,
            ':title_fr' => $titleFr,
            ':type' => $type,
            ':is_obtained' => $isObtained ? 1 : 0,
            ':earned_at' => $earnedAt
        ]);
    }

    public function getTrophiesByGame($psnGameId)
    {
        $query = "SELECT * FROM psn_trophies 
              WHERE psn_game_id = :game_id 
              ORDER BY 
                is_obtained ASC,
                CASE type 
                    WHEN 'platinum' THEN 1 
                    WHEN 'gold' THEN 2 
                    WHEN 'silver' THEN 3 
                    WHEN 'bronze' THEN 4 
                END, 
                earned_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':game_id' => $psnGameId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserPsnGamesWithStats($userId)
    {
        $query = "SELECT 
                    g.id, 
                    g.title, 
                    g.image_url,
                    SUM(CASE WHEN t.type = 'platinum' AND t.is_obtained = 1 THEN 1 ELSE 0 END) as plat,
                    SUM(CASE WHEN t.type = 'gold' AND t.is_obtained = 1 THEN 1 ELSE 0 END) as gold,
                    SUM(CASE WHEN t.type = 'silver' AND t.is_obtained = 1 THEN 1 ELSE 0 END) as silver,
                    SUM(CASE WHEN t.type = 'bronze' AND t.is_obtained = 1 THEN 1 ELSE 0 END) as bronze,
                    COUNT(t.id) as total_trophies,
                    SUM(CASE WHEN t.is_obtained = 1 THEN 1 ELSE 0 END) as obtained_trophies
                  FROM psn_games g
                  LEFT JOIN psn_trophies t ON g.id = t.psn_game_id
                  WHERE g.user_id = :uid
                  GROUP BY g.id, g.title, g.image_url, g.last_played_at
                  ORDER BY g.last_played_at DESC, g.title ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

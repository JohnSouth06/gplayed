<?php
class Game
{
    private $conn;
    private $table = 'user_games'; // Changement ici : on cible la table de liaison

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function catalogGameExists($id)
    {
        $query = "SELECT id FROM games WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getAll($userId)
    {
        $query = "SELECT ug.*, g.title, g.cover_url AS image_url, g.genres, g.release_date, 
                     g.summary, g.developer, g.publisher, g.rating AS igdb_rating,
                     p.time_main AS playtime 
              FROM " . $this->table . " ug
              JOIN games g ON ug.game_id = g.id
              LEFT JOIN playtime p ON ug.id = p.game_id 
              WHERE ug.user_id = :user_id 
              ORDER BY ug.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSelectableGames($userId)
    {
        $query = "SELECT ug.*, g.title, g.cover_url AS image_url 
                  FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.user_id = :user_id 
                  AND ug.status NOT IN ('wishlist', 'loaned') 
                  ORDER BY g.title ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLoanedGames($userId)
    {
        $query = "SELECT ug.*, g.title, g.cover_url AS image_url 
                  FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.user_id = :user_id AND ug.status = 'loaned' 
                  ORDER BY ug.loaned_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOne($id, $userId)
    {
        $query = "SELECT ug.*, g.title, g.cover_url AS image_url, g.genres, g.release_date 
                  FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.id = :id AND ug.user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id, $userId)
    {
        $game = $this->getOne($id, $userId);
        // On conserve la suppression d'image physique si c'était une vieille image uploadée
        if ($game && !empty($game['image_url']) && !filter_var($game['image_url'], FILTER_VALIDATE_URL)) {
            $filePath = dirname(__DIR__) . '/' . $game['image_url'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    public function save($data, $file, $userId)
    {
        $igdbId = $data['rawg_id'] ?? null;
        $imagePath = $data['image_url_hidden'] ?? '';
        $dominantColor = null;

        // Conservation de la logique d'upload si on en a encore besoin
        if (!empty($file['image_upload']['name'])) {
            $uploaded = $this->uploadImage($file['image_upload']);
            if ($uploaded) {
                if (!empty($imagePath) && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
                    @unlink(dirname(__DIR__) . '/' . $imagePath);
                }
                $imagePath = $uploaded;
                $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
            }
        } elseif (!empty($imagePath) && filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $dominantColor = $this->getAverageColor($imagePath);
        } elseif (!empty($imagePath) && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
            $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
        }

        $finalPlatform = ($data['platform'] === 'Multiplateforme' && !empty($data['platform_custom'])) ? $data['platform_custom'] : $data['platform'];

        if (empty($dominantColor)) {
            $dominantColor = $this->getFallbackColor($finalPlatform);
        }

        $format = $data['format'] ?? 'digital';
        $releaseDate = !empty($data['release_date']) ? $data['release_date'] : null;
        $metaScore = (isset($data['metacritic']) && $data['metacritic'] !== '') ? $data['metacritic'] : null;
        $price = (isset($data['estimated_price']) && $data['estimated_price'] !== '') ? $data['estimated_price'] : null;

        // 1. GESTION DU CATALOGUE (Nouvelle architecture)
        if ($igdbId && !$this->catalogGameExists($igdbId)) {
            $queryCatalog = "INSERT INTO games (id, title, cover_url, genres, release_date, summary, developer, publisher, rating) 
                         VALUES (:id, :title, :cover_url, :genres, :release_date, :summary, :dev, :pub, :hltb, :rating)";
            $stmtCat = $this->conn->prepare($queryCatalog);
            $stmtCat->execute([
                ':id' => $igdbId,
                ':title' => $data['title'],
                ':cover_url' => $data['image_url_hidden'],
                ':genres' => $data['genres'],
                ':release_date' => $data['release_date'],
                ':summary' => $data['description'], // On stocke la description globale ici
                ':dev' => $data['developer'] ?? null,
                ':pub' => $data['publisher'] ?? null,
                ':rating' => $data['metacritic'] // Note IGDB
            ]);
        }

        // 2. GESTION DE LA COLLECTION UTILISATEUR
        if (!empty($data['game_id'])) {
            $query = "UPDATE " . $this->table . " SET 
            platform=:platform, format=:format, status=:status, 
            comment=:comment, dominant_color=:color, estimated_price=:price 
            WHERE id=:id AND user_id=:uid";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['game_id']);
        } else {
            $query = "INSERT INTO " . $this->table . " 
            (user_id, game_id, platform, format, status, comment, dominant_color, estimated_price) 
            VALUES (:uid, :igdb_id, :platform, :format, :status, :comment, :color, :price)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':igdb_id', $igdbId);
        }

        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':platform', $finalPlatform);
        $stmt->bindParam(':format', $format);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':comment', $data['comment']);
        $stmt->bindParam(':color', $dominantColor);
        $stmt->bindParam(':price', $price);

        return $stmt->execute();
    }

    public function searchGames($userId, $term)
    {
        $query = "SELECT ug.*, g.title, g.cover_url AS image_url, g.genres, g.release_date, p.time_main AS playtime 
                  FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id
                  LEFT JOIN playtime p ON ug.id = p.game_id 
                  WHERE ug.user_id = :user_id 
                  AND (g.title LIKE :term OR g.genres LIKE :term OR ug.platform LIKE :term)
                  ORDER BY ug.created_at DESC";

        $stmt = $this->conn->prepare($query);

        $searchTerm = "%" . $term . "%";
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkDuplicate($userId, $rawgId, $title, $platform)
    {
        // On vérifie d'abord via l'ID de jeu (qui est maintenant game_id)
        if (!empty($rawgId)) {
            $query = "SELECT id FROM " . $this->table . " WHERE user_id = :uid AND game_id = :rawg_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':uid', $userId);
            $stmt->bindParam(':rawg_id', $rawgId);
            $stmt->execute();
            if ($stmt->fetch()) return true;
        }

        // Sinon on vérifie par titre et plateforme via une jointure
        $query = "SELECT ug.id FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.user_id = :uid AND g.title = :title AND ug.platform = :platform LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':platform', $platform);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    public function getWishlist($userId)
    {
        $query = "SELECT ug.*, g.title, g.cover_url AS image_url 
                  FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.user_id = :user_id AND ug.status = 'wishlist' 
                  ORDER BY ug.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acquireGame($id, $userId)
    {
        $query = "UPDATE " . $this->table . " SET status = 'not_started', created_at = NOW() WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    public function findPlayStationGameByTitle($userId, $psnTitle)
    {
        $query = "SELECT ug.id, g.title, ug.status FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.user_id = :uid 
                  AND (ug.platform LIKE '%PS%' OR ug.platform LIKE '%PlayStation%')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $userId);
        $stmt->execute();
        $localGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($localGames)) return false;

        $cleanPsn = $this->normalizeGameTitle($psnTitle);
        $psnParts = explode(':', $psnTitle);
        $cleanPsnShort = $this->normalizeGameTitle($psnParts[0]);

        $bestMatch = null;
        $highestSimilarity = 0;

        foreach ($localGames as $game) {
            $cleanLocal = $this->normalizeGameTitle($game['title']);
            if (empty($cleanLocal)) continue;

            if ($cleanLocal === $cleanPsn || $cleanLocal === $cleanPsnShort) {
                return $game;
            }

            if (strpos($cleanPsn, $cleanLocal) !== false || strpos($cleanLocal, $cleanPsn) !== false) {
                return $game;
            }

            similar_text($cleanPsn, $cleanLocal, $percent);
            if ($percent > $highestSimilarity && $percent > 85) {
                $highestSimilarity = $percent;
                $bestMatch = $game;
            }
        }
        return $bestMatch;
    }

    private function normalizeGameTitle($title)
    {
        $title = mb_strtolower(trim($title), 'UTF-8');
        $unwanted_array = [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            '™' => '',
            '®' => '',
            '©' => '',
            ' - ' => ' ',
            ': ' => ' '
        ];
        $title = strtr($title, $unwanted_array);
        $title = preg_replace('/[^a-z0-9]/', '', $title);
        return $title;
    }

    public function importEntry($game, $userId)
    {
        $igdbId = $game['rawg_id'] ?? null;
        if (!$igdbId) return false;

        $img = $game['image_url'] ?? '';
        if (!empty($img)) {
            $img = str_replace(' ', '%20', $img);
        }

        if (!$this->catalogGameExists($igdbId)) {
            $queryCatalog = "INSERT INTO games (id, title, cover_url, genres, release_date) 
                             VALUES (:id, :title, :cover_url, :genres, :release_date)";
            $stmtCat = $this->conn->prepare($queryCatalog);
            $stmtCat->bindParam(':id', $igdbId);
            $stmtCat->bindParam(':title', $game['title']);
            $stmtCat->bindParam(':cover_url', $img);
            $stmtCat->bindParam(':genres', $game['genres']);
            $stmtCat->bindParam(':release_date', $game['release_date']);
            $stmtCat->execute();
        }

        $query = "INSERT INTO " . $this->table . " 
                  (user_id, game_id, platform, format, status, comment, dominant_color, estimated_price) 
                  VALUES (:uid, :game_id, :platform, :format, :status, :comment, :color, :price)";
        $stmt = $this->conn->prepare($query);

        $dominantColor = $game['dominant_color'] ?? null;
        if ($dominantColor === 'rgb(30, 30, 30)') {
            $dominantColor = null;
        }

        if (empty($dominantColor) && !empty($img)) {
            $pathForColor = filter_var($img, FILTER_VALIDATE_URL) ? $img : dirname(__DIR__) . '/' . $img;
            $dominantColor = $this->getAverageColor($pathForColor);
        }

        if (empty($dominantColor)) {
            $dominantColor = $this->getFallbackColor($game['platform'] ?? '');
        }

        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':game_id', $igdbId);
        $stmt->bindParam(':platform', $game['platform']);
        $stmt->bindParam(':format', $game['format']);
        $stmt->bindParam(':status', $game['status']);
        $stmt->bindParam(':comment', $game['comment']);
        $stmt->bindParam(':color', $dominantColor);
        $stmt->bindParam(':price', $game['estimated_price']);

        return $stmt->execute();
    }

    private function getAverageColor($filepath)
    {
        if (empty($filepath)) return null;

        $img = null;
        if (filter_var($filepath, FILTER_VALIDATE_URL)) {
            $ch = curl_init($filepath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $data = curl_exec($ch);
            curl_close($ch);
            if ($data) {
                $img = @imagecreatefromstring($data);
            }
        } else {
            if (file_exists($filepath)) {
                $data = file_get_contents($filepath);
                if ($data) {
                    $img = @imagecreatefromstring($data);
                }
            }
        }

        if (!$img) return null;

        $pixel = imagecreatetruecolor(1, 1);
        imagecopyresampled($pixel, $img, 0, 0, 0, 0, 1, 1, imagesx($img), imagesy($img));
        $rgb = imagecolorat($pixel, 0, 0);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        imagedestroy($img);
        imagedestroy($pixel);

        return "rgb($r, $g, $b)";
    }

    private function getFallbackColor($platform)
    {
        $platform = strtolower($platform);
        if (strpos($platform, 'ps') !== false || strpos($platform, 'playstation') !== false) {
            return 'rgb(0, 55, 145)';
        } elseif (strpos($platform, 'xbox') !== false) {
            return 'rgb(16, 124, 16)';
        } elseif (strpos($platform, 'nintendo') !== false || strpos($platform, 'switch') !== false) {
            return 'rgb(228, 0, 15)';
        } elseif (strpos($platform, 'pc') !== false) {
            return 'rgb(20, 20, 20)';
        }
        return 'rgb(30, 30, 30)';
    }

    private function uploadImage($file)
    {
        $uploadDir = dirname(__DIR__) . '/uploads/games/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        $webFilePath = 'uploads/games/' . $fileName;

        $imageFileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'webp'])) {
            return null;
        }

        if ($file['size'] > 5000000) {
            return null;
        }

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $this->resizeAndCompressImage($filePath, $filePath, 1200, 80);
        }
        return null;
    }

    private function resizeAndCompressImage($source, $destination, $maxWidth, $quality)
    {
        $info = getimagesize($source);
        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        if ($width <= $maxWidth) {
            $newWidth = $width;
            $newHeight = $height;
        } else {
            $newWidth = $maxWidth;
            $newHeight = intval(($height / $width) * $maxWidth);
        }

        $src = null;
        switch ($mime) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $src = imagecreatefrompng($source);
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($source);
                break;
            default:
                return null;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime == 'image/png' || $mime == 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = false;
        switch ($mime) {
            case 'image/jpeg':
                $saved = imagejpeg($dst, $destination, $quality);
                break;
            case 'image/png':
                $saved = imagepng($dst, $destination, 8);
                break;
            case 'image/webp':
                $saved = imagewebp($dst, $destination, $quality);
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);

        $webFilePath = str_replace(dirname(__DIR__) . '/', '', $destination);

        return $saved ? $webFilePath : null;
    }

    public function loanGame($id, $userId, $loanedTo, $loanedDate)
    {
        $query = "UPDATE " . $this->table . " SET previous_status = status, status = 'loaned', loaned_to = :loaned_to, loaned_date = :loaned_date WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':loaned_to', $loanedTo);
        $stmt->bindParam(':loaned_date', $loanedDate);
        return $stmt->execute();
    }

    public function returnLoanedGame($id, $userId)
    {
        $query = "UPDATE " . $this->table . " 
                SET status = COALESCE(previous_status, 'not_started'), 
                    loaned_to = NULL, 
                    loaned_date = NULL,
                    previous_status = NULL 
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    public function getGamesByStatusRandom($userId, $status)
    {
        $query = "SELECT ug.id, g.title, g.cover_url AS image_url, ug.dominant_color 
                  FROM " . $this->table . " ug 
                  JOIN games g ON ug.game_id = g.id 
                  WHERE ug.user_id = :user_id AND ug.status = :status 
                  ORDER BY RAND()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateGameStatus($id, $userId, $status)
    {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }
}

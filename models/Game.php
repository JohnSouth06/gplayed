<?php
class Game {
    private $conn;
    private $table = 'games';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOne($id, $userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function delete($id, $userId) {
        $game = $this->getOne($id, $userId);
        if ($game && !empty($game['image_url'])) {
            $filePath = dirname(__DIR__) . '/' . $game['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    public function save($data, $file, $userId) {
        $imagePath = $data['image_url_hidden'] ?? '';
        $dominantColor = 'rgb(30, 30, 30)';

        // Gestion Image
        if (!empty($file['image_upload']['name'])) {
            $uploaded = $this->uploadImage($file['image_upload']);
            if ($uploaded) {
                if (!empty($imagePath) && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
                    unlink(dirname(__DIR__) . '/' . $imagePath);
                }
                $imagePath = $uploaded;
                $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
            }
        } elseif (!empty($imagePath) && filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $downloaded = $this->downloadImage($imagePath);
            if ($downloaded) {
                $imagePath = $downloaded;
                $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
            }
        } elseif (!empty($imagePath) && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
            $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
        }

        // Champs Standards
        $finalPlatform = ($data['platform'] === 'Multiplateforme' && !empty($data['platform_custom'])) ? $data['platform_custom'] : $data['platform'];
        $format = $data['format'] ?? 'digital';
        $releaseDate = !empty($data['release_date']) ? $data['release_date'] : null;
        $metaScore = (isset($data['metacritic']) && $data['metacritic'] !== '') ? $data['metacritic'] : null;
        $userRating = (isset($data['user_rating']) && $data['user_rating'] !== '') ? $data['user_rating'] : null;
        $price = (isset($data['estimated_price']) && $data['estimated_price'] !== '') ? $data['estimated_price'] : null;
        $personalTags = (isset($data['personal_tags']) && $data['personal_tags'] !== '') ? $data['personal_tags'] : null;

        if (!empty($data['game_id'])) {
            // UPDATE
            $query = "UPDATE " . $this->table . " SET 
                title=:title, platform=:platform, format=:format, status=:status, release_date=:date, 
                metacritic_score=:meta, user_rating=:rating, comment=:comment, 
                description=:desc, genres=:genres, dominant_color=:color, estimated_price=:price, personal_tags=:tags";
            
            if ($imagePath) $query .= ", image_url=:img";
            
            $query .= " WHERE id=:id AND user_id=:uid";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['game_id']);
            if ($imagePath) $stmt->bindParam(':img', $imagePath);
        } else {
            // INSERT
            $query = "INSERT INTO " . $this->table . " 
                (user_id, title, platform, format, status, release_date, metacritic_score, user_rating, comment, image_url, description, genres, dominant_color, estimated_price, personal_tags) 
                VALUES (:uid, :title, :platform, :format, :status, :date, :meta, :rating, :comment, :img, :desc, :genres, :color, :price, :tags)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':img', $imagePath);
        }

        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':platform', $finalPlatform);
        $stmt->bindParam(':format', $format);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':date', $releaseDate);
        $stmt->bindParam(':meta', $metaScore);
        $stmt->bindParam(':rating', $userRating);
        $stmt->bindParam(':comment', $data['comment']);
        $stmt->bindParam(':desc', $data['description']);
        $stmt->bindParam(':genres', $data['genres']);
        $stmt->bindParam(':color', $dominantColor);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':tags', $personalTags);

        return $stmt->execute();
    }

    public function importEntry($game, $userId) {
        $query = "INSERT INTO " . $this->table . " 
        (user_id, title, platform, format, status, release_date, metacritic_score, user_rating, comment, image_url, description, genres, dominant_color, estimated_price, personal_tags) 
        VALUES (:uid, :title, :platform, :format, :status, :date, :meta, :rating, :comment, :img, :desc, :genres, :color, :price, :tags)";
        
        $stmt = $this->conn->prepare($query);
        
        $img = $game['image_url'];
        if ($img && filter_var($img, FILTER_VALIDATE_URL)) {
             $downloaded = $this->downloadImage($img);
             if($downloaded) $img = $downloaded;
        }

        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':title', $game['title']);
        $stmt->bindParam(':platform', $game['platform']);
        $stmt->bindParam(':format', $game['format']);
        $stmt->bindParam(':status', $game['status']);
        $stmt->bindParam(':date', $game['release_date']);
        $stmt->bindParam(':meta', $game['metacritic_score']);
        $stmt->bindParam(':rating', $game['user_rating']);
        $stmt->bindParam(':comment', $game['comment']);
        $stmt->bindParam(':img', $img);
        $stmt->bindParam(':desc', $game['description']);
        $stmt->bindParam(':genres', $game['genres']);
        $stmt->bindParam(':color', $game['dominant_color']);
        $stmt->bindParam(':price', $game['estimated_price']);
        
        $tags = $game['personal_tags'] ?? null;
        $stmt->bindParam(':tags', $tags);

        return $stmt->execute();
    }

    private function downloadImage($url) {
        $uploadDir = dirname(__DIR__) . '/uploads/games/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid() . "_rawg.jpg";
        $content = @file_get_contents($url);
        if ($content === false) return null;
        $srcImage = @imagecreatefromstring($content);
        if (!$srcImage) return null;
        $width = imagesx($srcImage);
        $height = imagesy($srcImage);
        $maxWidth = 800;
        if ($width > $maxWidth) { $newWidth = $maxWidth; $newHeight = floor($height * ($maxWidth / $width)); } else { $newWidth = $width; $newHeight = $height; }
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($dstImage, 0, 0, imagecolorallocate($dstImage, 255, 255, 255));
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($dstImage, $uploadDir . $fileName, 80);
        imagedestroy($srcImage); imagedestroy($dstImage);
        return 'uploads/games/' . $fileName;
    }
    private function getAverageColor($filepath) {
        $info = @getimagesize($filepath);
        if (!$info) return 'rgb(30, 30, 30)';
        $mime = $info['mime'];
        switch ($mime) { case 'image/jpeg': $img = imagecreatefromjpeg($filepath); break; case 'image/png': $img = imagecreatefrompng($filepath); break; case 'image/webp': if(function_exists('imagecreatefromwebp')) { $img = imagecreatefromwebp($filepath); } else { return 'rgb(30, 30, 30)'; } break; default: return 'rgb(30, 30, 30)'; }
        if (!$img) return 'rgb(30, 30, 30)';
        $pixel = imagecreatetruecolor(1, 1);
        imagecopyresampled($pixel, $img, 0, 0, 0, 0, 1, 1, imagesx($img), imagesy($img));
        $rgb = imagecolorat($pixel, 0, 0);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        imagedestroy($img); imagedestroy($pixel);
        return "rgb($r, $g, $b)";
    }
    private function uploadImage($file) {
        $uploadDir = dirname(__DIR__) . '/uploads/games/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid() . "_" . basename($file["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $ext = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return null;
        switch ($ext) { case 'jpg': case 'jpeg': $src = imagecreatefromjpeg($file["tmp_name"]); break; case 'png': $src = imagecreatefrompng($file["tmp_name"]); break; case 'gif': $src = imagecreatefromgif($file["tmp_name"]); break; case 'webp': if(function_exists('imagecreatefromwebp')) { $src = imagecreatefromwebp($file["tmp_name"]); } else { return null; } break; default: return null; }
        if (!$src) return null;
        list($width, $height) = getimagesize($file["tmp_name"]);
        $newWidth = 800; $newHeight = ($width > $newWidth) ? ($height * ($newWidth / $width)) : $height;
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if ($ext == 'png' || $ext == 'webp') { imagealphablending($dst, false); imagesavealpha($dst, true); }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $saved = ($ext == 'png') ? imagepng($dst, $targetFilePath, 8) : (($ext == 'webp' && function_exists('imagewebp')) ? imagewebp($dst, $targetFilePath, 85) : imagejpeg($dst, $targetFilePath, 85));
        imagedestroy($src); imagedestroy($dst);
        return $saved ? 'uploads/games/' . $fileName : null;
    }
}
?>
<?php
class Game
{
    private $conn;
    private $table = 'games';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll($userId)
    {
        $query = "SELECT g.*, p.time_main AS playtime 
                  FROM " . $this->table . " g 
                  LEFT JOIN playtime p ON g.id = p.game_id 
                  WHERE g.user_id = :user_id 
                  ORDER BY g.created_at DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function getSelectableGames($userId)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND status NOT IN ('wishlist', 'loaned') 
                  ORDER BY title ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLoanedGames($userId)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id AND status = 'loaned' 
                  ORDER BY loaned_date DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getOne($id, $userId)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function delete($id, $userId)
    {
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

    public function save($data, $file, $userId)
    {
        $rawgId = $data['rawg_id'] ?? null; 
        $imagePath = $data['image_url_hidden'] ?? '';
        $dominantColor = null;

        if (!empty($file['image_upload']['name'])) {
            $uploaded = $this->uploadImage($file['image_upload']);
            if ($uploaded) {
                if (!empty($imagePath) && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
                    @unlink(dirname(__DIR__) . '/' . $imagePath);
                }
                $imagePath = $uploaded;
                $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
            }
        }
        elseif (!empty($imagePath) && filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $dominantColor = $this->getAverageColor($imagePath);
        }
        elseif (!empty($imagePath) && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
            $dominantColor = $this->getAverageColor(dirname(__DIR__) . '/' . $imagePath);
        }

        $finalPlatform = ($data['platform'] === 'Multiplateforme' && !empty($data['platform_custom'])) ? $data['platform_custom'] : $data['platform'];
        
        if (empty($dominantColor)) {
            $dominantColor = $this->getFallbackColor($finalPlatform);
        }

        $format = $data['format'] ?? 'digital';
        $releaseDate = !empty($data['release_date']) ? $data['release_date'] : null;
        $metaScore = (isset($data['metacritic']) && $data['metacritic'] !== '') ? $data['metacritic'] : null;
        $userRating = (isset($data['user_rating']) && $data['user_rating'] !== '') ? $data['user_rating'] : null;
        $price = (isset($data['estimated_price']) && $data['estimated_price'] !== '') ? $data['estimated_price'] : null;

        if (!empty($data['game_id'])) {
            $query = "UPDATE " . $this->table . " SET 
            title=:title, platform=:platform, format=:format, status=:status, release_date=:date, 
            metacritic_score=:meta, user_rating=:rating, comment=:comment, 
            description=:desc, genres=:genres, dominant_color=:color, estimated_price=:price,
            rawg_id=:rawg_id"; 

            if ($imagePath) $query .= ", image_url=:img";
            $query .= " WHERE id=:id AND user_id=:uid";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['game_id']);
            if ($imagePath) $stmt->bindParam(':img', $imagePath);
        } else {
            $query = "INSERT INTO " . $this->table . " 
            (user_id, title, platform, format, status, release_date, metacritic_score, user_rating, comment, image_url, description, genres, dominant_color, estimated_price, rawg_id) 
            VALUES (:uid, :title, :platform, :format, :status, :date, :meta, :rating, :comment, :img, :desc, :genres, :color, :price, :rawg_id)"; 
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':img', $imagePath);
        }

        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':rawg_id', $rawgId);
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

        return $stmt->execute();
    }

    public function searchGames($userId, $term)
    {
        $query = "SELECT g.*, p.time_main AS playtime 
                  FROM " . $this->table . " g 
                  LEFT JOIN playtime p ON g.id = p.game_id 
                  WHERE g.user_id = :user_id 
                  AND (g.title LIKE :term OR g.genres LIKE :term OR g.platform LIKE :term)
                  ORDER BY g.created_at DESC";

        $stmt = $this->conn->prepare($query);

        $searchTerm = "%" . $term . "%";
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkDuplicate($userId, $rawgId, $title, $platform)
    {
        if (!empty($rawgId)) {
            $query = "SELECT id FROM " . $this->table . " WHERE user_id = :uid AND rawg_id = :rawg_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':uid', $userId);
            $stmt->bindParam(':rawg_id', $rawgId);
            $stmt->execute();
            if ($stmt->fetch()) return true;
        }

        $query = "SELECT id FROM " . $this->table . " WHERE user_id = :uid AND title = :title AND platform = :platform LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':platform', $platform);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    public function getWishlist($userId)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id AND status = 'wishlist' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function acquireGame($id, $userId)
    {
        $query = "UPDATE " . $this->table . " SET status = 'not_started', created_at = NOW() WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    // --- IMPORT JSON ---
    public function importEntry($game, $userId)
    {
        $query = "INSERT INTO " . $this->table . " 
        (user_id, title, platform, format, status, release_date, metacritic_score, user_rating, comment, image_url, description, genres, dominant_color, estimated_price) 
        VALUES (:uid, :title, :platform, :format, :status, :date, :meta, :rating, :comment, :img, :desc, :genres, :color, :price)";

        $stmt = $this->conn->prepare($query);

        $img = $game['image_url'] ?? '';
        if (!empty($img)) {
            $img = str_replace(' ', '%20', $img); 
        }

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
        $stmt->bindParam(':color', $dominantColor);
        $stmt->bindParam(':price', $game['estimated_price']);

        return $stmt->execute();
    }

    // --- NOUVELLE FONCTION: cURL en RAM (Bypass Steam) ---
    private function downloadImage($url)
    {
        $uploadDir = dirname(__DIR__) . '/uploads/games/';

        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($data)) {
            return null;
        }

        $srcImage = @imagecreatefromstring($data);
        if (!$srcImage) {
            return null;
        }

        $fileName = uniqid() . "_rawg.jpg";
        $targetPath = $uploadDir . $fileName;

        $width = imagesx($srcImage);
        $height = imagesy($srcImage);
        $maxWidth = 800;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) floor($height * ($maxWidth / $width));

            $dstImage = imagecreatetruecolor($newWidth, $newHeight);
            imagefill($dstImage, 0, 0, imagecolorallocate($dstImage, 255, 255, 255));
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $saved = imagejpeg($dstImage, $targetPath, 85);
            imagedestroy($dstImage);
        } else {
            $saved = imagejpeg($srcImage, $targetPath, 85);
        }

        imagedestroy($srcImage);
        return $saved ? 'uploads/games/' . $fileName : null;
    }

    // --- NOUVELLE FONCTION : Lecture locale ou distante sans échec ---
    private function getAverageColor($filepath)
    {
        if (empty($filepath)) return null;

        $img = null;

        if (filter_var($filepath, FILTER_VALIDATE_URL)) {
            $ch = curl_init($filepath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
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
        if (stripos($platform, 'PS') !== false || stripos($platform, 'PlayStation') !== false) return 'rgb(0, 112, 210)'; 
        if (stripos($platform, 'Xbox') !== false) return 'rgb(16, 124, 16)'; 
        if (stripos($platform, 'Switch') !== false || stripos($platform, 'Nintendo') !== false) return 'rgb(228, 0, 15)'; 
        if (stripos($platform, 'PC') !== false || stripos($platform, 'Steam') !== false) return 'rgb(102, 192, 244)'; 
        
        return 'rgb(100, 100, 100)'; 
    }

    private function uploadImage($file)
    {
        $uploadDir = dirname(__DIR__) . '/uploads/games/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = uniqid() . "_" . basename($file["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $webFilePath = 'uploads/games/' . $fileName;

        $ext = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return null;

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $src = @imagecreatefromjpeg($file["tmp_name"]);
                break;
            case 'png':
                $src = @imagecreatefrompng($file["tmp_name"]);
                break;
            case 'gif':
                $src = @imagecreatefromgif($file["tmp_name"]);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($file["tmp_name"]);
                } else {
                    return null;
                }
                break;
            default:
                return null;
        }
        if (!$src) return null;

        list($width, $height) = getimagesize($file["tmp_name"]);
        $newWidth = 800;
        $newHeight = ($width > $newWidth) ? ($height * ($newWidth / $width)) : $height;
        $newWidth = ($width > $newWidth) ? $newWidth : $width;

        $dst = imagecreatetruecolor($newWidth, (int)$newHeight);
        if ($ext == 'png' || $ext == 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, (int)$newHeight, $width, $height);

        $saved = false;
        if ($ext == 'png') $saved = imagepng($dst, $targetFilePath, 8);
        elseif ($ext == 'webp') {
            if (function_exists('imagewebp')) {
                $saved = imagewebp($dst, $targetFilePath, 85);
            } else {
                $saved = false;
            }
        } else $saved = imagejpeg($dst, $targetFilePath, 85);

        imagedestroy($src);
        imagedestroy($dst);
        return $saved ? $webFilePath : null;
    }

    public function loanGame($id, $userId, $loanedTo, $loanedDate)
    {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'loaned', loaned_to = :loaned_to, loaned_date = :loaned_date 
                  WHERE id = :id AND user_id = :user_id";
                  
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
                  SET status = 'not_started', loaned_to = NULL, loaned_date = NULL 
                  WHERE id = :id AND user_id = :user_id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }

    public function getGamesByStatusRandom($userId, $status)
    {
        $query = "SELECT id, title, image_url, dominant_color FROM " . $this->table . " 
                  WHERE user_id = :user_id AND status = :status 
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
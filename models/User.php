<?php
class User
{
    private $conn;
    private $table = 'users';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function isPasswordStrong($password)
    {
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/';
        return preg_match($regex, $password);
    }

    public function register($username, $email, $password)
    {
        if (!$this->isPasswordStrong($password)) {
            return "weak_password";
        }

        $query = "INSERT INTO " . $this->table . " (username, email, password, language) VALUES (:username, :email, :password, :lang)";
        $stmt = $this->conn->prepare($query);
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $passHash);
        $stmt->bindParam(':lang', $language);

        try {
            if ($stmt->execute()) return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return "exists";
        }
        return false;
    }


    public function loginOrRegisterGoogle($googleInfo)
    {
        $email = $googleInfo->email;
        $googleId = $googleInfo->id;
        $name = $googleInfo->name;

        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE google_id = :gid LIMIT 1");
        $stmt->execute([':gid' => $googleId]);
        $user = $stmt->fetch();

        if ($user) return $user;

        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $update = $this->conn->prepare("UPDATE " . $this->table . " SET google_id = :gid WHERE id = :id");
            $update->execute([':gid' => $googleId, ':id' => $existingUser['id']]);
            return $existingUser;
        }

        $randomPass = bin2hex(random_bytes(8)) . 'A1!';
        $passHash = password_hash($randomPass, PASSWORD_DEFAULT);
        
        $defaultAvatar = 'uploads/avatars/default.png'; 

        $query = "INSERT INTO " . $this->table . " (username, email, password, google_id, language, avatar_url) VALUES (:username, :email, :pass, :gid, 'fr', :avatar)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            ':username' => $name,
            ':email' => $email,
            ':pass' => $passHash,
            ':gid' => $googleId,
            ':avatar' => $defaultAvatar
        ])) {
            return $this->login($name, $randomPass);
        }
        return false;
    }

    public function login($username, $password)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($row = $stmt->fetch()) {
            if (password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function emailExists($email)
    {
        $stmt = $this->conn->prepare("SELECT id FROM " . $this->table . " WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function setResetToken($email, $token)
    {
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $query = "UPDATE " . $this->table . " SET reset_token = :token, reset_expires = :expires WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

    public function resetPassword($token, $newPassword)
    {
        $query = "SELECT id FROM " . $this->table . " WHERE reset_token = :token AND reset_expires > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($user = $stmt->fetch()) {
            if (!$this->isPasswordStrong($newPassword)) return "weak_password";

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = "UPDATE " . $this->table . " SET password = :pass, reset_token = NULL, reset_expires = NULL WHERE id = :id";
            $stmtUpdate = $this->conn->prepare($update);
            $stmtUpdate->bindParam(':pass', $hash);
            $stmtUpdate->bindParam(':id', $user['id']);
            return $stmtUpdate->execute();
        }
        return false;
    }

    public function getById($id)
    {
        $query = "SELECT id, username, email, avatar_url, created_at, language FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getIdByUsername($username)
    {
        $query = "SELECT id, username, avatar_url FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update($id, $username, $email, $newPassword, $files, $language = null)
    {
        $fields = [];
        $params = [':id' => $id];

        if (!empty($username)) {
            $stmt = $this->conn->prepare("SELECT id FROM " . $this->table . " WHERE username = :username AND id != :id");
            $stmt->execute([':username' => $username, ':id' => $id]);
            if ($stmt->fetch()) {
                return "username_exists";
            }
            
            $fields[] = "username = :username";
            $params[':username'] = $username;
        }

        if (!empty($email)) {
            $fields[] = "email = :email";
            $params[':email'] = $email;
        }

        if (!empty($language)) {
            $fields[] = "language = :language";
            $params[':language'] = $language;
        }

        if (!empty($newPassword)) {
            if (!$this->isPasswordStrong($newPassword)) return "weak_password";
            $fields[] = "password = :password";
            $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if (!empty($files['avatar']['name'])) {
            $avatarPath = $this->uploadImage($files['avatar']);
            if ($avatarPath) {
                $currentUser = $this->getById($id);
                if (!empty($currentUser['avatar_url'])) {
                    $oldFile = dirname(__DIR__) . '/' . $currentUser['avatar_url'];
                    if (file_exists($oldFile)) unlink($oldFile);
                }
                $fields[] = "avatar_url = :avatar";
                $params[':avatar'] = $avatarPath;
            }
        }

        if (empty($fields)) return true;

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id)
    {
        $user = $this->getById($id);
        if (!empty($user['avatar_url'])) {
            $file = dirname(__DIR__) . '/' . $user['avatar_url'];
            if (file_exists($file)) unlink($file);
        }
        $stmt = $this->conn->prepare("SELECT image_url FROM games WHERE user_id = ?");
        $stmt->execute([$id]);
        while ($row = $stmt->fetch()) {
            if (!empty($row['image_url'])) {
                $file = dirname(__DIR__) . '/' . $row['image_url'];
                if (file_exists($file)) unlink($file);
            }
        }
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    private function uploadImage($file)
    {
        $uploadDir = dirname(__DIR__) . '/uploads/avatars/';
        $webDir = 'uploads/avatars/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = uniqid() . "_avatar_" . basename($file["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $webFilePath = $webDir . $fileName;
        $ext = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return null;
        list($width, $height) = getimagesize($file["tmp_name"]);
        $size = min($width, $height);
        $x = ($width - $size) / 2;
        $y = ($height - $size) / 2;
        $newSize = 200;
        $dst = imagecreatetruecolor($newSize, $newSize);
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($file["tmp_name"]);
                break;
            case 'png':
                $src = imagecreatefrompng($file["tmp_name"]);
                break;
            case 'webp':
                $src = imagecreatefromwebp($file["tmp_name"]);
                break;
            default:
                return null;
        }
        if ($ext == 'png' || $ext == 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, $x, $y, $newSize, $newSize, $size, $size);
        $saved = ($ext == 'png') ? imagepng($dst, $targetFilePath) : (($ext == 'webp') ? imagewebp($dst, $targetFilePath) : imagejpeg($dst, $targetFilePath, 90));
        imagedestroy($src);
        imagedestroy($dst);
        return $saved ? $webFilePath : null;
    }

    // --- FONCTIONS SOCIALES ---
    public function getAllUsersExcept($currentUserId)
    {
        $query = "SELECT id, username, avatar_url, created_at FROM " . $this->table . " WHERE id != :id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $currentUserId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function follow($followerId, $followedId)
    {
        if ($followerId == $followedId) return false; 
        $query = "INSERT IGNORE INTO user_follows (follower_id, followed_id) VALUES (:follower, :followed)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':follower', $followerId);
        $stmt->bindParam(':followed', $followedId);
        return $stmt->execute();
    }

    public function unfollow($followerId, $followedId)
    {
        $query = "DELETE FROM user_follows WHERE follower_id = :follower AND followed_id = :followed";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':follower', $followerId);
        $stmt->bindParam(':followed', $followedId);
        return $stmt->execute();
    }

    public function getFollowedIds($userId)
    {
        $query = "SELECT followed_id FROM user_follows WHERE follower_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

public function loginOrRegisterDiscord($discordUser)
    {
        $discordId = $discordUser['id'];
        $email = $discordUser['email'];
        $username = $discordUser['username'];
        $avatarHash = $discordUser['avatar'];

        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE discord_id = :did LIMIT 1");
        $stmt->execute([':did' => $discordId]);
        if ($user = $stmt->fetch()) return $user;

        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($existingUser = $stmt->fetch()) {
            $update = $this->conn->prepare("UPDATE " . $this->table . " SET discord_id = :did WHERE id = :id");
            $update->execute([':did' => $discordId, ':id' => $existingUser['id']]);
            return $existingUser;
        }

        $randomPass = bin2hex(random_bytes(8)) . 'A1!';
        $passHash = password_hash($randomPass, PASSWORD_DEFAULT);

        $avatarUrl = 'uploads/avatars/default.png';
        if ($avatarHash) {
            $avatarUrl = "https://cdn.discordapp.com/avatars/$discordId/$avatarHash.png";
        }

        $query = "INSERT INTO " . $this->table . " (username, email, password, discord_id, language, avatar_url) VALUES (:username, :email, :pass, :did, 'fr', :avatar)";
        $stmt = $this->conn->prepare($query);

        try {
            if ($stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':pass' => $passHash,
                ':did' => $discordId,
                ':avatar' => $avatarUrl
            ])) {
                return $this->login($username, $randomPass);
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }
}

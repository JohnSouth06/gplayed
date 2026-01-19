<?php
class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Validation Mot de passe fort
    public function isPasswordStrong($password) {
        // Min 10 chars, 1 Maj, 1 Min, 1 Chiffre, 1 Spécial
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/';
        return preg_match($regex, $password);
    }

    public function register($username, $email, $password) {
        if (!$this->isPasswordStrong($password)) {
            return "weak_password";
        }

        $query = "INSERT INTO " . $this->table . " (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);
        $passHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $passHash);

        try {
            if($stmt->execute()) return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            // Gestion erreur doublon (code 23000)
            if ($e->getCode() == 23000) return "exists";
        }
        return false;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if($row = $stmt->fetch()) {
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT id FROM " . $this->table . " WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function setResetToken($email, $token) {
        // Expire dans 1 heure
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $query = "UPDATE " . $this->table . " SET reset_token = :token, reset_expires = :expires WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

    public function resetPassword($token, $newPassword) {
        // Vérifier si token valide et non expiré
        $query = "SELECT id FROM " . $this->table . " WHERE reset_token = :token AND reset_expires > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($user = $stmt->fetch()) {
            if (!$this->isPasswordStrong($newPassword)) return "weak_password";
            
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            // On retire le token après usage
            $update = "UPDATE " . $this->table . " SET password = :pass, reset_token = NULL, reset_expires = NULL WHERE id = :id";
            $stmtUpdate = $this->conn->prepare($update);
            $stmtUpdate->bindParam(':pass', $hash);
            $stmtUpdate->bindParam(':id', $user['id']);
            return $stmtUpdate->execute();
        }
        return false; // Token invalide
    }

    public function getById($id) {
        $query = "SELECT id, username, email, avatar_url, created_at FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update($id, $email, $newPassword, $files) {
        $fields = [];
        $params = [':id' => $id];

        // Update Email
        if (!empty($email)) {
            $fields[] = "email = :email";
            $params[':email'] = $email;
        }

        // Update Password avec validation
        if (!empty($newPassword)) {
            if (!$this->isPasswordStrong($newPassword)) return "weak_password";
            $fields[] = "password = :password";
            $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Update Avatar
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
        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        $user = $this->getById($id);
        if (!empty($user['avatar_url'])) {
            $file = dirname(__DIR__) . '/' . $user['avatar_url'];
            if(file_exists($file)) unlink($file);
        }
        $stmt = $this->conn->prepare("SELECT image_url FROM games WHERE user_id = ?");
        $stmt->execute([$id]);
        while ($row = $stmt->fetch()) {
            if (!empty($row['image_url'])) {
                $file = dirname(__DIR__) . '/' . $row['image_url'];
                if(file_exists($file)) unlink($file);
            }
        }
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    private function uploadImage($file) {
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
            case 'jpg': case 'jpeg': $src = imagecreatefromjpeg($file["tmp_name"]); break;
            case 'png': $src = imagecreatefrompng($file["tmp_name"]); break;
            case 'webp': $src = imagecreatefromwebp($file["tmp_name"]); break;
            default: return null;
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
}
?>
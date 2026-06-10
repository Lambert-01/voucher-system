<?php
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function authenticate($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            return $user;
        }
        return false;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO users (full_name, username, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->execute([
            $data['full_name'],
            $data['username'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $passwordHash,
            $data['role'],
            $data['status'] ?? 'active'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function usernameExists($username, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
        }

        return (int)$stmt->fetchColumn() > 0;
    }
    
    public function getAll() {
        return $this->pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function resetPassword($id, $password) {
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}

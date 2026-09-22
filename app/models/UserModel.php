<?php
require_once __DIR__ . '/../core/Database.php';

class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        return $this->findByEmail($email);
    }

    public function findById($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getById($id) {
        return $this->findById($id);
    }

    public function create($nameOrData, $email = '', $password = '', $role = 'user') {
        if (!$this->db) return false;

        $department = null;

        if (is_array($nameOrData)) {
            $name = $nameOrData['name'] ?? '';
            $email = $nameOrData['email'] ?? '';
            $password = $nameOrData['password'] ?? '';
            $role = $nameOrData['role'] ?? 'user';
            $department = trim((string)($nameOrData['department'] ?? '')) ?: null;
        } else {
            $name = $nameOrData;
        }

        $role = in_array($role, ['user', 'admin', 'super_admin'], true) ? $role : 'user';

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';

        $stmt = $this->db->prepare("INSERT INTO users (name, email, department, password, avatar, role) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $department, $hash, $avatar, $role]);
    }

    public function getAllUsers() {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getAll() {
        return $this->getAllUsers();
    }

    public function updateRole($userId, $role) {
        if (!$this->db) return false;
        if (!in_array($role, ['user', 'admin', 'super_admin'], true)) return false;
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    public function delete($userId) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function getTotalCount() {
        if (!$this->db) return 0;
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}

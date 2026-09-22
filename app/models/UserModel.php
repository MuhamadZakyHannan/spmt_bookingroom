<?php
require_once __DIR__ . '/../core/Database.php';

class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        return $this->findByUsername($email);
    }

    public function getByUsername($username) {
        return $this->findByUsername($username);
    }

    public function getByEmail($email) {
        return $this->findByUsername($email);
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

    public function usernameExistsForOtherUser($username, $userId) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function emailExistsForOtherUser($email, $userId) {
        return $this->usernameExistsForOtherUser($email, $userId);
    }

    public function create($nameOrData, $email = '', $password = '', $role = 'user') {
        if (!$this->db) return false;

        $department = null;

        if (is_array($nameOrData)) {
            $name = $nameOrData['name'] ?? '';
            $email = $nameOrData['username'] ?? $nameOrData['email'] ?? '';
            $password = $nameOrData['password'] ?? '';
            $role = $nameOrData['role'] ?? 'user';
            $department = trim((string)($nameOrData['department'] ?? '')) ?: null;
        } else {
            $name = $nameOrData;
        }

        $role = in_array($role, ['user', 'admin', 'super_admin'], true) ? $role : 'user';

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';

        $stmt = $this->db->prepare("INSERT INTO users (name, username, email, department, password, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $email, $department, $hash, $avatar, $role]);
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

    public function updateAccount($userId, array $data) {
        if (!$this->db) return false;

        $role = in_array($data['role'] ?? '', ['user', 'admin', 'super_admin'], true)
            ? $data['role']
            : 'user';
        $department = trim((string)($data['department'] ?? '')) ?: null;
        $password = (string)($data['password'] ?? '');

        if ($password !== '') {
            $stmt = $this->db->prepare(
                "UPDATE users
                 SET name = ?, username = ?, email = ?, department = ?, role = ?, password = ?
                 WHERE id = ?"
            );
            $username = $data['username'] ?? $data['email'];
            return $stmt->execute([
                $data['name'],
                $username,
                $username,
                $department,
                $role,
                password_hash($password, PASSWORD_DEFAULT),
                $userId,
            ]);
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET name = ?, username = ?, email = ?, department = ?, role = ? WHERE id = ?"
        );
        $username = $data['username'] ?? $data['email'];
        return $stmt->execute([$data['name'], $username, $username, $department, $role, $userId]);
    }

    public function delete($userId) {
        if (!$this->db) return false;
        $documents = $this->documentFilesForUser((int) $userId);
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $deleted = $stmt->execute([$userId]);
        if ($deleted) $this->removeDocumentFiles($documents);
        return $deleted;
    }

    private function documentFilesForUser(int $userId): array {
        if ($userId <= 0) return [];
        try {
            $statement = $this->db->prepare(
                'SELECT d.stored_name FROM booking_documents d JOIN bookings b ON b.id = d.booking_id WHERE b.user_id = ?'
            );
            $statement->execute([$userId]);
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $exception) {
            error_log('Gagal membaca dokumen pengguna: ' . $exception->getMessage());
            return [];
        }
    }

    private function removeDocumentFiles(array $storedNames): void {
        if (!$storedNames) return;
        $service = new BookingDocumentService();
        foreach ($storedNames as $storedName) $service->remove((string) $storedName);
    }

    public function getTotalCount() {
        if (!$this->db) return 0;
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}

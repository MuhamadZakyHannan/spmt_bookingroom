<?php
require_once __DIR__ . '/../core/BaseModel.php';

class UserModel extends BaseModel {

    /** Mengambil data by username. */
    public function findByUsername($username) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /** Mengambil data by username. */
    public function getByUsername($username) {
        return $this->findByUsername($username);
    }

    /** Mengambil data by id. */
    public function findById($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Mengambil data by id. */
    public function getById($id) {
        return $this->findById($id);
    }

    /** Menjalankan proses username exists for other user pada user. */
    public function usernameExistsForOtherUser($username, $userId) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Membuat data user baru. */
    public function create($nameOrData, $email = '', $password = '', $role = 'user') {
        if (!$this->db) return false;

        $department = null;
        $username = '';

        if (is_array($nameOrData)) {
            $name = trim((string)($nameOrData['name'] ?? ''));
            $username = trim((string)($nameOrData['username'] ?? ''));
            if ($name === '' && $username !== '') {
                $name = $username;
            }
            $email = !empty($nameOrData['email']) ? $nameOrData['email'] : ($username . '@company.com');
            $password = $nameOrData['password'] ?? '';
            $role = $nameOrData['role'] ?? 'user';
            $department = trim((string)($nameOrData['department'] ?? '')) ?: null;
        } else {
            $name = trim((string)$nameOrData);
            $username = trim((string)$email);
            if ($name === '' && $username !== '') {
                $name = $username;
            }
            $email = str_contains($email, '@') ? $email : ($email . '@company.com');
        }

        $role = in_array($role, ['user', 'admin', 'super_admin'], true) ? $role : 'user';

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';

        $stmt = $this->db->prepare("INSERT INTO users (name, username, email, department, password, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $username, $email, $department, $hash, $avatar, $role]);
    }

    /** Mengambil data all users. */
    public function getAllUsers() {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    /** Mengambil seluruh data user. */
    public function getAll() {
        return $this->getAllUsers();
    }

    /** Menghitung jumlah user berdasarkan role. */
    public function countByRole(string $role): int {
        if (!$this->db) return 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    /** Memperbarui role. */
    public function updateRole($userId, $role) {
        if (!$this->db) return false;
        if (!in_array($role, ['user', 'admin', 'super_admin'], true)) return false;
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    /** Memperbarui account. */
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

    /** Menghapus data user beserta relasi terkait. */
    public function delete($userId) {
        if (!$this->db) return false;
        $documents = $this->documentFilesForUser((int) $userId);
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $deleted = $stmt->execute([$userId]);
        if ($deleted) $this->removeDocumentFiles($documents);
        return $deleted;
    }

    /** Menjalankan proses document files for user pada user. */
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

    /** Menghapus atau mereset document files. */
    private function removeDocumentFiles(array $storedNames): void {
        if (!$storedNames) return;
        $service = new BookingDocumentService();
        foreach ($storedNames as $storedName) $service->remove((string) $storedName);
    }

    /** Mengambil data total count. */
    public function getTotalCount() {
        if (!$this->db) return 0;
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}

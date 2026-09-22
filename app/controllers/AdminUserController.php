<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Mengelola akun pengguna dengan pembatasan khusus Super Admin.
 */
final class AdminUserController extends Controller
{
    private UserModel $users;

    /** Menyiapkan dependensi yang dibutuhkan oleh AdminUserController. */
    public function __construct()
    {
        $this->users = $this->model('UserModel');
    }

    /** Menampilkan halaman utama admin user. */
    public function index(): void
    {
        $this->requireAdmin();
        $error = '';
        if ($this->isPost()) {
            $this->validateCsrf('admin_users.php');
            $error = $this->handleAction((string) ($_POST['action'] ?? ''));
        }

        $this->view('admin/users', [
            'users' => $this->users->getAll(),
            'departments' => Organization::DEPARTMENTS,
            'error' => $error,
        ]);
    }

    /** Menangani proses action. */
    private function handleAction(string $action): string
    {
        if ($action === 'add') return $this->add();
        if ($action === 'edit') $this->edit();
        if ($action === 'update_role') $this->updateRole();
        if ($action === 'delete') $this->delete();
        return '';
    }

    /** Menambahkan data admin user. */
    private function add(): string
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = UsernamePolicy::normalize((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $department = trim((string) ($_POST['department'] ?? ''));
        $roleInput = trim((string) ($_POST['role'] ?? 'user'));
        $role = is_super_admin() && in_array($roleInput, ['user', 'admin'], true)
            ? $roleInput
            : 'user';

        if ($name === '' || $username === '' || $password === '' || $department === '') {
            return 'Nama, username, divisi, dan password wajib diisi!';
        }
        if (strlen($name) > 100) return 'Nama pengguna terlalu panjang.';
        if ($error = UsernamePolicy::validationError($username)) return $error;
        if (!Organization::isValidDepartment($department)) return 'Divisi yang dipilih tidak valid!';
        if ($error = PasswordPolicy::validationError($password)) return $error;
        if ($this->users->getByUsername($username)) return 'Username sudah digunakan!';

        $created = $this->users->create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'department' => $department,
            'role' => $role,
        ]);
        if (!$created) return 'Pengguna baru gagal ditambahkan.';
        set_flash('success', 'Pengguna baru berhasil ditambahkan.');
        $this->redirect('admin_users.php');
    }

    /** Menampilkan dan memproses perubahan admin user. */
    private function edit(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $target = $userId > 0 ? $this->users->getById($userId) : false;
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = UsernamePolicy::normalize((string) ($_POST['username'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $error = $this->editValidationError($target, $userId, $name, $username, $department, $password);
        if ($error !== null) {
            set_flash('danger', $error);
            $this->redirect('admin_users.php');
        }

        $requestedRole = trim((string) ($_POST['role'] ?? 'user'));
        $role = ($target['role'] ?? '') === 'super_admin'
            ? 'super_admin'
            : (in_array($requestedRole, ['user', 'admin'], true) ? $requestedRole : 'user');
        $updated = $this->users->updateAccount($userId, [
            'name' => $name,
            'username' => $username,
            'department' => $department,
            'role' => $role,
            'password' => $password,
        ]);
        set_flash($updated ? 'success' : 'danger', $updated
            ? 'Data akun berhasil diperbarui.'
            : 'Data akun gagal diperbarui.');
        $this->redirect('admin_users.php');
    }

    /** Memperbarui validation error. */
    private function editValidationError(
        $target,
        int $userId,
        string $name,
        string $username,
        string $department,
        string $password
    ): ?string {
        if (!is_super_admin()) return 'Hanya Super Admin yang dapat mengedit akun pengguna.';
        if (!$target) return 'Akun yang akan diedit tidak ditemukan.';
        if ($name === '' || $username === '' || $department === '') return 'Nama, username, dan divisi wajib diisi.';
        if (strlen($name) > 100) return 'Nama pengguna terlalu panjang.';
        if ($error = UsernamePolicy::validationError($username)) return $error;
        if (!Organization::isValidDepartment($department)) return 'Divisi yang dipilih tidak valid.';
        if ($password !== '' && ($error = PasswordPolicy::validationError($password))) return $error;
        if ($this->users->usernameExistsForOtherUser($username, $userId)) {
            return 'Username sudah digunakan oleh akun lain.';
        }
        return null;
    }

    /** Memperbarui role. */
    private function updateRole(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = trim((string) ($_POST['role'] ?? 'user'));
        $target = $userId > 0 ? $this->users->getById($userId) : false;
        if (!is_super_admin()) {
            set_flash('danger', 'Hanya Super Admin yang dapat mengubah role pengguna.');
        } elseif (!$target || $userId === (int) $_SESSION['user_id']) {
            set_flash('danger', 'Role akun tersebut tidak dapat diubah.');
        } elseif (($target['role'] ?? '') === 'super_admin') {
            set_flash('danger', 'Role Super Admin dilindungi dan tidak dapat diubah dari halaman ini.');
        } elseif (in_array($role, ['user', 'admin'], true)) {
            $this->users->updateRole($userId, $role);
            set_flash('success', 'Role pengguna berhasil diperbarui.');
        }
        $this->redirect('admin_users.php');
    }

    /** Menghapus data admin user beserta relasi terkait. */
    private function delete(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $target = $userId > 0 ? $this->users->getById($userId) : false;
        if (!is_super_admin()) {
            set_flash('danger', 'Hanya Super Admin yang dapat menghapus akun pengguna.');
        } elseif ($target && $userId !== (int) $_SESSION['user_id'] && ($target['role'] ?? '') !== 'super_admin') {
            $this->users->delete($userId);
            set_flash('success', 'Pengguna berhasil dihapus.');
        } else {
            set_flash('danger', 'Akun Super Admin atau akun yang sedang digunakan tidak dapat dihapus.');
        }
        $this->redirect('admin_users.php');
    }
}

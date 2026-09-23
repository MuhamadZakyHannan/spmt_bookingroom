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
            if ($error !== '') {
                set_flash('danger', $error);
                $this->redirect('admin_users.php');
            }
        }

        $existingUsers = $this->users->getAll();
        $dbDepts = array_filter(array_unique(array_map(function($u) {
            return trim((string)($u['department'] ?? ''));
        }, $existingUsers)));
        $departments = array_values(array_unique(array_merge(Organization::DEPARTMENTS, $dbDepts)));

        $this->view('admin/users', [
            'users' => $existingUsers,
            'departments' => $departments,
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
        if (!is_admin()) {
            return 'Hanya Administrator yang dapat menambahkan pengguna baru.';
        }

        $username = UsernamePolicy::normalize((string) ($_POST['username'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? '')) ?: $username;
        $password = (string) ($_POST['password'] ?? '');
        $department = Organization::normalizeDepartment((string) ($_POST['department'] ?? ''));
        $roleInput = trim((string) ($_POST['role'] ?? 'user'));
        $role = in_array($roleInput, ['user', 'admin', 'super_admin'], true)
            ? $roleInput
            : 'user';

        if ($username === '' || $password === '' || $department === '') {
            return 'Username, divisi, dan password wajib diisi!';
        }
        if (strlen($name) > 100) return 'Nama pengguna terlalu panjang.';
        if ($error = UsernamePolicy::validationError($username)) return $error;
        if (strlen($department) < 2 || strlen($department) > 100) return 'Nama divisi minimal 2 dan maksimal 100 karakter!';
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
        $username = UsernamePolicy::normalize((string) ($_POST['username'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? '')) ?: $username;
        $department = Organization::normalizeDepartment((string) ($_POST['department'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $error = $this->editValidationError($target, $userId, $name, $username, $department, $password);
        if ($error !== null) {
            set_flash('danger', $error);
            $this->redirect('admin_users.php');
        }

        $requestedRole = trim((string) ($_POST['role'] ?? 'user'));
        if (in_array($requestedRole, ['user', 'admin', 'super_admin'], true)) {
            if (($target['role'] ?? '') === 'super_admin' && $requestedRole !== 'super_admin' && $this->users->countByRole('super_admin') <= 1) {
                $role = 'super_admin';
            } else {
                $role = $requestedRole;
            }
        } else {
            $role = $target['role'] ?? 'user';
        }

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
        string &$name,
        string $username,
        string $department,
        string $password
    ): ?string {
        if (!is_admin()) return 'Hanya Administrator yang dapat mengedit akun pengguna.';
        if (!$target) return 'Akun yang akan diedit tidak ditemukan.';
        if ($name === '' && $username !== '') {
            $name = $username;
        }
        if ($name === '' || $username === '' || $department === '') return 'Nama/username dan divisi wajib diisi.';
        if (strlen($name) > 100) return 'Nama pengguna terlalu panjang.';
        if ($error = UsernamePolicy::validationError($username)) return $error;
        if (strlen($department) < 2 || strlen($department) > 100) return 'Nama divisi minimal 2 dan maksimal 100 karakter.';
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
        if (!is_admin()) {
            set_flash('danger', 'Hanya Administrator yang dapat mengubah role pengguna.');
        } elseif (!$target || $userId === (int) $_SESSION['user_id']) {
            set_flash('danger', 'Role akun Anda sendiri tidak dapat diubah.');
        } elseif (($target['role'] ?? '') === 'super_admin' && $role !== 'super_admin' && $this->users->countByRole('super_admin') <= 1) {
            set_flash('danger', 'Tidak dapat mengubah role Super Admin terakhir di sistem.');
        } elseif (in_array($role, ['user', 'admin', 'super_admin'], true)) {
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
        if (!is_admin()) {
            set_flash('danger', 'Hanya Administrator yang dapat menghapus akun pengguna.');
            $this->redirect('admin_users.php');
            return;
        }

        if (!$target) {
            set_flash('danger', 'Pengguna yang akan dihapus tidak ditemukan.');
            $this->redirect('admin_users.php');
            return;
        }

        if ($userId === (int) $_SESSION['user_id']) {
            set_flash('danger', 'Anda tidak dapat menghapus akun yang sedang Anda gunakan.');
            $this->redirect('admin_users.php');
            return;
        }

        if (($target['role'] ?? '') === 'super_admin' && $this->users->countByRole('super_admin') <= 1) {
            set_flash('danger', 'Tidak dapat menghapus Super Admin terakhir di sistem.');
            $this->redirect('admin_users.php');
            return;
        }

        $deleted = $this->users->delete($userId);
        if ($deleted) {
            set_flash('success', 'Pengguna "' . htmlspecialchars($target['name'] ?: $target['username']) . '" berhasil dihapus.');
        } else {
            set_flash('danger', 'Gagal menghapus pengguna.');
        }
        $this->redirect('admin_users.php');
    }
}

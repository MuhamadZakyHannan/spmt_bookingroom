<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/PasswordPolicy.php';
require_once __DIR__ . '/../core/UsernamePolicy.php';

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = $this->model('UserModel');
    }

    public function login() {
        if (is_logged_in()) {
            $this->redirect('dashboard.php');
        }

        $error = '';
        $username = '';

        if ($this->isPost()) {
            $this->validateCsrf('login.php');

            $username = UsernamePolicy::normalize((string) ($_POST['username'] ?? ''));
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = 'Harap isi username dan password!';
            } else {
                $user = $this->userModel->findByUsername($username);
                if ($user && password_verify($password, $user['password'])) {
                    // Prevent Session Fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_avatar'] = $user['avatar'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['department'] = $user['department'] ?? '';

                    $this->redirect('dashboard.php');
                } else {
                    $error = 'Username atau password yang Anda masukkan salah!';
                }
            }
        }

        $this->view('auth/login', [
            'error' => $error,
            'username' => $username
        ]);
    }

    public function register() {
        if (is_logged_in()) {
            $this->redirect('dashboard.php');
        }

        $error = '';
        $name = '';
        $username = '';

        if ($this->isPost()) {
            $this->validateCsrf('register.php');

            $name = trim($_POST['name'] ?? '');
            $username = UsernamePolicy::normalize((string) ($_POST['username'] ?? ''));
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if (empty($name) || empty($username) || empty($password)) {
                $error = 'Harap isi semua kolom bertanda bintang (*)!';
            } else if ($usernameError = UsernamePolicy::validationError($username)) {
                $error = $usernameError;
            } else if ($password !== $confirm_password) {
                $error = 'Konfirmasi password tidak cocok!';
            } else if ($passwordError = PasswordPolicy::validationError($password)) {
                $error = $passwordError;
            } else {
                $existing = $this->userModel->findByUsername($username);
                if ($existing) {
                    $error = 'Username tersebut sudah digunakan! Gunakan username lain.';
                } else {
                    if ($this->userModel->create([
                        'name' => $name,
                        'username' => $username,
                        'password' => $password,
                    ])) {
                        set_flash('success', 'Pendaftaran akun berhasil! Silakan login.');
                        $this->redirect('login.php');
                    } else {
                        $error = 'Gagal mendaftar, terjadi kesalahan sistem.';
                    }
                }
            }
        }

        $this->view('auth/register', [
            'error' => $error,
            'name' => $name,
            'username' => $username
        ]);
    }

    public function logout() {
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        set_flash('info', 'Anda telah berhasil keluar dari sistem.');
        $this->redirect('login.php');
    }
}

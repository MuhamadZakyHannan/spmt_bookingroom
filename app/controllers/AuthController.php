<?php
require_once __DIR__ . '/../core/Controller.php';

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
        $email = '';

        if ($this->isPost()) {
            $this->validateCsrf('login.php');

            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                $error = 'Harap isi email dan password!';
            } else {
                $user = $this->userModel->findByEmail($email);
                if ($user && password_verify($password, $user['password'])) {
                    // Prevent Session Fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_avatar'] = $user['avatar'];
                    $_SESSION['role'] = $user['role'];

                    $this->redirect('dashboard.php');
                } else {
                    $error = 'Email atau password yang Anda masukkan salah!';
                }
            }
        }

        $this->view('auth/login', [
            'error' => $error,
            'email' => $email
        ]);
    }

    public function register() {
        if (is_logged_in()) {
            $this->redirect('dashboard.php');
        }

        $error = '';
        $name = '';
        $email = '';

        if ($this->isPost()) {
            $this->validateCsrf('register.php');

            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                $error = 'Harap isi semua kolom bertanda bintang (*)!';
            } else if ($password !== $confirm_password) {
                $error = 'Konfirmasi password tidak cocok!';
            } else if (strlen($password) < 6) {
                $error = 'Password minimal harus 6 karakter!';
            } else {
                $existing = $this->userModel->findByEmail($email);
                if ($existing) {
                    $error = 'Email tersebut sudah terdaftar! Gunakan email lain.';
                } else {
                    if ($this->userModel->create($name, $email, $password)) {
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
            'email' => $email
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

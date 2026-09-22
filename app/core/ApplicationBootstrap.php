<?php

/**
 * Menyiapkan runtime, keamanan HTTP, sesi, dan koneksi aplikasi.
 */
final class ApplicationBootstrap
{
    /** Menjalankan seluruh proses bootstrap dan mengembalikan koneksi bersama. */
    public static function boot(): ?PDO
    {
        date_default_timezone_set('Asia/Jakarta');
        self::configureErrorHandling();
        self::sendSecurityHeaders();
        self::startWebSession();

        $connection = Database::getInstance()->getConnection();
        self::synchronizeAuthenticatedUser($connection);
        return $connection;
    }

    /** Mengaktifkan logging dan mengatur visibilitas error sesuai environment. */
    private static function configureErrorHandling(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', APP_DEBUG ? '1' : '0');
        ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
        ini_set('log_errors', '1');

        $logDirectory = dirname(APP_LOG_PATH);
        if ((is_dir($logDirectory) || @mkdir($logDirectory, 0700, true)) && is_writable($logDirectory)) {
            ini_set('error_log', APP_LOG_PATH);
        }
    }

    /** Mengirim header keamanan yang kompatibel dengan antarmuka aplikasi. */
    private static function sendSecurityHeaders(): void
    {
        if (headers_sent() || PHP_SAPI === 'cli') return;

        header_remove('X-Powered-By');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' data: https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
    }

    /** Membuka sesi web aman dan menerapkan batas waktu tidak aktif. */
    private static function startWebSession(): void
    {
        if (PHP_SAPI === 'cli' || session_status() !== PHP_SESSION_NONE) return;

        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        if (TRUST_PROXY_HEADERS && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $isHttps = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('MEETSPACESESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => SESSION_COOKIE_PATH,
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $lastActivity = (int) ($_SESSION['last_activity_at'] ?? 0);
        if (isset($_SESSION['user_id']) && $lastActivity > 0 && (time() - $lastActivity) > SESSION_IDLE_TIMEOUT) {
            session_unset();
            session_regenerate_id(true);
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => 'Sesi berakhir karena tidak ada aktivitas. Silakan masuk kembali.',
            ];
        }
        $_SESSION['last_activity_at'] = time();
    }

    /** Menyegarkan identitas dan role pengguna dari database setiap request web. */
    private static function synchronizeAuthenticatedUser(?PDO $connection): void
    {
        if (PHP_SAPI === 'cli' || !$connection || !isset($_SESSION['user_id'])) return;

        try {
            $statement = $connection->prepare(
                'SELECT id, name, username, email, department, avatar, role FROM users WHERE id = ? LIMIT 1'
            );
            $statement->execute([(int) $_SESSION['user_id']]);
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                session_unset();
                return;
            }

            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['department'] = $user['department'] ?? '';
            $_SESSION['user_avatar'] = $user['avatar'];
            $_SESSION['role'] = $user['role'];
        } catch (Throwable $exception) {
            error_log('Gagal menyinkronkan sesi pengguna: ' . $exception->getMessage());
        }
    }
}

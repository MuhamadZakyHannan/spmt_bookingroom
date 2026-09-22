<?php
// PHP Native Database Connection & Common Utilities
// Works out of the box on XAMPP (Apache + MySQL)

// Set Zona Waktu Default Indonesia (WIB / Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/app/core/Environment.php';
Environment::load(__DIR__ . '/.env');

if (!defined('APP_ENV')) define('APP_ENV', Environment::get('APP_ENV', 'local_lan'));
if (!defined('APP_DEBUG')) define('APP_DEBUG', Environment::bool('APP_DEBUG', false));
if (!defined('APP_ALLOW_REGISTRATION')) define('APP_ALLOW_REGISTRATION', Environment::bool('APP_ALLOW_REGISTRATION', false));
if (!defined('SESSION_IDLE_TIMEOUT')) define('SESSION_IDLE_TIMEOUT', Environment::int('SESSION_IDLE_TIMEOUT', 7200, 900, 86400));
if (!defined('SESSION_COOKIE_PATH')) define('SESSION_COOKIE_PATH', Environment::get('SESSION_COOKIE_PATH', '/Room_Booking_System/'));
if (!defined('TRUST_PROXY_HEADERS')) define('TRUST_PROXY_HEADERS', Environment::bool('TRUST_PROXY_HEADERS', false));
if (!defined('APP_LOG_PATH')) {
    $defaultLogPath = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'private'
        . DIRECTORY_SEPARATOR . 'Room_Booking_System'
        . DIRECTORY_SEPARATOR . 'logs'
        . DIRECTORY_SEPARATOR . 'application.log';
    define('APP_LOG_PATH', Environment::get('APP_LOG_PATH', $defaultLogPath));
}

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
$logDirectory = dirname(APP_LOG_PATH);
if ((is_dir($logDirectory) || @mkdir($logDirectory, 0700, true)) && is_writable($logDirectory)) {
    ini_set('error_log', APP_LOG_PATH);
}

// Security headers compatible with the local Tailwind/FullCalendar interface.
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' data: https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
}

// Database Connection Configuration Constants
if (!defined('DB_HOST')) define('DB_HOST', Environment::get('DB_HOST', 'localhost'));
if (!defined('DB_USER')) define('DB_USER', Environment::get('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', Environment::get('DB_PASS', ''));
if (!defined('DB_NAME')) define('DB_NAME', Environment::get('DB_NAME', 'meetspace_db'));
if (!defined('BOOKING_DOCUMENT_STORAGE')) {
    $defaultDocumentStorage = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'private'
        . DIRECTORY_SEPARATOR . 'Room_Booking_System'
        . DIRECTORY_SEPARATOR . 'booking-documents';
    define('BOOKING_DOCUMENT_STORAGE', Environment::get('BOOKING_DOCUMENT_STORAGE', $defaultDocumentStorage));
}

// Secure Session Initialization
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
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
        'samesite' => 'Lax'
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

require_once __DIR__ . '/app/core/Database.php';
$pdo = Database::getInstance()->getConnection();

// Sinkronkan identitas dan role dari database pada setiap request web.
// Perubahan role atau penghapusan akun berlaku segera tanpa menunggu logout.
if (PHP_SAPI !== 'cli' && $pdo && isset($_SESSION['user_id'])) {
    try {
        $sessionUserStatement = $pdo->prepare(
            'SELECT id, name, username, email, department, avatar, role FROM users WHERE id = ? LIMIT 1'
        );
        $sessionUserStatement->execute([(int) $_SESSION['user_id']]);
        $sessionUser = $sessionUserStatement->fetch(PDO::FETCH_ASSOC);

        if ($sessionUser) {
            $_SESSION['user_name'] = $sessionUser['name'];
            $_SESSION['user_username'] = $sessionUser['username'];
            $_SESSION['user_email'] = $sessionUser['email'];
            $_SESSION['department'] = $sessionUser['department'] ?? '';
            $_SESSION['user_avatar'] = $sessionUser['avatar'];
            $_SESSION['role'] = $sessionUser['role'];
        } else {
            session_unset();
        }
    } catch (Throwable $exception) {
        error_log('Gagal menyinkronkan sesi pengguna: ' . $exception->getMessage());
    }
}

// Helper Functions
/** Memeriksa apakah logged in terpenuhi. */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/** Memeriksa apakah admin terpenuhi. */
function is_admin()
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'], true);
}

/** Memeriksa apakah super admin terpenuhi. */
function is_super_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

/** Memvalidasi login. */
function require_login()
{
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header('Location: login.php');
        exit;
    }
}

/** Memvalidasi admin. */
function require_admin()
{
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak. Anda memerlukan hak akses Administrator.');
        header('Location: dashboard.php');
        exit;
    }
}

/** Menjalankan proses set flash pada fitur ini. */
function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/** Menampilkan atau menutup flash. */
function display_flash()
{
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $msg = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);

        $bg_colors = [
            'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'danger' => 'bg-rose-50 text-rose-800 border-rose-200',
            'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
            'info' => 'bg-blue-50 text-blue-800 border-blue-200'
        ];
        $icon_colors = [
            'success' => 'text-emerald-500 fa-check-circle',
            'danger' => 'text-rose-500 fa-exclamation-circle',
            'warning' => 'text-amber-500 fa-exclamation-triangle',
            'info' => 'text-blue-500 fa-info-circle'
        ];

        $cls = $bg_colors[$type] ?? $bg_colors['info'];
        $icon = $icon_colors[$type] ?? $icon_colors['info'];

        echo "<div class='p-4 mb-4 rounded-xl border flex items-center justify-between shadow-sm {$cls}' id='flashAlert'>
                <div class='flex items-center gap-3'>
                    <i class='fas {$icon} text-lg'></i>
                    <span class='text-sm font-medium'>" . htmlspecialchars($msg) . "</span>
                </div>
                <button type='button' onclick=\"document.getElementById('flashAlert').remove()\" class='text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-lg'>
                    <i class='fas fa-times'></i>
                </button>
              </div>";
    }
}

/** Memformat date. */
function format_date($date_str)
{
    if (!$date_str) return '-';
    $time = strtotime($date_str);
    $months = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $day = date('d', $time);
    $month = $months[(int)date('m', $time)];
    $year = date('Y', $time);
    return "$day $month $year";
}

/** Memformat time. */
function format_time($time_str)
{
    if (!$time_str) return '-';
    return date('H:i', strtotime($time_str));
}

/** Memeriksa apakah booking expired terpenuhi. */
function is_booking_expired(array $booking): bool
{
    return ($booking['status'] ?? '') === 'cancelled'
        && ($booking['status_reason'] ?? '') === BookingLifecycleService::REASON_EXPIRED;
}

/** Menjalankan proses booking status label pada fitur ini. */
function booking_status_label(array $booking): string
{
    if (is_booking_expired($booking)) return 'Kedaluwarsa';
    return match ($booking['status'] ?? '') {
        'pending' => 'Menunggu Persetujuan',
        'confirmed' => 'Disetujui',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan / Ditolak',
        default => 'Tidak Diketahui',
    };
}

/**
 * Menghasilkan versi aset dari waktu modifikasi file agar browser tidak
 * memakai CSS atau JavaScript lama setelah aplikasi diperbarui.
 */
function asset_version($relative_path)
{
    $relative_path = ltrim(str_replace('\\', '/', (string) $relative_path), '/');
    if ($relative_path === '' || strpos($relative_path, '..') !== false) {
        return '1';
    }

    $absolute_path = __DIR__ . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);

    return is_file($absolute_path) ? (string) filemtime($absolute_path) : '1';
}

/**
 * CSRF Protection Helper Functions
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Menjalankan proses csrf field pada fitur ini. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Memvalidasi csrf token. */
function verify_csrf_token($token = null)
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Autoloader untuk class di folder app/core, app/models, app/controllers
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/core/' . $class . '.php',
        __DIR__ . '/app/services/' . $class . '.php',
        __DIR__ . '/app/models/' . $class . '.php',
        __DIR__ . '/app/controllers/' . $class . '.php'
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

<?php
// PHP Native Database Connection & Common Utilities
// Works out of the box on XAMPP (Apache + MySQL)

// Set Zona Waktu Default Indonesia (WIB / Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Security Headers
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
}

// Optional .env File Loader
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $envLine) {
        $envLine = trim($envLine);
        if ($envLine === '' || strpos($envLine, '#') === 0) continue;
        if (strpos($envLine, '=') !== false) {
            list($envKey, $envVal) = explode('=', $envLine, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($envKey, $_ENV)) {
                $_ENV[$envKey] = $envVal;
                putenv("$envKey=$envVal");
            }
        }
    }
}

// Database Connection Configuration Constants
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'meetspace_db');

// Attendance Feature Configuration
if (!defined('ATTENDANCE_CHECK_IN_EARLY_MINUTES')) {
    $attendanceEarlyEnv = filter_var(
        getenv('ATTENDANCE_CHECK_IN_EARLY_MINUTES'),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    );
    define('ATTENDANCE_CHECK_IN_EARLY_MINUTES', $attendanceEarlyEnv !== false ? $attendanceEarlyEnv : 15);
}
if (!defined('ATTENDANCE_GRACE_MINUTES')) {
    $attendanceGraceEnv = filter_var(
        getenv('ATTENDANCE_GRACE_MINUTES'),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    );
    define('ATTENDANCE_GRACE_MINUTES', $attendanceGraceEnv !== false ? $attendanceGraceEnv : 15);
}

// Secure Session Initialization
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$db_host = DB_HOST;
$db_user = DB_USER;
$db_pass = DB_PASS;
$db_name = DB_NAME;

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    $pdo = null; // Will trigger setup warning in UI if database is not created yet
}

// Helper Functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak. Anda memerlukan hak akses Administrator.');
        header('Location: dashboard.php');
        exit;
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function display_flash() {
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

function format_date($date_str) {
    if (!$date_str) return '-';
    $time = strtotime($date_str);
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $day = date('d', $time);
    $month = $months[(int)date('m', $time)];
    $year = date('Y', $time);
    return "$day $month $year";
}

function format_time($time_str) {
    if (!$time_str) return '-';
    return date('H:i', strtotime($time_str));
}

/**
 * CSRF Protection Helper Functions
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token = null) {
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
?>

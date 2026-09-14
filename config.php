<?php
// PHP Native Database Connection & Common Utilities
// Works out of the box on XAMPP (Apache + MySQL)

// Set Zona Waktu Default Indonesia (WIB / Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "meetspace_db";

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
?>

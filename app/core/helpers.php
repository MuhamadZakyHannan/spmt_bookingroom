<?php

/** Memeriksa apakah pengguna sudah masuk. */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Memeriksa apakah pengguna mempunyai akses Administrator. */
function is_admin(): bool
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'], true);
}

/** Memeriksa apakah pengguna mempunyai akses Super Administrator. */
function is_super_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

/** Mengarahkan pengguna yang belum masuk ke halaman login. */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header('Location: login.php');
        exit;
    }
}

/** Membatasi halaman agar hanya dapat digunakan Administrator. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak. Anda memerlukan hak akses Administrator.');
        header('Location: dashboard.php');
        exit;
    }
}

/** Menyimpan satu pesan flash ke dalam sesi. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/** Merender dan menghapus pesan flash aktif. */
function display_flash(): void
{
    if (!isset($_SESSION['flash'])) return;

    $type = (string) ($_SESSION['flash']['type'] ?? 'info');
    $message = (string) ($_SESSION['flash']['message'] ?? '');
    unset($_SESSION['flash']);

    $backgrounds = [
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'danger' => 'bg-rose-50 text-rose-800 border-rose-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200',
    ];
    $icons = [
        'success' => 'text-emerald-500 fa-check-circle',
        'danger' => 'text-rose-500 fa-exclamation-circle',
        'warning' => 'text-amber-500 fa-exclamation-triangle',
        'info' => 'text-blue-500 fa-info-circle',
    ];

    $classes = $backgrounds[$type] ?? $backgrounds['info'];
    $icon = $icons[$type] ?? $icons['info'];
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo "<div class='p-4 mb-4 rounded-xl border flex items-center justify-between shadow-sm {$classes}' id='flashAlert'>
            <div class='flex items-center gap-3'>
                <i class='fas {$icon} text-lg'></i>
                <span class='text-sm font-medium'>{$safeMessage}</span>
            </div>
            <button type='button' onclick=\"document.getElementById('flashAlert').remove()\" class='text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-lg'>
                <i class='fas fa-times'></i>
            </button>
          </div>";
}

/** Mengubah tanggal database menjadi format tanggal Indonesia. */
function format_date($dateString): string
{
    if (!$dateString) return '-';

    $timestamp = strtotime((string) $dateString);
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
        'Desember',
    ];

    return date('d', $timestamp)
        . ' ' . $months[(int) date('m', $timestamp)]
        . ' ' . date('Y', $timestamp);
}

/** Mengubah nilai waktu menjadi format jam dan menit. */
function format_time($timeString): string
{
    if (!$timeString) return '-';
    return date('H:i', strtotime((string) $timeString));
}

/** Menentukan apakah booking dibatalkan karena melewati batas persetujuan. */
function is_booking_expired(array $booking): bool
{
    return ($booking['status'] ?? '') === 'cancelled'
        && ($booking['status_reason'] ?? '') === BookingLifecycleService::REASON_EXPIRED;
}

/** Menghasilkan label status booking yang dapat dibaca pengguna. */
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

/** Menghasilkan versi aset dari waktu modifikasi file untuk cache busting. */
function asset_version($relativePath): string
{
    $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
    if ($relativePath === '' || str_contains($relativePath, '..')) return '1';

    $absolutePath = MEETSPACE_ROOT . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';
}

/** Mengambil atau membuat token CSRF untuk sesi aktif. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

/** Membuat input tersembunyi berisi token CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/** Memvalidasi token CSRF dari argumen atau request aktif. */
function verify_csrf_token($token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;

    return hash_equals((string) $_SESSION['csrf_token'], (string) $token);
}

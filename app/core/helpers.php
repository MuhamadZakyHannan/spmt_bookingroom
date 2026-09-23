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

    echo "<div class='p-4 mb-4 rounded-xl border flex items-center justify-between shadow-sm transition-all duration-500 ease-in-out {$classes}' id='flashAlert'>
            <div class='flex items-center gap-3'>
                <i class='fas {$icon} text-lg'></i>
                <span class='text-sm font-medium'>{$safeMessage}</span>
            </div>
            <button type='button' onclick=\"dismissFlashAlert()\" class='text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-lg' aria-label='Tutup'>
                <i class='fas fa-times'></i>
            </button>
          </div>
          <script>
            function dismissFlashAlert() {
                var el = document.getElementById('flashAlert');
                if (el) {
                    el.style.transition = 'opacity 0.4s ease, transform 0.4s ease, margin 0.4s ease, max-height 0.4s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-6px)';
                    setTimeout(function() { if (el && el.parentNode) el.remove(); }, 400);
                }
            }
            setTimeout(dismissFlashAlert, 2000);
          </script>";
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
    if (($booking['status'] ?? '') === 'confirmed' && ($booking['status_reason'] ?? '') === BookingLifecycleService::REASON_RELOCATED_BY_ADMIN) {
        return 'Disetujui (Dialihkan)';
    }
    if (($booking['status'] ?? '') === 'cancelled' && ($booking['status_reason'] ?? '') === BookingLifecycleService::REASON_CANCELLED_BY_ADMIN) {
        return 'Dibatalkan oleh Admin';
    }

    return match ($booking['status'] ?? '') {
        'pending' => 'Menunggu Persetujuan',
        'confirmed' => 'Disetujui',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan / Ditolak',
        default => 'Tidak Diketahui',
    };
}

/** Menghasilkan markup badge status booking yang konsisten di seluruh antarmuka. */
function booking_status_badge(array $booking, string $context = 'web'): string
{
    $status = $booking['status'] ?? '';
    $reason = $booking['status_reason'] ?? '';
    $notes = trim((string) ($booking['admin_notes'] ?? ''));
    $safeNotes = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');

    if ($context === 'print') {
        if (is_booking_expired($booking)) {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-300">KEDALUWARSA</span>';
        }
        if ($status === 'confirmed' && $reason === BookingLifecycleService::REASON_RELOCATED_BY_ADMIN) {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">DIALIHKAN</span>';
        }
        if ($status === 'confirmed') {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">DISETUJUI</span>';
        }
        if ($status === 'completed') {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300">SELESAI</span>';
        }
        if ($status === 'pending') {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">MENUNGGU</span>';
        }
        if ($status === 'cancelled' && $reason === BookingLifecycleService::REASON_CANCELLED_BY_ADMIN) {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BATAL (ADMIN)</span>';
        }
        return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BATAL</span>';
    }

    if (is_booking_expired($booking)) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600">'
            . '<i class="fas fa-clock-rotate-left text-slate-500"></i> Kedaluwarsa'
            . '</span>';
    }
    if ($status === 'confirmed' && $reason === BookingLifecycleService::REASON_RELOCATED_BY_ADMIN) {
        $titleAttr = $safeNotes !== '' ? ' title="' . $safeNotes . '"' : '';
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800"' . $titleAttr . '>'
            . '<i class="fas fa-arrows-split-up-and-left text-amber-600"></i> Dialihkan'
            . '</span>';
    }
    if ($status === 'confirmed') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">'
            . '<i class="fas fa-check-circle text-emerald-600"></i> Disetujui'
            . '</span>';
    }
    if ($status === 'completed') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">'
            . '<i class="fas fa-check-double text-blue-600"></i> Selesai'
            . '</span>';
    }
    if ($status === 'pending') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 animate-pulse shadow-sm">'
            . '<i class="fas fa-hourglass-half text-amber-600"></i> Menunggu'
            . '</span>';
    }
    if ($status === 'cancelled' && $reason === BookingLifecycleService::REASON_CANCELLED_BY_ADMIN) {
        $titleAttr = $safeNotes !== '' ? ' title="' . $safeNotes . '"' : '';
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800"' . $titleAttr . '>'
            . '<i class="fas fa-ban text-rose-600"></i> Dibatalkan Admin'
            . '</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">'
        . '<i class="fas fa-times-circle text-rose-600"></i> Dibatalkan'
        . '</span>';
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

<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Menghentikan pengujian presentasi ketika struktur modular tidak terpenuhi.
 */
function expectPresentation(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$root = dirname(__DIR__);
$header = (string) file_get_contents($root . '/app/views/layouts/header.php');
$shell = (string) file_get_contents($root . '/public/js/site-shell.js');
$utilities = (string) file_get_contents($root . '/public/js/ui-utils.js');

expectPresentation(
    str_contains($header, 'public/js/theme-init.js')
        && str_contains($header, 'public/js/site-shell.js')
        && !str_contains($header, 'function toggleSidebar()'),
    'Tema dan shell navigasi tidak lagi ditulis inline dalam layout.'
);
expectPresentation(
    str_contains($header, "require __DIR__ . '/_runtime_config.php'")
        && is_file($root . '/app/views/layouts/_runtime_config.php'),
    'Konfigurasi runtime dipisahkan sebagai partial layout.'
);
expectPresentation(
    str_contains($shell, 'window.toggleSidebar')
        && str_contains($shell, 'initializeAdminNotifications'),
    'Interaksi shell dan notifikasi admin berada dalam modul JavaScript khusus.'
);
expectPresentation(
    str_contains($utilities, 'Object.freeze({ escapeHtml, highlightText })'),
    'Utility escaping dan highlight tersedia dari satu sumber bersama.'
);
expectPresentation(
    str_contains($header, 'class="w-full px-3 sm:px-4 lg:px-6"')
        && str_contains($header, 'absolute left-1/2 flex -translate-x-1/2')
        && str_contains($header, 'max-w-[4.5rem]')
        && str_contains($header, 'ml-auto flex shrink-0')
        && substr_count($header, 'aria-label="Ke dashboard"') === 1
        && str_contains($header, 'theme-toggle-button flex h-9 w-9')
        && str_contains($shell, "button.setAttribute('aria-label', label)"),
    'Navbar menampilkan logo tengah adaptif dan kontrol tema berupa ikon aksesibel tanpa tabrakan mobile.'
);

$sharedUtilityViews = [
    'app/views/admin/bookings.php',
    'app/views/admin/history.php',
    'app/views/admin/rooms.php',
    'app/views/admin/users.php',
    'app/views/booking/my_bookings.php',
    'app/views/dashboard/index.php',
    'app/views/display/index.php',
    'app/views/display/lobby.php',
];
$allUseSharedUtilities = true;
foreach ($sharedUtilityViews as $view) {
    $allUseSharedUtilities = $allUseSharedUtilities
        && str_contains((string) file_get_contents($root . '/' . $view), 'MeetSpaceUI');
}
expectPresentation($allUseSharedUtilities, 'View interaktif menggunakan utility presentasi bersama.');

echo PHP_EOL . 'Hasil: 6 lulus, 0 gagal.' . PHP_EOL;

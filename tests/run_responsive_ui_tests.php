<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Menghentikan audit bila kontrak responsif antartampilan terlepas.
 */
function expectResponsiveUi(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$root = dirname(__DIR__);
$read = static fn(string $path): string => (string) file_get_contents($root . '/' . $path);
$header = $read('app/views/layouts/header.php');
$styles = $read('src/input.css');
$calendar = $read('app/views/calendar/index.php');
$dashboard = $read('app/views/dashboard/index.php');

expectResponsiveUi(
    str_contains($header, 'width=device-width, initial-scale=1.0')
        && str_contains($header, 'overflow-x-hidden'),
    'Layout utama menetapkan viewport dan mencegah overflow halaman.'
);
expectResponsiveUi(
    str_contains($header, 'w-[min(20rem,calc(100vw-1rem))]')
        && str_contains($header, 'px-3 py-4 sm:px-6 sm:py-8'),
    'Drawer dan ruang konten mengikuti lebar layar kecil.'
);
expectResponsiveUi(
    str_contains($styles, '.responsive-table-shell')
        && str_contains($styles, '-webkit-overflow-scrolling: touch'),
    'Tabel lebar memakai primitive scroll sentuh bersama.'
);
expectResponsiveUi(
    str_contains($styles, '.responsive-modal-panel')
        && str_contains($styles, '100dvh'),
    'Modal dibatasi oleh tinggi viewport dinamis.'
);
expectResponsiveUi(
    str_contains($styles, "input:not([type='checkbox']):not([type='radio'])")
        && str_contains($styles, 'font-size: 16px'),
    'Kontrol formulir seluler mencegah zoom otomatis browser.'
);

$tableViews = [
    'app/views/admin/bookings.php',
    'app/views/admin/displays.php',
    'app/views/admin/history.php',
    'app/views/admin/rooms.php',
    'app/views/admin/statistics.php',
    'app/views/admin/users.php',
];
$tablesUseSharedShell = true;
foreach ($tableViews as $view) {
    $content = $read($view);
    if ($view === 'app/views/admin/bookings.php' && file_exists(__DIR__ . '/../app/views/admin/partials/_booking_table.php')) {
        $content .= $read('app/views/admin/partials/_booking_table.php');
    }
    $tablesUseSharedShell = $tablesUseSharedShell
        && str_contains($content, 'responsive-table-shell');
}
expectResponsiveUi($tablesUseSharedShell, 'Seluruh tabel admin memakai primitive responsif bersama.');

expectResponsiveUi(
    str_contains($dashboard, 'responsive-modal-panel')
        && str_contains($dashboard, 'flex-col-reverse')
        && str_contains($dashboard, 'sm:w-auto'),
    'Modal booking memiliki panel dan aksi yang adaptif di ponsel.'
);
expectResponsiveUi(
    str_contains($calendar, "initialView: window.matchMedia('(max-width: 639px)').matches ? 'listMonth' : 'dayGridMonth'")
        && str_contains($calendar, 'sm:min-h-[520px]'),
    'Kalender memilih agenda pada ponsel dan grid bulan pada layar lebih besar.'
);

echo PHP_EOL . 'Hasil: 8 lulus, 0 gagal.' . PHP_EOL;

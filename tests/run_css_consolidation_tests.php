<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Menghentikan audit ketika aturan konsolidasi CSS tidak terpenuhi.
 */
function expectCssConsolidation(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

/**
 * Menggabungkan isi seluruh file PHP di direktori produksi yang diberikan.
 */
function combinedPhpSource(string $path): string
{
    $source = '';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $source .= "\n" . file_get_contents($file->getPathname());
        }
    }
    return $source;
}

$root = dirname(__DIR__);
$views = combinedPhpSource($root . '/app/views');
$controllers = combinedPhpSource($root . '/app/controllers');
$inputCss = (string) file_get_contents($root . '/src/input.css');

expectCssConsolidation(
    !str_contains($views, '<style') && !str_contains($controllers, '<style'),
    'View dan controller tidak lagi menyimpan blok CSS mandiri.'
);
expectCssConsolidation(
    !preg_match('/style=["\']\s*(?:width|background(?:-color)?)\s*:/i', $views),
    'Dekorasi dinamis menggunakan kelas bersama dan CSS custom property.'
);
expectCssConsolidation(
    str_contains($inputCss, '/* Kiosk display shared styles */')
        && str_contains($inputCss, '/* FullCalendar presentation */'),
    'CSS kiosk dan kalender berada dalam pipeline sumber utama.'
);
expectCssConsolidation(
    str_contains($inputCss, '.data-progress-bar')
        && str_contains($inputCss, '.data-color-swatch'),
    'Nilai visual berbasis data memakai primitive CSS bersama.'
);
expectCssConsolidation(
    is_file($root . '/public/css/tailwind.min.css'),
    'Artefak CSS produksi tersedia dari satu proses build.'
);

echo PHP_EOL . 'Hasil: 5 lulus, 0 gagal.' . PHP_EOL;

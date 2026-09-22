<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

/**
 * Menghentikan pengujian kontrak API ketika struktur tidak konsisten.
 */
function expectApiArchitecture(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$root = dirname(__DIR__);
$requestSource = (string) file_get_contents($root . '/app/core/ApiRequest.php');
$responseSource = (string) file_get_contents($root . '/app/core/ApiResponse.php');

expectApiArchitecture(
    (new ReflectionClass(ApiRequest::class))->isFinal()
        && (new ReflectionClass(ApiResponse::class))->isFinal(),
    'Request guard dan JSON response memiliki implementasi final bersama.'
);
expectApiArchitecture(
    str_contains($requestSource, 'requireMethod')
        && str_contains($requestSource, 'requireLogin')
        && str_contains($requestSource, 'requireAdmin')
        && str_contains($requestSource, 'requireCsrf'),
    'Guard API mencakup method, login, role Administrator, dan CSRF.'
);
expectApiArchitecture(
    str_contains($responseSource, "'success' => false")
        && str_contains($responseSource, "'code' => \$code")
        && str_contains($responseSource, 'Cache-Control: no-store'),
    'Error API memiliki bentuk dan cache policy yang konsisten.'
);

$endpointFiles = glob($root . '/api/*.php') ?: [];
$allEndpointSource = '';
foreach ($endpointFiles as $endpointFile) {
    $allEndpointSource .= (string) file_get_contents($endpointFile);
}
$controllerApiSource = (string) file_get_contents($root . '/app/controllers/CalendarController.php')
    . (string) file_get_contents($root . '/app/controllers/DisplayController.php');

expectApiArchitecture(
    !str_contains($allEndpointSource, 'echo json_encode'),
    'Endpoint tidak lagi merakit response JSON secara manual.'
);
expectApiArchitecture(
    substr_count($allEndpointSource . $controllerApiSource, 'ApiRequest::requireMethod') >= 6,
    'Seluruh endpoint utama membatasi method HTTP secara eksplisit.'
);
expectApiArchitecture(
    str_contains($allEndpointSource, 'ApiRequest::requireAdmin')
        && str_contains($allEndpointSource, 'ApiRequest::requireLogin'),
    'Endpoint privat membedakan autentikasi pengguna dan Administrator.'
);
expectApiArchitecture(
    str_contains($allEndpointSource, 'ApiRequest::requireCsrf'),
    'Mutasi notifikasi Administrator dilindungi CSRF.'
);
expectApiArchitecture(
    !str_contains($allEndpointSource . $controllerApiSource, "'message' => 'Unauthorized'"),
    'Pesan error generik lama sudah dihapus dari kontrak API.'
);

echo PHP_EOL . 'Hasil: 8 lulus, 0 gagal.' . PHP_EOL;

<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/AdminDashboardService.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$passed = 0;
$failed = 0;

function expectDashboard($condition, string $message): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$message}\n";
        return;
    }

    $failed++;
    echo "[FAIL] {$message}\n";
}

if (!$pdo) {
    fwrite(STDERR, "Database tidak tersedia; pengujian dashboard admin dilewati.\n");
    exit(2);
}

try {
    $snapshot = (new AdminDashboardService($pdo))->getSnapshot();

    expectDashboard(
        isset($snapshot['pending_count'])
            && isset($snapshot['pending_requests'])
            && isset($snapshot['room_displays']),
        'Snapshot menyediakan data persetujuan dan monitoring display.'
    );
    expectDashboard(
        is_array($snapshot['pending_requests']) && count($snapshot['pending_requests']) <= 3,
        'Sidebar membatasi daftar persetujuan maksimal tiga pengajuan.'
    );

    $roomCount = (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    expectDashboard(
        is_array($snapshot['room_displays']) && count($snapshot['room_displays']) === $roomCount,
        'Monitoring display memuat satu status untuk setiap ruangan.'
    );

    $validDisplayStatuses = ['online', 'offline', 'unconfigured'];
    foreach ($snapshot['room_displays'] as $room) {
        expectDashboard(
            in_array($room['display_status'], $validDisplayStatuses, true),
            'Status display ruangan valid: ' . $room['code']
        );
    }

    $admin_dashboard = $snapshot;
    ob_start();
    require __DIR__ . '/../app/views/dashboard/_admin_sidebar.php';
    $sidebarHtml = ob_get_clean();
    expectDashboard(
        str_contains($sidebarHtml, 'Persetujuan Peminjaman')
            && str_contains($sidebarHtml, 'Monitoring Display'),
        'Sidebar admin merender kedua panel tambahan.'
    );
} catch (Throwable $e) {
    $failed++;
    echo '[FAIL] Snapshot dashboard: ' . $e->getMessage() . "\n";
}

echo "\nHasil: {$passed} lulus, {$failed} gagal.\n";
exit($failed > 0 ? 1 : 0);

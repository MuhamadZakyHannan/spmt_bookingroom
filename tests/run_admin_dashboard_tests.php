<?php

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
    $summaryKeys = [
        'pending_requests',
        'ongoing_meetings',
        'awaiting_check_in',
        'no_show_today',
        'available_rooms',
        'total_rooms',
    ];

    expectDashboard(
        empty(array_diff($summaryKeys, array_keys($snapshot['summary'] ?? []))),
        'Snapshot menyediakan seluruh indikator operasional.'
    );
    expectDashboard(
        is_array($snapshot['pending_requests'] ?? null)
            && count($snapshot['pending_requests']) <= 6,
        'Antrean tindakan dibatasi maksimal enam pengajuan.'
    );
    expectDashboard(
        is_array($snapshot['room_monitoring'] ?? null)
            && count($snapshot['room_monitoring']) === (int)$snapshot['summary']['total_rooms'],
        'Monitoring memuat satu status untuk setiap ruangan.'
    );

    $validRoomStatuses = ['available', 'occupied', 'awaiting_check_in', 'maintenance'];
    $validDisplayStatuses = ['online', 'offline', 'unconfigured'];
    foreach ($snapshot['room_monitoring'] as $room) {
        expectDashboard(
            in_array($room['operational_status'], $validRoomStatuses, true),
            'Status operasional ruangan valid: ' . $room['code']
        );
        expectDashboard(
            in_array($room['display_status'], $validDisplayStatuses, true),
            'Status display ruangan valid: ' . $room['code']
        );
    }
} catch (Throwable $e) {
    $failed++;
    echo '[FAIL] Snapshot dashboard: ' . $e->getMessage() . "\n";
}

echo "\nHasil: {$passed} lulus, {$failed} gagal.\n";
exit($failed > 0 ? 1 : 0);

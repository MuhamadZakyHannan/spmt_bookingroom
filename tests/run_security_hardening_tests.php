<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

/**
 * Menghentikan pengujian keamanan ketika kondisi yang diharapkan tidak terpenuhi.
 */
function expectSecurity(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$pdo = Database::getInstance()->getConnection();
if (!$pdo) throw new RuntimeException('Koneksi database tidak tersedia.');

$username = 'security-test-' . bin2hex(random_bytes(6));
$ipAddress = '127.0.0.77';
$otherIpAddress = '127.0.0.78';
$reference = new DateTimeImmutable('2030-01-01 10:00:00');
$service = new LoginThrottleService($pdo);

$pdo->beginTransaction();
try {
    $initial = $service->status($username, $ipAddress, $reference);
    expectSecurity(!$initial['blocked'] && $initial['attempts'] === 0, 'Identitas baru belum dibatasi.');

    for ($attempt = 0; $attempt < LoginThrottleService::MAX_ATTEMPTS - 1; $attempt++) {
        $service->recordFailure($username, $ipAddress, $reference->add(new DateInterval('PT' . $attempt . 'S')));
    }
    $beforeLimit = $service->status($username, $ipAddress, $reference->add(new DateInterval('PT10S')));
    expectSecurity(!$beforeLimit['blocked'], 'Empat kegagalan masih berada di bawah batas login.');

    $service->recordFailure($username, $ipAddress, $reference->add(new DateInterval('PT11S')));
    $blocked = $service->status($username, $ipAddress, $reference->add(new DateInterval('PT12S')));
    expectSecurity($blocked['blocked'] && $blocked['retry_after'] > 0, 'Percobaan kelima memblokir login selama jendela keamanan.');

    $differentClient = $service->status($username, $otherIpAddress, $reference->add(new DateInterval('PT12S')));
    expectSecurity(!$differentClient['blocked'], 'Pembatasan tidak memblokir alamat IP lain.');

    $service->clear($username, $ipAddress);
    expectSecurity(!$service->status($username, $ipAddress, $reference)['blocked'], 'Login berhasil dapat membersihkan riwayat kegagalan.');

    $service->recordFailure($username, $ipAddress, $reference);
    $storedIdentity = $pdo->query('SELECT username_hash, ip_hash FROM login_attempts ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    expectSecurity(
        strlen((string) $storedIdentity['username_hash']) === 64
            && strlen((string) $storedIdentity['ip_hash']) === 64
            && $storedIdentity['username_hash'] !== $username,
        'Rate limiter hanya menyimpan fingerprint identitas satu arah.'
    );
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

expectSecurity(APP_ALLOW_REGISTRATION === false, 'Pendaftaran mandiri nonaktif secara default.');

$configSource = file_get_contents(__DIR__ . '/../config.php');
expectSecurity(
    str_contains($configSource, "session_name('MEETSPACESESSID')")
        && str_contains($configSource, "'path' => SESSION_COOKIE_PATH")
        && str_contains($configSource, 'SESSION_IDLE_TIMEOUT'),
    'Session web memakai nama, path, dan batas waktu khusus aplikasi.'
);

$htaccess = file_get_contents(__DIR__ . '/../.htaccess');
expectSecurity(
    str_contains($htaccess, 'node_modules|scripts|src|tests'),
    'Folder internal diblokir oleh konfigurasi Apache.'
);

$authSource = file_get_contents(__DIR__ . '/../app/controllers/AuthController.php');
expectSecurity(
    str_contains($authSource, 'LoginThrottleService')
        && str_contains($authSource, 'APP_ALLOW_REGISTRATION')
        && str_contains($authSource, "unset(\$_SESSION['csrf_token'])"),
    'Controller autentikasi menerapkan throttle, kebijakan registrasi, dan rotasi CSRF.'
);

echo PHP_EOL . 'Hasil: 10 lulus, 0 gagal.' . PHP_EOL;

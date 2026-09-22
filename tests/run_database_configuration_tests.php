<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

/**
 * Menghentikan pengujian konfigurasi database ketika kondisi tidak terpenuhi.
 */
function expectDatabaseConfiguration(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$temporaryEnvironment = tempnam(sys_get_temp_dir(), 'meetspace-env-');
if ($temporaryEnvironment === false) {
    throw new RuntimeException('File environment sementara tidak dapat dibuat.');
}

$prefix = 'MEETSPACE_TEST_' . strtoupper(bin2hex(random_bytes(4)));
$preservedKey = $prefix . '_PRESERVED';
$loadedKey = $prefix . '_LOADED';
$booleanKey = $prefix . '_BOOLEAN';
$integerKey = $prefix . '_INTEGER';

try {
    putenv($preservedKey . '=system-value');
    $_ENV[$preservedKey] = 'system-value';
    file_put_contents(
        $temporaryEnvironment,
        $preservedKey . "=file-value\n"
            . $loadedKey . "=loaded-value\n"
            . $booleanKey . "=yes\n"
            . $integerKey . "=99999\n"
    );

    Environment::load($temporaryEnvironment);
    expectDatabaseConfiguration(
        Environment::get($preservedKey) === 'system-value',
        'File .env tidak menimpa konfigurasi yang diberikan oleh sistem.'
    );
    expectDatabaseConfiguration(
        Environment::get($loadedKey) === 'loaded-value',
        'Environment loader membaca pasangan KEY=VALUE yang valid.'
    );
    expectDatabaseConfiguration(
        Environment::bool($booleanKey) && Environment::int($integerKey, 60, 10, 3600) === 3600,
        'Nilai boolean dan integer dinormalisasi serta dibatasi dengan aman.'
    );
} finally {
    @unlink($temporaryEnvironment);
    foreach ([$preservedKey, $loadedKey, $booleanKey, $integerKey] as $key) {
        putenv($key);
        unset($_ENV[$key]);
    }
}

$database = Database::getInstance();
$connection = $database->getConnection();
if (!$connection) throw new RuntimeException('Koneksi database tidak tersedia.');

expectDatabaseConfiguration(
    $pdo === $connection && $database === Database::getInstance(),
    'Seluruh aplikasi menggunakan instance dan koneksi database yang sama.'
);
expectDatabaseConfiguration(
    $connection->query('SELECT @@session.time_zone')->fetchColumn() === '+07:00',
    'Timezone session database ditetapkan ke WIB (+07:00).'
);
expectDatabaseConfiguration(
    (bool) $connection->getAttribute(PDO::ATTR_EMULATE_PREPARES) === false,
    'PDO menggunakan native prepared statements.'
);

$schemaSource = (string) file_get_contents(__DIR__ . '/../database/schema.sql');
expectDatabaseConfiguration(
    str_contains($schemaSource, 'CREATE TABLE `users`')
        && str_contains($schemaSource, 'CREATE TABLE `bookings`')
        && !preg_match('/\bINSERT\s+INTO\b/i', $schemaSource)
        && !preg_match('/AUTO_INCREMENT\s*=\s*\d+/i', $schemaSource),
    'Skema final lengkap dan tidak membawa data maupun urutan ID operasional.'
);

$migrationSource = (string) file_get_contents(__DIR__ . '/../scripts/apply_migrations.php');
$htaccessSource = (string) file_get_contents(__DIR__ . '/../.htaccess');
expectDatabaseConfiguration(
    str_contains($migrationSource, 'GET_LOCK')
        && str_contains($migrationSource, "hash('sha256', \$sql)")
        && str_contains($migrationSource, 'RELEASE_LOCK')
        && str_contains($htaccessSource, 'app|database|docs'),
    'Runner migrasi memiliki lock, checksum, pelepasan lock, dan skema diblokir dari web.'
);

echo PHP_EOL . 'Hasil: 8 lulus, 0 gagal.' . PHP_EOL;

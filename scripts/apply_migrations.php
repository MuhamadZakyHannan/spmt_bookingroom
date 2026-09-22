<?php

require_once __DIR__ . '/../config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!$pdo) {
    fwrite(STDERR, "Koneksi database tidak tersedia.\n");
    exit(1);
}

$lockName = 'meetspace_schema_migrations';
$lockStatement = $pdo->prepare('SELECT GET_LOCK(?, 10)');
$lockStatement->execute([$lockName]);
if ((int) $lockStatement->fetchColumn() !== 1) {
    fwrite(STDERR, "Migrasi sedang dijalankan oleh proses lain.\n");
    exit(1);
}

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(190) PRIMARY KEY,
            checksum CHAR(64) NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $pdo->exec('ALTER TABLE schema_migrations ADD COLUMN IF NOT EXISTS checksum CHAR(64) NULL AFTER migration');

    $files = glob(__DIR__ . '/../migrations/*.sql') ?: [];
    sort($files, SORT_STRING);
    $findMigration = $pdo->prepare('SELECT checksum FROM schema_migrations WHERE migration = ? LIMIT 1');
    $recordMigration = $pdo->prepare(
        'INSERT INTO schema_migrations (migration, checksum) VALUES (?, ?)'
    );
    $saveChecksum = $pdo->prepare(
        'UPDATE schema_migrations SET checksum = ? WHERE migration = ? AND checksum IS NULL'
    );

    foreach ($files as $file) {
        $name = basename($file);
        $sql = trim((string) file_get_contents($file));
        if ($sql === '') {
            echo "[EMPTY] {$name}\n";
            continue;
        }
        $checksum = hash('sha256', $sql);

        $findMigration->execute([$name]);
        $storedChecksum = $findMigration->fetchColumn();
        if ($storedChecksum !== false) {
            if ($storedChecksum !== null && !hash_equals((string) $storedChecksum, $checksum)) {
                throw new RuntimeException("Migrasi {$name} berubah setelah diterapkan.");
            }
            if ($storedChecksum === null) {
                $saveChecksum->execute([$checksum, $name]);
            }
            echo "[SKIP] {$name}\n";
            continue;
        }

        $pdo->exec($sql);
        $recordMigration->execute([$name, $checksum]);
        echo "[OK] {$name}\n";
    }

    echo "Migrasi database selesai.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, '[FAIL] ' . $exception->getMessage() . PHP_EOL);
    $migrationFailed = true;
} finally {
    $releaseStatement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $releaseStatement->execute([$lockName]);
}

if (!empty($migrationFailed)) {
    exit(1);
}

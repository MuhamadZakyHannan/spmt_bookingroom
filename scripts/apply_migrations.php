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

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(190) PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$files = glob(__DIR__ . '/../migrations/*.sql') ?: [];
sort($files, SORT_STRING);
$isApplied = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE migration = ?');
$record = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');

foreach ($files as $file) {
    $name = basename($file);
    $isApplied->execute([$name]);
    if ((int) $isApplied->fetchColumn() > 0) {
        echo "[SKIP] {$name}\n";
        continue;
    }

    $sql = trim((string) file_get_contents($file));
    if ($sql === '') {
        echo "[EMPTY] {$name}\n";
        continue;
    }

    try {
        $pdo->exec($sql);
        $record->execute([$name]);
        echo "[OK] {$name}\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, "[FAIL] {$name}: {$exception->getMessage()}\n");
        exit(1);
    }
}

echo "Migrasi database selesai.\n";

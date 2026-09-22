<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

/**
 * Menghentikan pengujian fondasi model ketika kondisi tidak terpenuhi.
 */
function expectModelArchitecture(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

if (!$pdo) throw new RuntimeException('Koneksi database tidak tersedia.');

$modelClasses = [
    BookingModel::class,
    BookingDocumentModel::class,
    DisplayModel::class,
    NotificationModel::class,
    RoomModel::class,
    UserModel::class,
];

foreach ($modelClasses as $modelClass) {
    expectModelArchitecture(
        is_subclass_of($modelClass, BaseModel::class),
        $modelClass . ' menggunakan fondasi model bersama.'
    );
}

$connectionProperty = new ReflectionProperty(BaseModel::class, 'db');
$injectedModels = [
    new BookingModel($pdo),
    new BookingDocumentModel($pdo),
    new DisplayModel($pdo),
    new NotificationModel($pdo),
    new RoomModel($pdo),
    new UserModel($pdo),
];

foreach ($injectedModels as $model) {
    expectModelArchitecture(
        $connectionProperty->getValue($model) === $pdo,
        get_class($model) . ' menerima koneksi PDO melalui constructor injection.'
    );
}

echo PHP_EOL . 'Hasil: 12 lulus, 0 gagal.' . PHP_EOL;

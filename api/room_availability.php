<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/RoomAvailabilityService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function availabilityResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    availabilityResponse(405, ['success' => false, 'error' => 'Metode tidak diizinkan.']);
}

if (! is_logged_in()) {
    availabilityResponse(401, ['success' => false, 'error' => 'Sesi login telah berakhir. Silakan masuk kembali.']);
}

$date = trim((string) ($_GET['date'] ?? ''));
$startTime = trim((string) ($_GET['start_time'] ?? ''));
$endTime = trim((string) ($_GET['end_time'] ?? ''));
$attendeesCount = filter_var($_GET['attendees_count'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 100],
]);

$dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
$dateIsValid = $dateValue && $dateValue->format('Y-m-d') === $date;
$timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';

if (! $dateIsValid || $date < date('Y-m-d')) {
    availabilityResponse(422, ['success' => false, 'error' => 'Tanggal pemesanan tidak valid atau sudah lewat.']);
}

if (! preg_match($timePattern, $startTime) || ! preg_match($timePattern, $endTime) || $endTime <= $startTime) {
    availabilityResponse(422, ['success' => false, 'error' => 'Rentang waktu pemesanan tidak valid.']);
}

if ($attendeesCount === false) {
    availabilityResponse(422, ['success' => false, 'error' => 'Jumlah peserta harus antara 1 dan 100 orang.']);
}

try {
    $service = new RoomAvailabilityService();
    $availability = $service->getAvailability($date, $startTime, $endTime, (int) $attendeesCount);
    availabilityResponse(200, array_merge(['success' => true], $availability));
} catch (Throwable $exception) {
    error_log('Room availability error: ' . $exception->getMessage());
    availabilityResponse(500, ['success' => false, 'error' => 'Ketersediaan ruangan belum dapat diperiksa.']);
}

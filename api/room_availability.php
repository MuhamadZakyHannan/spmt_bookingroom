<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/RoomAvailabilityService.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

ApiRequest::requireMethod('GET');
ApiRequest::requireLogin();

$date = trim((string) ($_GET['date'] ?? ''));
$startTime = trim((string) ($_GET['start_time'] ?? ''));
$endTime = trim((string) ($_GET['end_time'] ?? ''));
$attendeesCount = filter_var($_GET['attendees_count'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 100],
]);
$excludeBookingId = 0;
if (isset($_GET['exclude_booking_id']) && $_GET['exclude_booking_id'] !== '') {
    $validatedExcludeId = filter_var($_GET['exclude_booking_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if ($validatedExcludeId === false) {
        ApiResponse::error('Booking pengecualian tidak valid.', 422, 'invalid_excluded_booking');
    }

    $excludedBooking = (new BookingModel())->getById((int) $validatedExcludeId);
    $canExclude = $excludedBooking && (
        (is_admin() && in_array($excludedBooking['status'], ['pending', 'confirmed'], true))
        || ($excludedBooking['status'] === 'pending'
            && (int) $excludedBooking['user_id'] === (int) $_SESSION['user_id'])
    );
    if (!$canExclude) {
        ApiResponse::error('Anda tidak memiliki izin untuk mengecualikan booking ini.', 403, 'excluded_booking_forbidden');
    }
    $excludeBookingId = (int) $validatedExcludeId;
}

$dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
$dateIsValid = $dateValue && $dateValue->format('Y-m-d') === $date;
$timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';

if (! $dateIsValid || $date < date('Y-m-d')) {
    ApiResponse::error('Tanggal pemesanan tidak valid atau sudah lewat.', 422, 'invalid_date');
}

if (! preg_match($timePattern, $startTime) || ! preg_match($timePattern, $endTime) || $endTime <= $startTime) {
    ApiResponse::error('Rentang waktu pemesanan tidak valid.', 422, 'invalid_time_range');
}

if ($attendeesCount === false) {
    ApiResponse::error('Jumlah peserta harus antara 1 dan 100 orang.', 422, 'invalid_attendee_count');
}

try {
    $service = new RoomAvailabilityService();
    $availability = $service->getAvailability(
        $date,
        $startTime,
        $endTime,
        (int) $attendeesCount,
        $excludeBookingId
    );
    ApiResponse::send(array_merge(['success' => true], $availability));
} catch (Throwable $exception) {
    error_log('Room availability error: ' . $exception->getMessage());
    ApiResponse::error('Ketersediaan ruangan belum dapat diperiksa.', 500, 'availability_failed');
}

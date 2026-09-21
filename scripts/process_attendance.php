<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$bookingModel = new BookingModel();
$result = $bookingModel->processAutomaticAttendanceTransitions();

echo sprintf(
    "Attendance diproses: %d no-show, %d auto check-out.\n",
    count($result['no_show'] ?? []),
    count($result['checked_out'] ?? [])
);

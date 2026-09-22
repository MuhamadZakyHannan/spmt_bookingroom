<?php
/**
 * API Endpoint for Lobby Display Live Status Polling
 * Returns JSON of confirmed bookings for today without triggering page reload.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

ApiRequest::requireMethod('GET');

$bookingModel = new BookingModel();
$todayBookings = $bookingModel->getTodayBookings();
$currentTime = date('H:i');

// Filter HANYA confirmed yang belum selesai (end_time > currentTime)
$activeBookings = array_values(array_filter($todayBookings, function($b) use ($currentTime) {
    if ($b['status'] !== 'confirmed') return false;
    $end5 = substr($b['end_time'], 0, 5);
    return $currentTime < $end5;
}));

// Urutkan berdasarkan start_time
usort($activeBookings, function($a, $b) {
    return strcmp(substr($a['start_time'], 0, 5), substr($b['start_time'], 0, 5));
});

ApiResponse::send([
    'success' => true,
    'server_time' => date('H:i:s'),
    'server_date' => date('Y-m-d'),
    'current_time_hhmm' => $currentTime,
    'total_active' => count($activeBookings),
    'bookings' => $activeBookings
]);

<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Menampilkan riwayat booking beserta filter dan ringkasannya.
 */
final class AdminHistoryController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $filters = [
            'start_date' => trim((string) ($_GET['start_date'] ?? '')),
            'end_date' => trim((string) ($_GET['end_date'] ?? '')),
            'room_id' => (int) ($_GET['room_id'] ?? 0),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'search' => trim((string) ($_GET['search'] ?? '')),
        ];
        $summary = $this->model('BookingModel')->getBookingHistorySummary($filters);
        $this->view('admin/history', [
            'summary' => $summary,
            'bookings' => $summary['bookings'],
            'rooms' => $this->model('RoomModel')->getAllRooms(),
            'filters' => $filters,
        ]);
    }
}

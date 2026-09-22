<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Menyiapkan data statistik pemakaian ruang untuk administrator.
 */
final class AdminStatisticsController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $filters = [
            'period' => trim((string) ($_GET['period'] ?? 'this_year')),
            'start_date' => trim((string) ($_GET['start_date'] ?? '')),
            'end_date' => trim((string) ($_GET['end_date'] ?? '')),
            'room_id' => (int) ($_GET['room_id'] ?? 0),
        ];
        $statistics = $this->model('BookingModel')->getStatisticsData($filters);
        $this->view('admin/statistics', [
            'stats' => $statistics,
            'kpi' => $statistics['kpi'],
            'rooms' => $this->model('RoomModel')->getAllRooms(),
            'filters' => $filters,
        ]);
    }
}

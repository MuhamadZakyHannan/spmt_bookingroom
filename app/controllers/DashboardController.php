<?php
require_once __DIR__ . '/../core/Controller.php';

class DashboardController extends Controller {
    private $roomModel;
    private $bookingModel;
    private $userModel;

    public function __construct() {
        $this->roomModel = $this->model('RoomModel');
        $this->bookingModel = $this->model('BookingModel');
        $this->userModel = $this->model('UserModel');
    }

    public function index() {
        $this->requireAuth();

        $search = trim($_GET['search'] ?? '');
        $status_filter = trim($_GET['status'] ?? '');
        $capacity_filter = (int)($_GET['min_capacity'] ?? 0);

        $total_rooms = $this->roomModel->getTotalRoomsCount();
        $available_rooms = $this->roomModel->getAvailableRoomsCount();
        $active_bookings_today = $this->bookingModel->getTodayActiveBookingsCount();
        $total_users = $this->userModel->getTotalCount();

        $rooms = $this->roomModel->getAllRooms($search, $status_filter, $capacity_filter);
        $room_suggestions = $this->roomModel->getAllRooms();
        $today_bookings = $this->bookingModel->getTodayBookings();

        $active_rooms = $this->roomModel->getActiveRooms();

        $this->view('dashboard/index', [
            'total_rooms' => $total_rooms,
            'available_rooms' => $available_rooms,
            'active_bookings_today' => $active_bookings_today,
            'total_users' => $total_users,
            'rooms' => $rooms,
            'active_rooms' => $active_rooms,
            'room_suggestions' => $room_suggestions,
            'today_bookings' => $today_bookings,
            'search' => $search,
            'status_filter' => $status_filter,
            'capacity_filter' => $capacity_filter
        ]);
    }
}

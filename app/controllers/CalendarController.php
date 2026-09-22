<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/CalendarPresentation.php';

class CalendarController extends Controller {
    private $roomModel;
    private $bookingModel;

    public function __construct() {
        $this->roomModel = $this->model('RoomModel');
        $this->bookingModel = $this->model('BookingModel');
    }

    public function index() {
        $this->requireAuth();

        $room_filter = (int)($_GET['room_id'] ?? 0);
        $rooms = CalendarPresentation::decorateRooms($this->roomModel->getAllRooms());

        $this->view('calendar/index', [
            'rooms' => $rooms,
            'room_filter' => $room_filter
        ]);
    }

    public function eventsApi() {
        ApiRequest::requireMethod('GET');
        ApiRequest::requireLogin();

        $room_filter = (int)($_GET['room_id'] ?? 0);
        $bookings = $this->bookingModel->getCalendarEvents($room_filter);

        $events = [];
        foreach ($bookings as $b) {
            $start = $b['date'] . 'T' . $b['start_time'];
            $end = $b['date'] . 'T' . $b['end_time'];

            $color = CalendarPresentation::roomColor((int)$b['room_id']);

            $events[] = [
                'id' => $b['id'],
                'title' => $b['room_name'] . ': ' . $b['title'],
                'start' => $start,
                'end' => $end,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'room_id' => (int)$b['room_id'],
                    'room' => $b['room_name'],
                    'user' => $b['user_name'],
                    'title' => $b['title'],
                    'purpose' => $b['purpose'],
                    'attendees' => $b['attendees_count'],
                    'status' => $b['status'],
                    'time' => format_time($b['start_time']) . ' - ' . format_time($b['end_time']),
                    'date_formatted' => format_date($b['date'])
                ]
            ];
        }

        ApiResponse::send($events);
    }
}

<?php
require_once __DIR__ . '/../core/Controller.php';

class DisplayController extends Controller {
    private $displayModel;
    private $roomModel;

    public function __construct() {
        $this->displayModel = $this->model('DisplayModel');
        $this->roomModel = $this->model('RoomModel');
    }

    public function index($tokenOrRoom = null) {
        $this->show($tokenOrRoom);
    }

    public function show($tokenOrRoom = null) {
        $token = trim($_GET['token'] ?? '');
        $roomId = (int)($_GET['room'] ?? 0);

        if (empty($token) && !empty($tokenOrRoom)) {
            if (is_numeric($tokenOrRoom)) {
                $roomId = (int)$tokenOrRoom;
            } else {
                $token = $tokenOrRoom;
            }
        }

        $display = null;
        $room = null;

        if (!empty($token)) {
            $display = $this->displayModel->getByToken($token);
            if ($display) {
                $roomId = $display['room_id'];
                $this->displayModel->touchLastActive($display['id']);
            }
        }

        if (!$display && $roomId > 0) {
            $room = $this->roomModel->getById($roomId);
            $display = $this->displayModel->getByRoomId($roomId);
        }

        // Fallback jika tidak ada parameter sama sekali: ambil display pertama
        if (!$display && !$room) {
            $allDisplays = $this->displayModel->getAllDisplays();
            if (!empty($allDisplays)) {
                $display = $allDisplays[0];
                $roomId = $display['room_id'];
            } else {
                $allRooms = $this->roomModel->getAllRooms();
                if (!empty($allRooms)) {
                    $room = $allRooms[0];
                    $roomId = $room['id'];
                }
            }
        }

        if ($display && empty($room)) {
            $room = $this->roomModel->getById($roomId);
        }

        if (!$room) {
            echo "<div style='font-family: sans-serif; padding: 40px; text-align: center; color: #64748b;'>";
            echo "<h2>Ruangan Tidak Ditemukan</h2>";
            echo "<p>Token monitor atau ID ruangan tidak valid. Silakan hubungi Administrator.</p>";
            echo "<a href='dashboard.php' style='color: #2563eb; text-decoration: underline;'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }

        $liveData = $this->displayModel->getRoomLiveStatus($roomId);
        $allDisplays = $this->displayModel->getAllDisplays();
        $allRooms = $this->roomModel->getAllRooms();

        $this->view('display/index', [
            'display' => $display,
            'room' => $room,
            'liveData' => $liveData,
            'allDisplays' => $allDisplays,
            'allRooms' => $allRooms,
            'currentToken' => $display ? $display['display_token'] : '',
            'currentRoomId' => $roomId
        ]);
    }

    public function apiStatus() {
        header('Content-Type: application/json');
        
        $token = trim($_GET['token'] ?? '');
        $roomId = (int)($_GET['room'] ?? 0);

        if (!empty($token)) {
            $display = $this->displayModel->getByToken($token);
            if ($display) {
                $roomId = $display['room_id'];
                $this->displayModel->touchLastActive($display['id']);
            }
        }

        if ($roomId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Room or token required']);
            exit;
        }

        $liveData = $this->displayModel->getRoomLiveStatus($roomId);

        if (!$liveData) {
            echo json_encode(['success' => false, 'message' => 'Room data not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $liveData,
            'server_time' => date('H:i:s'),
            'server_date' => date('Y-m-d')
        ]);
        exit;
    }

    public function lobby() {
        $bookingModel = $this->model('BookingModel');
        $todayBookings = $bookingModel->getTodayBookings();
        $currentTime = date('H:i');

        // Filter HANYA booking confirmed yang MASIH BERLANGSUNG atau AKAN DATANG
        $activeBookings = array_values(array_filter($todayBookings, function($b) use ($currentTime) {
            if ($b['status'] !== 'confirmed') {
                return false;
            }
            $end5 = substr($b['end_time'], 0, 5);
            return $currentTime < $end5;
        }));

        // Urutkan berdasarkan jam mulai (start_time)
        usort($activeBookings, function($a, $b) {
            return strcmp(substr($a['start_time'], 0, 5), substr($b['start_time'], 0, 5));
        });

        $totalActiveSchedule = count($activeBookings);
        $activeNowCount = 0;

        foreach ($activeBookings as $b) {
            $start5 = substr($b['start_time'], 0, 5);
            $end5 = substr($b['end_time'], 0, 5);
            if ($currentTime >= $start5 && $currentTime < $end5) {
                $activeNowCount++;
            }
        }

        $this->view('display/lobby', [
            'activeBookings' => $activeBookings,
            'totalActiveSchedule' => $totalActiveSchedule,
            'activeNowCount' => $activeNowCount,
            'currentTime' => $currentTime
        ]);
    }
}

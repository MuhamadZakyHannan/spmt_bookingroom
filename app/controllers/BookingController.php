<?php
require_once __DIR__ . '/../core/Controller.php';

class BookingController extends Controller {
    private $roomModel;
    private $bookingModel;

    public function __construct() {
        $this->roomModel = $this->model('RoomModel');
        $this->bookingModel = $this->model('BookingModel');
    }

    public function create() {
        $this->requireAuth();

        $selected_room_id = (int)($_GET['room_id'] ?? 0);
        $user_name = '';
        $user_dept = '';
        $title = '';
        $date = date('Y-m-d');
        $start_time = '09:00';
        $end_time = '10:00';
        $purpose = '';
        $attendees_count = 1;
        $error = '';

        $rooms = $this->roomModel->getActiveRooms();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                      || (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1');

            $room_id = (int)($_POST['room_id'] ?? 0);
            $user_name = trim($_POST['user_name'] ?? '');
            $user_dept = trim($_POST['user_dept'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $start_time = trim($_POST['start_time'] ?? '');
            $end_time = trim($_POST['end_time'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');
            $attendees_count = (int)($_POST['attendees_count'] ?? 1);

            if (!$room_id || empty($title) || empty($date) || empty($start_time) || empty($end_time) || empty($user_name) || empty($user_dept)) {
                $error = 'Harap isi semua kolom wajib (*)!';
            } else if (strtotime($end_time) <= strtotime($start_time)) {
                $error = 'Waktu selesai harus lebih lambat dari waktu mulai!';
            } else if (strtotime($date) < strtotime(date('Y-m-d'))) {
                $error = 'Tanggal pemesanan tidak boleh di masa lalu!';
            } else {
                $room_info = $this->roomModel->getById($room_id);

                if ($room_info && $attendees_count > $room_info['capacity']) {
                    $error = 'Jumlah peserta (' . $attendees_count . ' orang) melebihi kapasitas maksimum ' . $room_info['name'] . ' (' . $room_info['capacity'] . ' orang).';
                } else {
                    $conflict = $this->bookingModel->checkConflict($room_id, $date, $start_time, $end_time);

                    if ($conflict) {
                        $error = 'Gagal! Ruangan ini sudah dipesan pada waktu tersebut untuk agenda "' . htmlspecialchars($conflict['title']) . '" (' . format_time($conflict['start_time']) . ' - ' . format_time($conflict['end_time']) . '). Silakan pilih jadwal atau ruangan lain.';
                    } else {
                        // Khusus role admin, langsung confirmed tanpa perlu persetujuan
                        $isAdmin = is_admin();
                        $status = $isAdmin ? 'confirmed' : 'pending';

                        $data = [
                            'user_id' => $_SESSION['user_id'],
                            'user_name' => $user_name,
                            'user_dept' => $user_dept,
                            'room_id' => $room_id,
                            'title' => $title,
                            'date' => $date,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'purpose' => $purpose,
                            'attendees_count' => $attendees_count,
                            'status' => $status
                        ];

                        if ($this->bookingModel->create($data)) {
                            $msg = $isAdmin 
                                ? 'Pemesanan ruangan oleh Admin berhasil dibuat dan langsung terkonfirmasi ke jadwal!' 
                                : 'Pengajuan booking berhasil dikirim! Status saat ini menunggu persetujuan (approval) dari Administrator.';
                            set_flash('success', $msg);

                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'success' => true,
                                    'message' => $msg,
                                    'redirect' => 'my_bookings.php'
                                ]);
                                exit;
                            }
                            $this->redirect('my_bookings.php');
                        } else {
                            $error = 'Gagal menyimpan pemesanan, terjadi kesalahan database.';
                        }
                    }
                }
            }

            if ($isAjax && !empty($error)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $error
                ]);
                exit;
            }
        }

        $this->view('booking/create', [
            'rooms' => $rooms,
            'selected_room_id' => $selected_room_id,
            'user_name' => $user_name,
            'user_dept' => $user_dept,
            'title' => $title,
            'date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'purpose' => $purpose,
            'attendees_count' => $attendees_count,
            'error' => $error
        ]);
    }

    public function myBookings() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            if ($booking_id > 0) {
                if ($this->bookingModel->cancel($booking_id, $_SESSION['user_id'], is_admin())) {
                    set_flash('success', 'Pemesanan telah berhasil dibatalkan.');
                } else {
                    set_flash('danger', 'Gagal membatalkan pemesanan.');
                }
            }
            $this->redirect('my_bookings.php');
        }

        $my_bookings = $this->bookingModel->getByUserId($_SESSION['user_id']);

        $this->view('booking/my_bookings', [
            'my_bookings' => $my_bookings
        ]);
    }
}

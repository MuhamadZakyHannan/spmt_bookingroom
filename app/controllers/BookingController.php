<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/SawService.php';

class BookingController extends Controller {
    private $roomModel;
    private $bookingModel;
    private $notificationModel;

    public function __construct() {
        $this->roomModel = $this->model('RoomModel');
        $this->bookingModel = $this->model('BookingModel');
        $this->notificationModel = $this->model('NotificationModel');
    }

    public function create() {
        $this->requireAuth();

        $selected_room_id = (int)($_GET['room_id'] ?? 0);
        $user_name = $_SESSION['user_name'] ?? '';
        $user_dept = $_SESSION['department'] ?? '';
        $title = '';
        $requestedDate = trim($_GET['date'] ?? '');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)
            && strtotime($requestedDate) >= strtotime(date('Y-m-d'))
            ? $requestedDate
            : date('Y-m-d');
        $start_time = '09:00';
        $end_time = '10:00';
        $purpose = '';
        $attendees_count = 1;
        $error = '';

        // Tampilkan seluruh ruangan agar status perawatan juga terlihat jelas
        // sebagai tidak dapat dipilih pada pemeriksa ketersediaan.
        $rooms = $this->roomModel->getAllRooms();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf('booking.php');

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
            $activity_type = trim($_POST['activity_type'] ?? 'internal_divisi');
            if (!array_key_exists($activity_type, SawService::ACTIVITY_TYPES)) {
                $activity_type = 'internal_divisi';
            }

            $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $dateIsValid = $dateValue && $dateValue->format('Y-m-d') === $date;
            $timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';

            if (!$room_id || empty($title) || empty($date) || empty($start_time) || empty($end_time) || empty($user_name) || empty($user_dept)) {
                $error = 'Harap isi semua kolom wajib (*)!';
            } else if (!$dateIsValid || $date < date('Y-m-d')) {
                $error = 'Tanggal pemesanan tidak valid atau sudah lewat!';
            } else if (!preg_match($timePattern, $start_time) || !preg_match($timePattern, $end_time) || $end_time <= $start_time) {
                $error = 'Waktu selesai harus lebih lambat dari waktu mulai!';
            } else if ($attendees_count < 1 || $attendees_count > 100) {
                $error = 'Jumlah peserta harus antara 1 dan 100 orang.';
            } else {
                $isAdmin = is_admin();
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
                    'activity_type' => $activity_type,
                    'attendees_count' => $attendees_count,
                ];

                $result = $this->bookingModel->createWithSchedulePolicy($data, $isAdmin);

                if ($result['success']) {
                    $status = $result['status'];
                    if (!empty($result['pending_conflict'])) {
                        $msg = 'Pengajuan berhasil dicatat sebagai Pending. Ada pengajuan lain pada jadwal yang sama; Administrator akan menentukan prioritas menggunakan metode SAW.';
                    } else {
                        $msg = $status === 'confirmed'
                            ? 'Pemesanan ruangan oleh Admin berhasil dibuat dan langsung terkonfirmasi ke jadwal!'
                            : 'Pengajuan booking berhasil dikirim! Status saat ini menunggu persetujuan Administrator.';
                    }

                    if ($status === 'pending') {
                        $this->notificationModel->createForPendingBooking((int) $result['booking_id']);
                    }
                    set_flash('success', $msg);

                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => true,
                            'message' => $msg,
                            'redirect' => 'my_bookings.php'
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $this->redirect('my_bookings.php');
                } else {
                    $reason = $result['reason'] ?? 'database_error';
                    if ($reason === 'confirmed_conflict') {
                        $conflict = $result['conflict'];
                        $error = 'Ruangan sudah terkonfirmasi untuk jadwal '
                            . substr($conflict['start_time'], 0, 5) . '–' . substr($conflict['end_time'], 0, 5)
                            . '. Pilih ruangan atau waktu lain.';
                    } elseif ($reason === 'maintenance') {
                        $error = 'Ruangan sedang dalam perawatan dan belum dapat dipesan.';
                    } elseif ($reason === 'insufficient_capacity') {
                        $room = $result['room'];
                        $error = 'Jumlah peserta melebihi kapasitas maksimum ' . $room['name'] . ' (' . $room['capacity'] . ' orang).';
                    } elseif ($reason === 'room_not_found') {
                        $error = 'Ruangan yang dipilih tidak ditemukan.';
                    } else {
                        $error = 'Gagal menyimpan pemesanan, terjadi kesalahan database.';
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
            'activity_type' => $activity_type ?? 'internal_divisi',
            'activity_types' => SawService::ACTIVITY_TYPES,
            'attendees_count' => $attendees_count,
            'error' => $error
        ]);
    }

    public function myBookings() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $this->validateCsrf('my_bookings.php');
            $action = $_POST['action'];
            $booking_id = (int)($_POST['booking_id'] ?? 0);

            if ($booking_id > 0 && $action === 'cancel') {
                if ($this->bookingModel->cancel($booking_id, $_SESSION['user_id'], false)) {
                    set_flash('success', 'Pemesanan telah berhasil dibatalkan.');
                } else {
                    set_flash('danger', 'Pemesanan tidak dapat dibatalkan atau bukan milik akun Anda.');
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

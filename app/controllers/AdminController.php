<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/SawService.php';

class AdminController extends Controller {
    private $roomModel;
    private $bookingModel;
    private $userModel;
    private $displayModel;

    public function __construct() {
        $this->roomModel = $this->model('RoomModel');
        $this->bookingModel = $this->model('BookingModel');
        $this->userModel = $this->model('UserModel');
        $this->displayModel = $this->model('DisplayModel');
    }

    private function handleRoomImageUpload($file, $existingImage = 'public/rooms/KalTim.jpeg') {
        if (isset($file) && is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            // Check size: max 5MB
            if (($file['size'] ?? 0) > 5242880) {
                return $existingImage;
            }

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $fileName = $file['name'];
            $fileTmp = $file['tmp_name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                return $existingImage;
            }

            // Real MIME type verification
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $fileTmp);
                finfo_close($finfo);
                if (!in_array($mime, $allowedMimes, true)) {
                    return $existingImage;
                }
            }

            // Image integrity check
            $imgInfo = @getimagesize($fileTmp);
            if ($imgInfo === false) {
                return $existingImage;
            }

            $uploadDir = __DIR__ . '/../../public/rooms/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'room_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                return 'public/rooms/' . $newFileName;
            }
        }
        return $existingImage;
    }

    public function rooms() {
        $this->requireAdmin();

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf('admin_rooms.php');
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                $code = strtoupper(trim($_POST['code'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $capacity = (int)($_POST['capacity'] ?? 0);
                $location = trim($_POST['location'] ?? '');
                $floor = trim($_POST['floor'] ?? '');
                $facilities = trim($_POST['facilities'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = trim($_POST['status'] ?? 'available');

                if (empty($code) || empty($name) || $capacity <= 0 || empty($location)) {
                    $error = 'Harap lengkapi field wajib (Kode, Nama, Kapasitas, Lokasi)!';
                } else if ($this->roomModel->getByCode($code)) {
                    $error = 'Kode ruangan ' . $code . ' sudah ada dalam database!';
                } else {
                    $imagePath = $this->handleRoomImageUpload($_FILES['room_image'] ?? null, 'public/rooms/KalTim.jpeg');

                    $data = [
                        'code' => $code,
                        'name' => $name,
                        'capacity' => $capacity,
                        'location' => $location,
                        'floor' => $floor,
                        'facilities' => $facilities,
                        'description' => $description,
                        'image' => $imagePath,
                        'status' => in_array($status, ['available', 'occupied', 'maintenance']) ? $status : 'available'
                    ];

                    if ($this->roomModel->create($data)) {
                        set_flash('success', 'Ruangan baru "' . htmlspecialchars($name) . '" berhasil ditambahkan.');
                        $this->redirect('admin_rooms.php');
                    } else {
                        $error = 'Gagal menyimpan ruangan baru.';
                    }
                }
            } else if ($action === 'edit') {
                $room_id = (int)($_POST['room_id'] ?? 0);
                $code = strtoupper(trim($_POST['code'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $capacity = (int)($_POST['capacity'] ?? 0);
                $location = trim($_POST['location'] ?? '');
                $floor = trim($_POST['floor'] ?? '');
                $facilities = trim($_POST['facilities'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = trim($_POST['status'] ?? 'available');

                $currentRoom = $room_id > 0 ? $this->roomModel->getById($room_id) : null;

                if (!$currentRoom) {
                    $error = 'Data ruangan tidak ditemukan.';
                } else if (empty($code) || empty($name) || $capacity <= 0 || empty($location)) {
                    $error = 'Harap lengkapi field wajib (Kode, Nama, Kapasitas, Lokasi)!';
                } else {
                    $checkCode = $this->roomModel->getByCode($code);
                    if ($checkCode && $checkCode['id'] != $room_id) {
                        $error = 'Kode ruangan ' . $code . ' sudah digunakan oleh ruangan lain!';
                    } else {
                        $existingImage = $currentRoom['image'] ?: 'public/rooms/KalTim.jpeg';
                        $imagePath = $this->handleRoomImageUpload($_FILES['room_image'] ?? null, $existingImage);

                        $data = [
                            'code' => $code,
                            'name' => $name,
                            'capacity' => $capacity,
                            'location' => $location,
                            'floor' => $floor,
                            'facilities' => $facilities,
                            'description' => $description,
                            'image' => $imagePath,
                            'status' => in_array($status, ['available', 'occupied', 'maintenance']) ? $status : 'available'
                        ];

                        if ($this->roomModel->update($room_id, $data)) {
                            set_flash('success', 'Informasi ruangan "' . htmlspecialchars($name) . '" berhasil diperbarui.');
                            $this->redirect('admin_rooms.php');
                        } else {
                            $error = 'Gagal memperbarui data ruangan.';
                        }
                    }
                }
            } else if ($action === 'update_status') {
                $room_id = (int)($_POST['room_id'] ?? 0);
                $status = trim($_POST['status'] ?? 'available');
                if ($room_id > 0) {
                    $this->roomModel->updateStatus($room_id, $status);
                    set_flash('success', 'Status ruangan berhasil diperbarui.');
                }
                $this->redirect('admin_rooms.php');
            } else if ($action === 'delete') {
                $room_id = (int)($_POST['room_id'] ?? 0);
                if ($room_id > 0) {
                    $this->roomModel->delete($room_id);
                    set_flash('success', 'Ruangan berhasil dihapus.');
                }
                $this->redirect('admin_rooms.php');
            }
        }

        $rooms = $this->roomModel->getAllRooms();

        $this->view('admin/rooms', [
            'rooms' => $rooms,
            'error' => $error
        ]);
    }

    public function bookings() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf('admin_bookings.php');
            $action = $_POST['action'] ?? '';
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $returnTo = ($_POST['return_to'] ?? '') === 'dashboard.php'
                ? 'dashboard.php'
                : 'admin_bookings.php';

            if ($action === 'apply_saw_decision') {
                $winner_id = (int)($_POST['winner_id'] ?? 0);
                $loser_ids_raw = $_POST['loser_ids'] ?? '';
                $loser_ids = array_filter(array_map('intval', explode(',', $loser_ids_raw)));

                if ($winner_id > 0) {
                    if ($this->bookingModel->resolveConflict($winner_id, $loser_ids)) {
                        set_flash('success', 'Rekomendasi keputusan SAW berhasil diterapkan! Jadwal terpilih disetujui (Confirmed), dan jadwal bentrok lainnya otomatis dibatalkan.');
                    } else {
                        set_flash('danger', 'Gagal menerapkan keputusan SAW.');
                    }
                }
                $this->redirect('admin_bookings.php');
            }

            if ($booking_id > 0) {
                if ($action === 'update_status') {
                    $status = trim($_POST['status'] ?? 'confirmed');
                    $this->bookingModel->updateStatus($booking_id, $status);
                    set_flash('success', 'Status pemesanan berhasil diperbarui.');
                } else if ($action === 'delete') {
                    $this->bookingModel->delete($booking_id);
                    set_flash('success', 'Data pemesanan berhasil dihapus.');
                }
            }
            $this->redirect($returnTo);
        }

        $search = trim($_GET['search'] ?? '');
        $status_filter = trim($_GET['status'] ?? '');

        $bookings = $this->bookingModel->getAllBookings($search, $status_filter);

        // Analisis SPK SAW untuk seluruh kelompok jadwal yang bertabrakan / berkonflik
        $conflictGroupsRaw = $this->bookingModel->getConflictingGroups();
        $conflictAnalyses = [];

        $usageCallback = function($dept, $date) {
            return $this->bookingModel->getDivisionMonthlyUsageCount($dept, $date);
        };

        foreach ($conflictGroupsRaw as $cg) {
            $analysis = SawService::analyzeConflictGroup($cg['bookings'], $usageCallback);
            if (!empty($analysis)) {
                $conflictAnalyses[] = array_merge($cg, [
                    'saw' => $analysis
                ]);
            }
        }

        $conflictBookingIds = [];
        foreach ($conflictAnalyses as $ca) {
            foreach ($ca['bookings'] as $cb) {
                $conflictBookingIds[$cb['id']] = [
                    'group_id' => $ca['group_id'],
                    'room_name' => $ca['room_name']
                ];
            }
        }

        $this->view('admin/bookings', [
            'bookings' => $bookings,
            'search' => $search,
            'status_filter' => $status_filter,
            'conflict_analyses' => $conflictAnalyses,
            'conflict_booking_ids' => $conflictBookingIds
        ]);
    }

    public function history() {
        $this->requireAdmin();

        $filters = [
            'start_date' => trim($_GET['start_date'] ?? ''),
            'end_date' => trim($_GET['end_date'] ?? ''),
            'room_id' => (int)($_GET['room_id'] ?? 0),
            'status' => trim($_GET['status'] ?? ''),
            'search' => trim($_GET['search'] ?? '')
        ];

        $summary = $this->bookingModel->getBookingHistorySummary($filters);
        $rooms = $this->roomModel->getAllRooms();

        $this->view('admin/history', [
            'summary' => $summary,
            'bookings' => $summary['bookings'],
            'rooms' => $rooms,
            'filters' => $filters
        ]);
    }

    public function users() {
        $this->requireAdmin();

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf('admin_users.php');
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $department = trim($_POST['department'] ?? '');
                $roleInput = trim($_POST['role'] ?? 'user');
                $role = is_super_admin() && in_array($roleInput, ['user', 'admin'], true)
                    ? $roleInput
                    : 'user';

                if (empty($name) || empty($email) || empty($password)) {
                    $error = 'Nama, email, dan password wajib diisi!';
                } else if ($this->userModel->getByEmail($email)) {
                    $error = 'Email sudah terdaftar!';
                } else {
                    $this->userModel->create([
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'department' => $department,
                        'role' => $role
                    ]);
                    set_flash('success', 'Pengguna baru berhasil ditambahkan.');
                    $this->redirect('admin_users.php');
                }
            } else if ($action === 'update_role') {
                $user_id = (int)($_POST['user_id'] ?? 0);
                $role = trim($_POST['role'] ?? 'user');
                $targetUser = $user_id > 0 ? $this->userModel->getById($user_id) : false;

                if (!is_super_admin()) {
                    set_flash('danger', 'Hanya Super Admin yang dapat mengubah role pengguna.');
                } else if (!$targetUser || $user_id === (int)$_SESSION['user_id']) {
                    set_flash('danger', 'Role akun tersebut tidak dapat diubah.');
                } else if (($targetUser['role'] ?? '') === 'super_admin') {
                    set_flash('danger', 'Role Super Admin dilindungi dan tidak dapat diubah dari halaman ini.');
                } else if (in_array($role, ['user', 'admin'], true)) {
                    $this->userModel->updateRole($user_id, $role);
                    set_flash('success', 'Role pengguna berhasil diperbarui.');
                }
                $this->redirect('admin_users.php');
            } else if ($action === 'delete') {
                $user_id = (int)($_POST['user_id'] ?? 0);
                $targetUser = $user_id > 0 ? $this->userModel->getById($user_id) : false;

                if (!is_super_admin()) {
                    set_flash('danger', 'Hanya Super Admin yang dapat menghapus akun pengguna.');
                } else if ($targetUser && $user_id !== (int)$_SESSION['user_id'] && ($targetUser['role'] ?? '') !== 'super_admin') {
                    $this->userModel->delete($user_id);
                    set_flash('success', 'Pengguna berhasil dihapus.');
                } else {
                    set_flash('danger', 'Akun Super Admin atau akun yang sedang digunakan tidak dapat dihapus.');
                }
                $this->redirect('admin_users.php');
            }
        }

        $users = $this->userModel->getAll();

        $this->view('admin/users', [
            'users' => $users,
            'error' => $error
        ]);
    }

    public function displays() {
        $this->requireAdmin();

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf('admin_displays.php');
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $room_id = (int)($_POST['room_id'] ?? 0);
                $display_name = trim($_POST['display_name'] ?? '');
                $token = trim($_POST['token'] ?? '');

                if (empty($room_id) || empty($display_name)) {
                    $error = 'Ruangan dan nama monitor wajib diisi!';
                } else {
                    if (empty($token)) {
                        $token = 'DISP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                    }
                    if ($this->displayModel->getByToken($token)) {
                        $error = 'Token display sudah digunakan, silakan gunakan token lain!';
                    } else {
                        if ($this->displayModel->create($room_id, $display_name, $token)) {
                            set_flash('success', 'Monitor display baru berhasil didaftarkan.');
                            $this->redirect('admin_displays.php');
                        } else {
                            $error = 'Gagal mendaftarkan display.';
                        }
                    }
                }
            } else if ($action === 'regenerate_token') {
                $display_id = (int)($_POST['display_id'] ?? 0);
                if ($display_id > 0) {
                    $newToken = 'DISP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                    $this->displayModel->updateToken($display_id, $newToken);
                    set_flash('success', 'Token monitor display berhasil diperbarui.');
                }
                $this->redirect('admin_displays.php');
            } else if ($action === 'delete') {
                $display_id = (int)($_POST['display_id'] ?? 0);
                if ($display_id > 0) {
                    $this->displayModel->delete($display_id);
                    set_flash('success', 'Perangkat display berhasil dihapus.');
                }
                $this->redirect('admin_displays.php');
            }
        }

        $displays = $this->displayModel->getAllDisplays();
        $rooms = $this->roomModel->getAllRooms();

        $this->view('admin/displays', [
            'displays' => $displays,
            'rooms' => $rooms,
            'error' => $error
        ]);
    }

    public function statistics() {
        $this->requireAdmin();

        $filters = [
            'period' => trim($_GET['period'] ?? 'this_year'),
            'start_date' => trim($_GET['start_date'] ?? ''),
            'end_date' => trim($_GET['end_date'] ?? ''),
            'room_id' => (int)($_GET['room_id'] ?? 0)
        ];

        $stats = $this->bookingModel->getStatisticsData($filters);
        $rooms = $this->roomModel->getAllRooms();

        $this->view('admin/statistics', [
            'stats' => $stats,
            'kpi' => $stats['kpi'],
            'rooms' => $rooms,
            'filters' => $filters
        ]);
    }
}

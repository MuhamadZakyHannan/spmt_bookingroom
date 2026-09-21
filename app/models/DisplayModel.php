<?php
require_once __DIR__ . '/../core/Database.php';

class DisplayModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllDisplays() {
        if (!$this->db) return [];
        $sql = "SELECT d.*, r.name as room_name, r.code as room_code, r.capacity, r.location, r.floor, r.status as room_status 
                FROM room_displays d 
                JOIN rooms r ON d.room_id = r.id 
                ORDER BY r.code ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getAll() {
        return $this->getAllDisplays();
    }

    public function getByToken($token) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT d.*, r.name as room_name, r.code as room_code, r.capacity, r.location, r.floor, r.status as room_status, r.image as room_image, r.facilities as room_facilities, r.description as room_description
                                    FROM room_displays d 
                                    JOIN rooms r ON d.room_id = r.id 
                                    WHERE d.display_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function getByRoomId($roomId) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT d.*, r.name as room_name, r.code as room_code, r.capacity, r.location, r.floor, r.status as room_status, r.image as room_image, r.facilities as room_facilities, r.description as room_description
                                    FROM room_displays d 
                                    JOIN rooms r ON d.room_id = r.id 
                                    WHERE d.room_id = ?");
        $stmt->execute([$roomId]);
        return $stmt->fetch();
    }

    public function getById($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT d.*, r.name as room_name, r.code as room_code 
                                    FROM room_displays d 
                                    JOIN rooms r ON d.room_id = r.id 
                                    WHERE d.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($roomId, $displayName, $token) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("INSERT INTO room_displays (room_id, display_name, display_token) VALUES (?, ?, ?)");
        return $stmt->execute([$roomId, $displayName, $token]);
    }

    public function updateToken($id, $token) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("UPDATE room_displays SET display_token = ? WHERE id = ?");
        return $stmt->execute([$token, $id]);
    }

    public function delete($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM room_displays WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function touchLastActive($displayId) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("UPDATE room_displays SET last_active = NOW() WHERE id = ?");
        return $stmt->execute([$displayId]);
    }

    /**
     * Menghitung status real-time, meeting yang sedang berlangsung, next booking, dan jadwal hari ini untuk display monitor.
     */
    public function getRoomLiveStatus($roomId, $targetTime = null) {
        if (!$this->db) return null;

        require_once __DIR__ . '/BookingModel.php';
        (new BookingModel())->processAutomaticAttendanceTransitions();

        // Ambil data ruangan
        $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch();

        if (!$room) return null;

        $currentTime = $targetTime ?: date('H:i:s');
        $currentTimestamp = strtotime($currentTime);

        // Ambil jadwal hari ini (CURDATE()) - HANYA YANG SUDAH DISETUJUI ADMIN (CONFIRMED)
        $stmtBookings = $this->db->prepare("SELECT b.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar 
                                            FROM bookings b 
                                            JOIN users u ON b.user_id = u.id 
                                            WHERE b.room_id = ? 
                                            AND b.date = CURDATE() 
                                            AND b.status = 'confirmed' 
                                            ORDER BY b.start_time ASC");
        $stmtBookings->execute([$roomId]);
        $rawBookings = $stmtBookings->fetchAll();

        $currentBooking = null;
        $nextBooking = null;
        $todaySchedule = [];

        foreach ($rawBookings as $b) {
            $start5 = substr($b['start_time'], 0, 5);
            $end5 = substr($b['end_time'], 0, 5);
            $current5 = date('H:i', $currentTimestamp);

            $isCheckedIn = ($b['attendance_status'] ?? null) === 'checked_in';
            $isAwaitingCheckIn = ($b['attendance_status'] ?? null) === 'scheduled'
                && $current5 >= $start5
                && $current5 < $end5;
            $isCurrent = ($current5 >= $start5 && $current5 < $end5 && $isCheckedIn);
            $isPast = ($current5 >= $end5);
            $isUpcoming = ($current5 < $start5);

            $item = $b;
            $item['start_time'] = $start5;
            $item['end_time'] = $end5;
            $item['is_current'] = $isCurrent;
            $item['is_past'] = $isPast;
            $item['is_upcoming'] = $isUpcoming;
            $item['is_awaiting_check_in'] = $isAwaitingCheckIn;

            // HANYA MASUKKAN YANG MASIH BERLANGSUNG ATAU AKAN DATANG (Selesai dihilangkan)
            if (!$isPast) {
                $todaySchedule[] = $item;
            }

            if ($isCurrent && !$currentBooking) {
                $startTs = strtotime(date('Y-m-d') . ' ' . $b['start_time']);
                $endTs = strtotime(date('Y-m-d') . ' ' . $b['end_time']);
                $totalDuration = max(1, ($endTs - $startTs) / 60);
                $elapsed = max(0, ($currentTimestamp - $startTs) / 60);
                $remaining = max(0, ($endTs - $currentTimestamp) / 60);
                $progressPercent = min(100, round(($elapsed / $totalDuration) * 100));

                $currentBooking = $item;
                $currentBooking['total_duration_minutes'] = $totalDuration;
                $currentBooking['elapsed_minutes'] = round($elapsed);
                $currentBooking['remaining_minutes'] = round($remaining);
                $currentBooking['progress_percent'] = $progressPercent;
            }

            if ($isUpcoming && !$nextBooking) {
                $startTs = strtotime(date('Y-m-d') . ' ' . $b['start_time']);
                $startsIn = max(0, round(($startTs - $currentTimestamp) / 60));
                $nextBooking = $item;
                $nextBooking['starts_in_minutes'] = $startsIn;
            }
        }

        // Tentukan status ruangan
        $status = 'available';
        if ($room['status'] === 'maintenance') {
            $status = 'maintenance';
        } else if ($currentBooking !== null) {
            $status = 'occupied';
        }

        $availableUntil = null;
        if ($status === 'available') {
            $availableUntil = $nextBooking ? $nextBooking['start_time'] : 'Sepanjang Hari';
        }

        return [
            'room' => $room,
            'status' => $status,
            'current_time' => $currentTime,
            'current_date' => date('Y-m-d'),
            'current_booking' => $currentBooking,
            'next_booking' => $nextBooking,
            'available_until' => $availableUntil,
            'today_schedule' => $todaySchedule,
            'total_today_bookings' => count($todaySchedule)
        ];
    }
}

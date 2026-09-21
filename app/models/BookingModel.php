<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AttendancePolicy.php';
require_once __DIR__ . '/../services/AttendanceService.php';

class BookingModel {
    private $db;
    private $attendanceService;
    private $lastInsertId = 0;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->attendanceService = new AttendanceService($this->db);
    }

    public function getTodayActiveBookingsCount() {
        if (!$this->db) return 0;
        return (int)$this->db->query("SELECT COUNT(*) FROM bookings WHERE date = CURDATE() AND status = 'confirmed'")->fetchColumn();
    }

    public function getTodayBookings() {
        if (!$this->db) return [];
        $this->processAutomaticAttendanceTransitions();
        $stmt = $this->db->prepare("SELECT b.*, r.name as room_name, u.name as user_name, u.avatar as user_avatar 
                                    FROM bookings b 
                                    JOIN rooms r ON b.room_id = r.id 
                                    JOIN users u ON b.user_id = u.id 
                                    WHERE b.date = CURDATE() AND b.status = 'confirmed'
                                    ORDER BY b.start_time ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function checkConflict($roomId, $date, $startTime, $endTime, $excludeId = 0) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT id, title, start_time, end_time, status FROM bookings 
                                    WHERE room_id = ? 
                                    AND date = ? 
                                    AND status IN ('confirmed', 'pending')
                                    AND id != ?
                                    AND (
                                        (start_time < ? AND end_time > ?) OR
                                        (start_time < ? AND end_time > ?) OR
                                        (start_time >= ? AND end_time <= ?)
                                    )");
        $stmt->execute([$roomId, $date, $excludeId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime]);
        return $stmt->fetch();
    }

    public function create($data) {
        if (!$this->db) return false;
        
        $activity_type = $data['activity_type'] ?? 'internal_divisi';

        try {
            $attendanceStatus = ($data['status'] ?? '') === 'confirmed' ? 'scheduled' : null;
            $stmt = $this->db->prepare("INSERT INTO bookings (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, attendance_status, user_name, user_dept)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $data['user_id'],
                $data['room_id'],
                $data['title'],
                $data['date'],
                $data['start_time'],
                $data['end_time'],
                $data['purpose'],
                $activity_type,
                $data['attendees_count'],
                $data['status'],
                $attendanceStatus,
                $data['user_name'] ?? null,
                $data['user_dept'] ?? null
            ]);
            if ($success) {
                $this->lastInsertId = (int)$this->db->lastInsertId();
            }
            return $success;
        } catch (Exception $e) {
            // Fallback jika database belum ada kolom activity_type
            $stmt = $this->db->prepare("INSERT INTO bookings (user_id, room_id, title, date, start_time, end_time, purpose, attendees_count, status, user_name, user_dept) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $data['user_id'],
                $data['room_id'],
                $data['title'],
                $data['date'],
                $data['start_time'],
                $data['end_time'],
                $data['purpose'],
                $data['attendees_count'],
                $data['status'],
                $data['user_name'] ?? null,
                $data['user_dept'] ?? null
            ]);
            if ($success) {
                $this->lastInsertId = (int)$this->db->lastInsertId();
            }
            return $success;
        }
    }

    public function getLastInsertId() {
        return $this->lastInsertId;
    }

    public function getDivisionMonthlyUsageCount($dept, $date) {
        if (!$this->db || empty($dept)) return 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings 
                                    WHERE user_dept = ? 
                                    AND MONTH(date) = MONTH(?) 
                                    AND YEAR(date) = YEAR(?) 
                                    AND status IN ('confirmed', 'completed')");
        $stmt->execute([$dept, $date, $date]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mendeteksi dan mengelompokkan pemesanan yang bertabrakan (bentrok waktu & ruangan)
     * Hanya kelompok yang memiliki minimal satu status 'pending' yang dianalisis untuk rekomendasi
     */
    public function getConflictingGroups() {
        if (!$this->db) return [];

        $sql = "SELECT b.*, r.name as room_name, r.code as room_code, r.capacity as room_capacity, r.location as room_location,
                       IFNULL(b.user_name, u.name) as user_name, 
                       u.email as user_email,
                       IFNULL(b.user_dept, 'Internal') as user_dept 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                LEFT JOIN users u ON b.user_id = u.id 
                WHERE b.status IN ('pending', 'confirmed') 
                ORDER BY b.date ASC, b.room_id ASC, b.start_time ASC";
        $stmt = $this->db->query($sql);
        $allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($allBookings)) return [];

        // Kelompokkan per room_id dan date
        $byRoomDate = [];
        foreach ($allBookings as $b) {
            $key = $b['room_id'] . '_' . $b['date'];
            $byRoomDate[$key][] = $b;
        }

        $conflictGroups = [];
        $groupId = 1;

        foreach ($byRoomDate as $key => $roomDateBookings) {
            if (count($roomDateBookings) < 2) continue;

            $n = count($roomDateBookings);
            $adj = array_fill(0, $n, []);

            for ($i = 0; $i < $n; $i++) {
                $startA = strtotime($roomDateBookings[$i]['start_time']);
                $endA = strtotime($roomDateBookings[$i]['end_time']);

                for ($j = $i + 1; $j < $n; $j++) {
                    $startB = strtotime($roomDateBookings[$j]['start_time']);
                    $endB = strtotime($roomDateBookings[$j]['end_time']);

                    // Overlap: max(startA, startB) < min(endA, endB)
                    if ($startA < $endB && $endA > $startB) {
                        $adj[$i][] = $j;
                        $adj[$j][] = $i;
                    }
                }
            }

            $visited = array_fill(0, $n, false);
            for ($i = 0; $i < $n; $i++) {
                if ($visited[$i]) continue;

                $clusterIndices = [];
                $queue = [$i];
                $visited[$i] = true;

                while (!empty($queue)) {
                    $curr = array_shift($queue);
                    $clusterIndices[] = $curr;

                    foreach ($adj[$curr] as $neighbor) {
                        if (!$visited[$neighbor]) {
                            $visited[$neighbor] = true;
                            $queue[] = $neighbor;
                        }
                    }
                }

                if (count($clusterIndices) >= 2) {
                    $groupBookings = [];
                    $hasPending = false;
                    foreach ($clusterIndices as $idx) {
                        $item = $roomDateBookings[$idx];
                        if ($item['status'] === 'pending') {
                            $hasPending = true;
                        }
                        $groupBookings[] = $item;
                    }

                    if ($hasPending) {
                        $first = $groupBookings[0];
                        $conflictGroups[] = [
                            'group_id' => $groupId++,
                            'room_id' => $first['room_id'],
                            'room_name' => $first['room_name'],
                            'room_code' => $first['room_code'],
                            'date' => $first['date'],
                            'bookings' => $groupBookings
                        ];
                    }
                }
            }
        }

        return $conflictGroups;
    }

    public function resolveConflict($winnerId, array $loserIds) {
        if (!$this->db) return false;
        try {
            $this->db->beginTransaction();

            // Setujui pemenang
            $stmtWin = $this->db->prepare("UPDATE bookings SET status = 'confirmed', attendance_status = 'scheduled' WHERE id = ?");
            $stmtWin->execute([$winnerId]);

            // Batalkan pengajuan yang kalah
            if (!empty($loserIds)) {
                $placeholders = implode(',', array_fill(0, count($loserIds), '?'));
                $stmtLose = $this->db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id IN ($placeholders)");
                $stmtLose->execute($loserIds);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getByUserId($userId) {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT b.*, r.name as room_name, r.code as room_code, r.location 
                                    FROM bookings b 
                                    JOIN rooms r ON b.room_id = r.id 
                                    WHERE b.user_id = ? 
                                    ORDER BY CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END, b.id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAttendanceActionState(array $booking, ?DateTimeImmutable $now = null) {
        return AttendancePolicy::getActionState($booking, $now);
    }

    /**
     * Memproses booking melewati grace period dan rapat yang telah mencapai
     * jam selesai. Dipanggil oleh halaman user, admin, dan polling display;
     * scripts/process_attendance.php juga dapat dijadwalkan tiap menit.
     */
    public function processAutomaticAttendanceTransitions() {
        return $this->attendanceService->processAutomaticTransitions();
    }

    public function checkIn($bookingId, $userId) {
        return $this->attendanceService->checkIn($bookingId, $userId);
    }

    public function checkOut($bookingId, $userId) {
        return $this->attendanceService->checkOut($bookingId, $userId);
    }

    public function cancel($bookingId, $userId, $isAdmin = false) {
        if (!$this->db) return false;
        if ($isAdmin) {
            $stmt = $this->db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            return $stmt->execute([$bookingId]);
        } else {
            $stmt = $this->db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed') AND (attendance_status IS NULL OR attendance_status = 'scheduled')");
            return $stmt->execute([$bookingId, $userId]);
        }
    }

    public function getAllBookings($search = '', $status = '') {
        if (!$this->db) return [];
        $this->processAutomaticAttendanceTransitions();
        $sql = "SELECT b.*, r.name as room_name, r.code as room_code, 
                       IFNULL(b.user_name, u.name) as user_name, 
                       u.email as user_email,
                       IFNULL(b.user_dept, 'Internal') as user_dept 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                JOIN users u ON b.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (b.title LIKE ? OR u.name LIKE ? OR r.name LIKE ? OR IFNULL(b.user_name, '') LIKE ?)";
            $term = "%$search%";
            $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
        }
        if ($status !== '') {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END, b.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAllBookingsAdmin($search = '', $status = '') {
        return $this->getAllBookings($search, $status);
    }

    public function updateStatus($bookingId, $status) {
        if (!$this->db) return false;
        $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $attendanceStatus = $status === 'confirmed' ? 'scheduled' : null;
        $stmt = $this->db->prepare("UPDATE bookings SET status = ?, attendance_status = ? WHERE id = ?");
        return $stmt->execute([$status, $attendanceStatus, $bookingId]);
    }

    public function delete($bookingId) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM bookings WHERE id = ?");
        return $stmt->execute([$bookingId]);
    }

    public function getCalendarEvents($roomId = 0) {
        if (!$this->db) return [];
        // HANYA JADWAL YANG SAH / TELAH DISETUJUI ADMIN (CONFIRMED)
        $sql = "SELECT b.*, r.name as room_name, IFNULL(b.user_name, u.name) as user_name, IFNULL(b.user_dept, 'Internal') as user_dept 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'confirmed'";
        $params = [];

        if ($roomId > 0) {
            $sql .= " AND b.room_id = ?";
            $params[] = $roomId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getBookingHistory($filters = []) {
        if (!$this->db) return [];
        $sql = "SELECT b.*, r.name as room_name, r.code as room_code, r.location as room_location,
                       IFNULL(b.user_name, u.name) as user_name, 
                       u.email as user_email,
                       IFNULL(b.user_dept, 'Internal') as user_dept 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                JOIN users u ON b.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND b.date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND b.date <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['room_id'])) {
            $sql .= " AND b.room_id = ?";
            $params[] = (int)$filters['room_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE ? OR u.name LIKE ? OR r.name LIKE ? OR IFNULL(b.user_name, '') LIKE ? OR IFNULL(b.user_dept, '') LIKE ?)";
            $term = "%" . $filters['search'] . "%";
            $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
        }

        $sql .= " ORDER BY b.date DESC, b.start_time DESC, b.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getBookingHistorySummary($filters = []) {
        $bookings = $this->getBookingHistory($filters);
        $totalBookings = count($bookings);
        $confirmedCount = 0;
        $pendingCount = 0;
        $totalAttendees = 0;
        $totalMinutes = 0;

        foreach ($bookings as $b) {
            if ($b['status'] === 'confirmed') $confirmedCount++;
            if ($b['status'] === 'pending') $pendingCount++;
            $totalAttendees += (int)($b['attendees_count'] ?? 1);

            $start = strtotime($b['date'] . ' ' . $b['start_time']);
            $end = strtotime($b['date'] . ' ' . $b['end_time']);
            if ($end > $start) {
                $totalMinutes += ($end - $start) / 60;
            }
        }

        return [
            'total_bookings' => $totalBookings,
            'confirmed_count' => $confirmedCount,
            'pending_count' => $pendingCount,
            'total_attendees' => $totalAttendees,
            'total_hours' => round($totalMinutes / 60, 1),
            'bookings' => $bookings
        ];
    }

    public function getStatisticsData($filters = []) {
        if (!$this->db) {
            return [
                'kpi' => [
                    'total_bookings' => 0, 'confirmed_count' => 0, 'pending_count' => 0,
                    'total_hours' => 0, 'total_attendees' => 0, 'avg_duration_minutes' => 0,
                    'avg_attendees' => 0, 'approval_rate' => 0, 'active_rooms_used' => 0, 'total_rooms' => 0
                ],
                'monthly_trend' => ['labels' => [], 'confirmed' => [], 'pending' => [], 'hours' => []],
                'room_stats' => [],
                'dept_distribution' => ['labels' => [], 'data' => [], 'percentages' => []],
                'peak_hours' => ['labels' => [], 'data' => []],
                'day_distribution' => ['labels' => [], 'data' => []],
                'top_organizers' => []
            ];
        }

        $period = $filters['period'] ?? 'this_year';
        $roomId = (int)($filters['room_id'] ?? 0);
        $startDate = trim($filters['start_date'] ?? '');
        $endDate = trim($filters['end_date'] ?? '');

        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "b.date >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "b.date <= ?";
            $params[] = $endDate;
        }

        if (empty($startDate) && empty($endDate)) {
            if ($period === 'this_month') {
                $where[] = "YEAR(b.date) = YEAR(CURDATE()) AND MONTH(b.date) = MONTH(CURDATE())";
            } else if ($period === 'last_month') {
                $where[] = "b.date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) AND b.date < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            } else if ($period === 'last_3_months') {
                $where[] = "b.date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
            } else if ($period === 'this_year') {
                $where[] = "YEAR(b.date) = YEAR(CURDATE())";
            }
        }

        if ($roomId > 0) {
            $where[] = "b.room_id = ?";
            $params[] = $roomId;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT b.*, r.name as room_name, r.code as room_code, r.capacity as room_capacity,
                       r.location as room_location, r.floor as room_floor,
                       IFNULL(b.user_name, u.name) as user_name,
                       u.email as user_email,
                       IFNULL(NULLIF(b.user_dept, ''), 'Internal') as user_dept
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN users u ON b.user_id = u.id
                WHERE $whereClause
                ORDER BY b.date ASC, b.start_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

        // 1. KPI Aggregates
        $totalBookings = count($bookings);
        $confirmedCount = 0;
        $pendingCount = 0;
        $totalAttendees = 0;
        $totalMinutes = 0;
        $roomsUsedMap = [];

        // 2. Data containers for charts
        $monthlyMap = [];
        $roomStats = [];
        $deptMap = [];
        $hourlyMap = array_fill(8, 11, 0); // 08:00 to 18:00
        $dayMap = [
            1 => ['name' => 'Senin', 'count' => 0],
            2 => ['name' => 'Selasa', 'count' => 0],
            3 => ['name' => 'Rabu', 'count' => 0],
            4 => ['name' => 'Kamis', 'count' => 0],
            5 => ['name' => 'Jumat', 'count' => 0],
            6 => ['name' => 'Sabtu', 'count' => 0],
            0 => ['name' => 'Minggu', 'count' => 0],
        ];
        $organizerMap = [];

        // Fetch all rooms to ensure all rooms are listed in roomStats
        $allRoomsStmt = $this->db->query("SELECT id, code, name, capacity, location, floor, status FROM rooms ORDER BY code ASC");
        $allRooms = $allRoomsStmt ? $allRoomsStmt->fetchAll() : [];
        foreach ($allRooms as $r) {
            $roomStats[$r['id']] = [
                'id' => $r['id'],
                'code' => $r['code'],
                'name' => $r['name'],
                'capacity' => $r['capacity'],
                'location' => $r['location'],
                'floor' => $r['floor'],
                'status' => $r['status'],
                'booking_count' => 0,
                'total_hours' => 0,
                'total_attendees' => 0,
                'confirmed_count' => 0
            ];
        }

        // Initialize months for monthly chart
        for ($i = 5; $i >= 0; $i--) {
            $time = strtotime("-$i months");
            $mKey = date('Y-m', $time);
            $mLabel = date('M Y', $time);
            $bulanIndo = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags',
                'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
            ];
            $short = date('M', $time);
            if (isset($bulanIndo[$short])) {
                $mLabel = $bulanIndo[$short] . ' ' . date('Y', $time);
            }
            $monthlyMap[$mKey] = [
                'label' => $mLabel,
                'confirmed' => 0,
                'pending' => 0,
                'hours' => 0
            ];
        }

        foreach ($bookings as $b) {
            $status = $b['status'];
            if ($status === 'confirmed') $confirmedCount++;
            if ($status === 'pending') $pendingCount++;

            $attendees = (int)($b['attendees_count'] ?? 1);
            $totalAttendees += $attendees;

            $sTime = strtotime($b['date'] . ' ' . $b['start_time']);
            $eTime = strtotime($b['date'] . ' ' . $b['end_time']);
            $durationHours = 0;
            if ($eTime > $sTime) {
                $durationMin = ($eTime - $sTime) / 60;
                $totalMinutes += $durationMin;
                $durationHours = round($durationMin / 60, 2);
            }

            $rId = (int)$b['room_id'];
            $roomsUsedMap[$rId] = true;

            // Room stats
            if (isset($roomStats[$rId])) {
                $roomStats[$rId]['booking_count']++;
                $roomStats[$rId]['total_hours'] += $durationHours;
                $roomStats[$rId]['total_attendees'] += $attendees;
                if ($status === 'confirmed') {
                    $roomStats[$rId]['confirmed_count']++;
                }
            }

            // Monthly map
            $mKey = date('Y-m', strtotime($b['date']));
            if (!isset($monthlyMap[$mKey])) {
                $mLabel = date('M Y', strtotime($b['date']));
                $short = date('M', strtotime($b['date']));
                $bulanIndo = [
                    'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                    'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags',
                    'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
                ];
                if (isset($bulanIndo[$short])) {
                    $mLabel = $bulanIndo[$short] . ' ' . date('Y', strtotime($b['date']));
                }
                $monthlyMap[$mKey] = [
                    'label' => $mLabel,
                    'confirmed' => 0,
                    'pending' => 0,
                    'hours' => 0
                ];
            }
            if ($status === 'confirmed') $monthlyMap[$mKey]['confirmed']++;
            if ($status === 'pending') $monthlyMap[$mKey]['pending']++;
            $monthlyMap[$mKey]['hours'] += $durationHours;

            // Department map
            $dept = !empty($b['user_dept']) ? $b['user_dept'] : 'Internal';
            if (!isset($deptMap[$dept])) {
                $deptMap[$dept] = 0;
            }
            $deptMap[$dept]++;

            // Hourly busy hours (08:00 - 18:00)
            $startHour = (int)date('G', strtotime($b['start_time']));
            $endHour = (int)date('G', strtotime($b['end_time']));
            if ($endHour <= $startHour) $endHour = $startHour + 1;
            for ($h = max(8, $startHour); $h < min(19, $endHour); $h++) {
                if (isset($hourlyMap[$h])) {
                    $hourlyMap[$h]++;
                }
            }

            // Day of week
            $w = (int)date('w', strtotime($b['date']));
            if (isset($dayMap[$w])) {
                $dayMap[$w]['count']++;
            }

            // Organizer map
            $uName = $b['user_name'] ?: 'Pengguna';
            $uEmail = $b['user_email'] ?? '';
            $uDept = $b['user_dept'] ?: 'Internal';
            $orgKey = $uEmail ?: $uName;
            if (!isset($organizerMap[$orgKey])) {
                $organizerMap[$orgKey] = [
                    'name' => $uName,
                    'email' => $uEmail,
                    'department' => $uDept,
                    'booking_count' => 0,
                    'total_hours' => 0
                ];
            }
            $organizerMap[$orgKey]['booking_count']++;
            $organizerMap[$orgKey]['total_hours'] += $durationHours;
        }

        // Sort roomStats by booking_count desc
        uasort($roomStats, function($a, $b) {
            if ($b['booking_count'] === $a['booking_count']) {
                return $b['total_hours'] <=> $a['total_hours'];
            }
            return $b['booking_count'] <=> $a['booking_count'];
        });

        // Sort organizerMap by booking_count desc
        uasort($organizerMap, function($a, $b) {
            return $b['booking_count'] <=> $a['booking_count'];
        });

        // Arrange monthly data
        ksort($monthlyMap);
        $monthlyLabels = [];
        $monthlyConfirmed = [];
        $monthlyPending = [];
        $monthlyHours = [];
        foreach ($monthlyMap as $m) {
            $monthlyLabels[] = $m['label'];
            $monthlyConfirmed[] = $m['confirmed'];
            $monthlyPending[] = $m['pending'];
            $monthlyHours[] = round($m['hours'], 1);
        }

        // Arrange dept data
        arsort($deptMap);
        $deptLabels = array_keys($deptMap);
        $deptCounts = array_values($deptMap);
        $deptPercentages = [];
        foreach ($deptCounts as $cnt) {
            $deptPercentages[] = $totalBookings > 0 ? round(($cnt / $totalBookings) * 100, 1) : 0;
        }

        // Arrange hourly data
        $hourlyLabels = [];
        $hourlyData = [];
        foreach ($hourlyMap as $h => $cnt) {
            $hourlyLabels[] = sprintf("%02d:00", $h);
            $hourlyData[] = $cnt;
        }

        // Arrange day data (Senin - Minggu)
        $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $dayOrder = [1, 2, 3, 4, 5, 6, 0];
        $dayData = [];
        foreach ($dayOrder as $w) {
            $dayData[] = $dayMap[$w]['count'];
        }

        $totalHours = round($totalMinutes / 60, 1);
        $avgDurationMin = $totalBookings > 0 ? round($totalMinutes / $totalBookings) : 0;
        $avgAttendees = $totalBookings > 0 ? round($totalAttendees / $totalBookings, 1) : 0;
        $approvalRate = $totalBookings > 0 ? round(($confirmedCount / $totalBookings) * 100, 1) : 0;

        $maxHoursPossiblePerRoom = 160;
        foreach ($roomStats as $rId => &$rs) {
            $rs['total_hours'] = round($rs['total_hours'], 1);
            $rs['avg_attendees'] = $rs['booking_count'] > 0 ? round($rs['total_attendees'] / $rs['booking_count'], 1) : 0;
            $rs['utilization_rate'] = min(100, round(($rs['total_hours'] / max(1, $maxHoursPossiblePerRoom)) * 100, 1));
        }

        return [
            'kpi' => [
                'total_bookings' => $totalBookings,
                'confirmed_count' => $confirmedCount,
                'pending_count' => $pendingCount,
                'total_hours' => $totalHours,
                'total_attendees' => $totalAttendees,
                'avg_duration_minutes' => $avgDurationMin,
                'avg_attendees' => $avgAttendees,
                'approval_rate' => $approvalRate,
                'active_rooms_used' => count($roomsUsedMap),
                'total_rooms' => count($allRooms)
            ],
            'monthly_trend' => [
                'labels' => $monthlyLabels,
                'confirmed' => $monthlyConfirmed,
                'pending' => $monthlyPending,
                'hours' => $monthlyHours
            ],
            'room_stats' => array_values($roomStats),
            'dept_distribution' => [
                'labels' => $deptLabels,
                'data' => $deptCounts,
                'percentages' => $deptPercentages
            ],
            'peak_hours' => [
                'labels' => $hourlyLabels,
                'data' => $hourlyData
            ],
            'day_distribution' => [
                'labels' => $dayLabels,
                'data' => $dayData
            ],
            'top_organizers' => array_slice(array_values($organizerMap), 0, 8)
        ];
    }
}

<?php

/**
 * Menyusun KPI dan dataset grafik pemakaian ruang rapat.
 */
final class BookingStatisticsService
{
    private const MONTH_LABELS = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags',
        'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des',
    ];

    /** Menyiapkan dependensi yang dibutuhkan oleh BookingStatisticsService. */
    public function __construct(private PDO $db)
    {
    }

    /** Menjalankan proses empty result pada booking statistics. */
    public static function emptyResult(): array
    {
        return [
            'kpi' => [
                'total_bookings' => 0,
                'confirmed_count' => 0,
                'pending_count' => 0,
                'total_hours' => 0,
                'total_attendees' => 0,
                'avg_duration_minutes' => 0,
                'avg_attendees' => 0,
                'approval_rate' => 0,
                'active_rooms_used' => 0,
                'total_rooms' => 0,
            ],
            'monthly_trend' => ['labels' => [], 'confirmed' => [], 'pending' => [], 'hours' => []],
            'room_stats' => [],
            'dept_distribution' => ['labels' => [], 'data' => [], 'percentages' => []],
            'peak_hours' => ['labels' => [], 'data' => []],
            'day_distribution' => ['labels' => [], 'data' => []],
            'top_organizers' => [],
        ];
    }

    /** Mengambil dataset booking statistics. */
    public function getData(array $filters = []): array
    {
        $bookings = $this->fetchBookings($filters);
        $allRooms = $this->fetchRooms();
        $roomStats = $this->initializeRoomStats($allRooms);
        $monthlyMap = $this->initializeMonthlyMap();
        $departmentMap = [];
        $hourlyMap = array_fill(8, 11, 0);
        $dayMap = array_fill(0, 7, 0);
        $organizerMap = [];
        $roomsUsed = [];
        $confirmedCount = 0;
        $pendingCount = 0;
        $totalAttendees = 0;
        $totalMinutes = 0;

        foreach ($bookings as $booking) {
            $status = $booking['status'];
            if ($status === 'confirmed') $confirmedCount++;
            if ($status === 'pending') $pendingCount++;

            $attendees = (int) ($booking['attendees_count'] ?? 1);
            $totalAttendees += $attendees;
            $durationMinutes = $this->durationMinutes($booking);
            $durationHours = round($durationMinutes / 60, 2);
            $totalMinutes += $durationMinutes;

            $roomId = (int) $booking['room_id'];
            $roomsUsed[$roomId] = true;
            if (isset($roomStats[$roomId])) {
                $roomStats[$roomId]['booking_count']++;
                $roomStats[$roomId]['total_hours'] += $durationHours;
                $roomStats[$roomId]['total_attendees'] += $attendees;
                if ($status === 'confirmed') $roomStats[$roomId]['confirmed_count']++;
            }

            $monthKey = date('Y-m', strtotime($booking['date']));
            $monthlyMap[$monthKey] ??= $this->emptyMonth($booking['date']);
            if ($status === 'confirmed') $monthlyMap[$monthKey]['confirmed']++;
            if ($status === 'pending') $monthlyMap[$monthKey]['pending']++;
            $monthlyMap[$monthKey]['hours'] += $durationHours;

            $department = $booking['user_dept'] ?: 'Internal';
            $departmentMap[$department] = ($departmentMap[$department] ?? 0) + 1;
            $this->addBusyHours($hourlyMap, $booking);
            $weekday = (int) date('w', strtotime($booking['date']));
            $dayMap[$weekday]++;
            $this->addOrganizer($organizerMap, $booking, $durationHours);
        }

        $this->sortRoomStats($roomStats);
        uasort($organizerMap, fn(array $left, array $right) =>
            $right['booking_count'] <=> $left['booking_count']
        );

        $totalBookings = count($bookings);
        return [
            'kpi' => $this->buildKpis(
                $totalBookings,
                $confirmedCount,
                $pendingCount,
                $totalAttendees,
                $totalMinutes,
                count($roomsUsed),
                count($allRooms)
            ),
            'monthly_trend' => $this->formatMonthlyTrend($monthlyMap),
            'room_stats' => $this->formatRoomStats($roomStats),
            'dept_distribution' => $this->formatDepartments($departmentMap, $totalBookings),
            'peak_hours' => $this->formatHours($hourlyMap),
            'day_distribution' => [
                'labels' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                'data' => array_map(fn(int $day) => $dayMap[$day], [1, 2, 3, 4, 5, 6, 0]),
            ],
            'top_organizers' => array_slice(array_values($organizerMap), 0, 8),
        ];
    }

    /** Mengambil data bookings. */
    private function fetchBookings(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        $startDate = trim((string) ($filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? ''));
        if ($startDate !== '') {
            $where[] = 'b.date >= ?';
            $params[] = $startDate;
        }
        if ($endDate !== '') {
            $where[] = 'b.date <= ?';
            $params[] = $endDate;
        }
        if ($startDate === '' && $endDate === '') {
            $this->addPeriodFilter($where, (string) ($filters['period'] ?? 'this_year'));
        }
        $roomId = (int) ($filters['room_id'] ?? 0);
        if ($roomId > 0) {
            $where[] = 'b.room_id = ?';
            $params[] = $roomId;
        }

        $statement = $this->db->prepare(
            "SELECT b.*, r.name as room_name, r.code as room_code,
                    r.capacity as room_capacity, r.location as room_location,
                    r.floor as room_floor, IFNULL(b.user_name, u.name) as user_name,
                    u.username as user_email,
                    IFNULL(NULLIF(b.user_dept, ''), 'Internal') as user_dept
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             JOIN users u ON b.user_id = u.id
             WHERE " . implode(' AND ', $where) . '
             ORDER BY b.date ASC, b.start_time ASC'
        );
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Menambahkan data period filter. */
    private function addPeriodFilter(array &$where, string $period): void
    {
        if ($period === 'this_month') {
            $where[] = 'YEAR(b.date) = YEAR(CURDATE()) AND MONTH(b.date) = MONTH(CURDATE())';
        } elseif ($period === 'last_month') {
            $where[] = "b.date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                        AND b.date < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        } elseif ($period === 'last_3_months') {
            $where[] = 'b.date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)';
        } elseif ($period === 'this_year') {
            $where[] = 'YEAR(b.date) = YEAR(CURDATE())';
        }
    }

    /** Mengambil data rooms. */
    private function fetchRooms(): array
    {
        return $this->db->query(
            'SELECT id, code, name, capacity, location, floor, status FROM rooms ORDER BY code ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Menyiapkan room stats. */
    private function initializeRoomStats(array $rooms): array
    {
        $statistics = [];
        foreach ($rooms as $room) {
            $statistics[$room['id']] = [
                'id' => $room['id'],
                'code' => $room['code'],
                'name' => $room['name'],
                'capacity' => $room['capacity'],
                'location' => $room['location'],
                'floor' => $room['floor'],
                'status' => $room['status'],
                'booking_count' => 0,
                'total_hours' => 0,
                'total_attendees' => 0,
                'confirmed_count' => 0,
            ];
        }
        return $statistics;
    }

    /** Menyiapkan monthly map. */
    private function initializeMonthlyMap(): array
    {
        $months = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $timestamp = strtotime("-$offset months");
            $months[date('Y-m', $timestamp)] = $this->emptyMonth(date('Y-m-d', $timestamp));
        }
        return $months;
    }

    /** Menjalankan proses empty month pada booking statistics. */
    private function emptyMonth(string $date): array
    {
        $timestamp = strtotime($date);
        $shortMonth = date('M', $timestamp);
        return [
            'label' => (self::MONTH_LABELS[$shortMonth] ?? $shortMonth) . ' ' . date('Y', $timestamp),
            'confirmed' => 0,
            'pending' => 0,
            'hours' => 0,
        ];
    }

    /** Menjalankan proses duration minutes pada booking statistics. */
    private function durationMinutes(array $booking): float
    {
        $start = strtotime($booking['date'] . ' ' . $booking['start_time']);
        $end = strtotime($booking['date'] . ' ' . $booking['end_time']);
        return $end > $start ? ($end - $start) / 60 : 0;
    }

    /** Menambahkan data busy hours. */
    private function addBusyHours(array &$hourlyMap, array $booking): void
    {
        $startHour = (int) date('G', strtotime($booking['start_time']));
        $endHour = (int) date('G', strtotime($booking['end_time']));
        if ($endHour <= $startHour) $endHour = $startHour + 1;
        for ($hour = max(8, $startHour); $hour < min(19, $endHour); $hour++) {
            if (isset($hourlyMap[$hour])) $hourlyMap[$hour]++;
        }
    }

    /** Menambahkan data organizer. */
    private function addOrganizer(array &$organizers, array $booking, float $hours): void
    {
        $name = $booking['user_name'] ?: 'Pengguna';
        $username = $booking['user_email'] ?? '';
        $key = $username ?: $name;
        $organizers[$key] ??= [
            'name' => $name,
            'email' => $username,
            'department' => $booking['user_dept'] ?: 'Internal',
            'booking_count' => 0,
            'total_hours' => 0,
        ];
        $organizers[$key]['booking_count']++;
        $organizers[$key]['total_hours'] += $hours;
    }

    /** Menjalankan proses sort room stats pada booking statistics. */
    private function sortRoomStats(array &$roomStats): void
    {
        uasort($roomStats, function (array $left, array $right): int {
            return $right['booking_count'] === $left['booking_count']
                ? $right['total_hours'] <=> $left['total_hours']
                : $right['booking_count'] <=> $left['booking_count'];
        });
    }

    /** Menyiapkan kpis. */
    private function buildKpis(
        int $totalBookings,
        int $confirmedCount,
        int $pendingCount,
        int $totalAttendees,
        float $totalMinutes,
        int $activeRooms,
        int $totalRooms
    ): array {
        return [
            'total_bookings' => $totalBookings,
            'confirmed_count' => $confirmedCount,
            'pending_count' => $pendingCount,
            'total_hours' => round($totalMinutes / 60, 1),
            'total_attendees' => $totalAttendees,
            'avg_duration_minutes' => $totalBookings > 0 ? round($totalMinutes / $totalBookings) : 0,
            'avg_attendees' => $totalBookings > 0 ? round($totalAttendees / $totalBookings, 1) : 0,
            'approval_rate' => $totalBookings > 0 ? round(($confirmedCount / $totalBookings) * 100, 1) : 0,
            'active_rooms_used' => $activeRooms,
            'total_rooms' => $totalRooms,
        ];
    }

    /** Memformat monthly trend. */
    private function formatMonthlyTrend(array $months): array
    {
        ksort($months);
        $result = ['labels' => [], 'confirmed' => [], 'pending' => [], 'hours' => []];
        foreach ($months as $month) {
            $result['labels'][] = $month['label'];
            $result['confirmed'][] = $month['confirmed'];
            $result['pending'][] = $month['pending'];
            $result['hours'][] = round($month['hours'], 1);
        }
        return $result;
    }

    /** Memformat room stats. */
    private function formatRoomStats(array $roomStats): array
    {
        foreach ($roomStats as &$room) {
            $room['total_hours'] = round($room['total_hours'], 1);
            $room['avg_attendees'] = $room['booking_count'] > 0
                ? round($room['total_attendees'] / $room['booking_count'], 1)
                : 0;
            $room['utilization_rate'] = min(100, round(($room['total_hours'] / 160) * 100, 1));
        }
        unset($room);
        return array_values($roomStats);
    }

    /** Memformat departments. */
    private function formatDepartments(array $departments, int $totalBookings): array
    {
        arsort($departments);
        $counts = array_values($departments);
        return [
            'labels' => array_keys($departments),
            'data' => $counts,
            'percentages' => array_map(
                fn(int $count) => $totalBookings > 0 ? round(($count / $totalBookings) * 100, 1) : 0,
                $counts
            ),
        ];
    }

    /** Memformat hours. */
    private function formatHours(array $hours): array
    {
        $labels = [];
        foreach (array_keys($hours) as $hour) $labels[] = sprintf('%02d:00', $hour);
        return ['labels' => $labels, 'data' => array_values($hours)];
    }
}

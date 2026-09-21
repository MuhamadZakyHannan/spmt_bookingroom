<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AttendancePolicy.php';

/**
 * Menyusun snapshot operasional dashboard admin dalam query yang terpusat.
 */
class AdminDashboardService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function getSnapshot(): array {
        $empty = [
            'summary' => [
                'pending_requests' => 0,
                'ongoing_meetings' => 0,
                'awaiting_check_in' => 0,
                'no_show_today' => 0,
                'available_rooms' => 0,
                'total_rooms' => 0,
            ],
            'pending_requests' => [],
            'room_monitoring' => [],
            'alerts' => [],
        ];

        if (!$this->db) return $empty;

        $summary = $this->getSummary();
        $pendingRequests = $this->getPendingRequests();
        $roomMonitoring = $this->getRoomMonitoring();

        $summary['total_rooms'] = count($roomMonitoring);
        $summary['available_rooms'] = count(array_filter(
            $roomMonitoring,
            static fn(array $room): bool => $room['operational_status'] === 'available'
        ));

        return [
            'summary' => $summary,
            'pending_requests' => $pendingRequests,
            'room_monitoring' => $roomMonitoring,
            'alerts' => $this->buildAlerts($summary, $roomMonitoring),
        ];
    }

    private function getSummary(): array {
        $earlyMinutes = AttendancePolicy::checkInEarlyMinutes();
        $graceMinutes = AttendancePolicy::graceMinutes();
        $sql = "SELECT
                    SUM(status = 'pending' AND date >= CURDATE()) AS pending_requests,
                    SUM(status = 'confirmed'
                        AND attendance_status = 'checked_in'
                        AND date = CURDATE()
                        AND CURTIME() >= start_time
                        AND CURTIME() < end_time) AS ongoing_meetings,
                    SUM(status = 'confirmed'
                        AND attendance_status = 'scheduled'
                        AND date = CURDATE()
                        AND DATE_SUB(TIMESTAMP(date, start_time), INTERVAL {$earlyMinutes} MINUTE) <= NOW()
                        AND DATE_ADD(TIMESTAMP(date, start_time), INTERVAL {$graceMinutes} MINUTE) >= NOW()) AS awaiting_check_in,
                    SUM(attendance_status = 'no_show' AND date = CURDATE()) AS no_show_today
                FROM bookings";
        $row = $this->db->query($sql)->fetch() ?: [];

        return [
            'pending_requests' => (int)($row['pending_requests'] ?? 0),
            'ongoing_meetings' => (int)($row['ongoing_meetings'] ?? 0),
            'awaiting_check_in' => (int)($row['awaiting_check_in'] ?? 0),
            'no_show_today' => (int)($row['no_show_today'] ?? 0),
            'available_rooms' => 0,
            'total_rooms' => 0,
        ];
    }

    private function getPendingRequests(int $limit = 6): array {
        $limit = max(1, min(20, $limit));
        $sql = "SELECT
                    b.id, b.title, b.date, b.start_time, b.end_time, b.created_at,
                    b.attendees_count, IFNULL(b.user_name, u.name) AS user_name,
                    IFNULL(b.user_dept, 'Internal') AS user_dept,
                    r.id AS room_id, r.name AS room_name, r.code AS room_code,
                    GREATEST(0, TIMESTAMPDIFF(MINUTE, b.created_at, NOW())) AS wait_minutes,
                    EXISTS (
                        SELECT 1 FROM bookings other
                        WHERE other.id <> b.id
                          AND other.room_id = b.room_id
                          AND other.date = b.date
                          AND other.status IN ('pending', 'confirmed')
                          AND other.start_time < b.end_time
                          AND other.end_time > b.start_time
                    ) AS has_conflict
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                LEFT JOIN users u ON u.id = b.user_id
                WHERE b.status = 'pending' AND b.date >= CURDATE()
                ORDER BY b.date ASC, b.start_time ASC, b.created_at ASC
                LIMIT {$limit}";

        return $this->db->query($sql)->fetchAll();
    }

    private function getRoomMonitoring(): array {
        $earlyMinutes = AttendancePolicy::checkInEarlyMinutes();
        $graceMinutes = AttendancePolicy::graceMinutes();
        $sql = "SELECT
                    r.id, r.code, r.name, r.location, r.floor, r.capacity, r.status AS room_status,
                    current_booking.id AS current_booking_id,
                    current_booking.title AS current_title,
                    current_booking.start_time AS current_start_time,
                    current_booking.end_time AS current_end_time,
                    IFNULL(current_booking.user_name, current_booking_user.name) AS current_user_name,
                    awaiting_booking.id AS awaiting_booking_id,
                    awaiting_booking.title AS awaiting_title,
                    awaiting_booking.start_time AS awaiting_start_time,
                    awaiting_booking.end_time AS awaiting_end_time,
                    next_booking.id AS next_booking_id,
                    next_booking.title AS next_title,
                    next_booking.date AS next_date,
                    next_booking.start_time AS next_start_time,
                    display.id AS display_id,
                    display.display_name,
                    display.last_active AS display_last_active
                FROM rooms r
                LEFT JOIN bookings current_booking ON current_booking.id = (
                    SELECT b1.id FROM bookings b1
                    WHERE b1.room_id = r.id
                      AND b1.status = 'confirmed'
                      AND b1.attendance_status = 'checked_in'
                      AND b1.date = CURDATE()
                      AND DATE_SUB(TIMESTAMP(b1.date, b1.start_time), INTERVAL {$earlyMinutes} MINUTE) <= NOW()
                      AND TIMESTAMP(b1.date, b1.end_time) > NOW()
                    ORDER BY b1.start_time ASC
                    LIMIT 1
                )
                LEFT JOIN users current_booking_user ON current_booking_user.id = current_booking.user_id
                LEFT JOIN bookings awaiting_booking ON awaiting_booking.id = (
                    SELECT b2.id FROM bookings b2
                    WHERE b2.room_id = r.id
                      AND b2.status = 'confirmed'
                      AND b2.attendance_status = 'scheduled'
                      AND DATE_SUB(TIMESTAMP(b2.date, b2.start_time), INTERVAL {$earlyMinutes} MINUTE) <= NOW()
                      AND DATE_ADD(TIMESTAMP(b2.date, b2.start_time), INTERVAL {$graceMinutes} MINUTE) >= NOW()
                    ORDER BY b2.start_time ASC
                    LIMIT 1
                )
                LEFT JOIN bookings next_booking ON next_booking.id = (
                    SELECT b3.id FROM bookings b3
                    WHERE b3.room_id = r.id
                      AND b3.status = 'confirmed'
                      AND b3.attendance_status = 'scheduled'
                      AND TIMESTAMP(b3.date, b3.start_time) > NOW()
                    ORDER BY b3.date ASC, b3.start_time ASC
                    LIMIT 1
                )
                LEFT JOIN room_displays display ON display.id = (
                    SELECT d1.id FROM room_displays d1
                    WHERE d1.room_id = r.id
                    ORDER BY d1.id ASC
                    LIMIT 1
                )
                ORDER BY r.code ASC";

        $rows = $this->db->query($sql)->fetchAll();
        foreach ($rows as &$row) {
            if ($row['room_status'] === 'maintenance') {
                $row['operational_status'] = 'maintenance';
            } elseif (!empty($row['current_booking_id'])) {
                $row['operational_status'] = 'occupied';
            } elseif (!empty($row['awaiting_booking_id'])) {
                $row['operational_status'] = 'awaiting_check_in';
            } else {
                $row['operational_status'] = 'available';
            }

            if (empty($row['display_id'])) {
                $row['display_status'] = 'unconfigured';
            } elseif (!empty($row['display_last_active']) && time() - strtotime($row['display_last_active']) <= 120) {
                $row['display_status'] = 'online';
            } else {
                $row['display_status'] = 'offline';
            }
        }
        unset($row);

        return $rows;
    }

    private function buildAlerts(array $summary, array $rooms): array {
        $alerts = [];
        $conflictCount = (int)$this->db->query(
            "SELECT COUNT(*) FROM bookings b
             WHERE b.status = 'pending'
               AND b.date >= CURDATE()
               AND EXISTS (
                   SELECT 1 FROM bookings other
                   WHERE other.id <> b.id
                     AND other.room_id = b.room_id
                     AND other.date = b.date
                     AND other.status IN ('pending', 'confirmed')
                     AND other.start_time < b.end_time
                     AND other.end_time > b.start_time
               )"
        )->fetchColumn();
        $offlineDisplays = count(array_filter(
            $rooms,
            static fn(array $room): bool => $room['display_status'] === 'offline'
        ));
        $unconfiguredDisplays = count(array_filter(
            $rooms,
            static fn(array $room): bool => $room['display_status'] === 'unconfigured'
        ));
        $maintenanceRooms = count(array_filter(
            $rooms,
            static fn(array $room): bool => $room['operational_status'] === 'maintenance'
        ));

        if ($conflictCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-code-branch',
                'title' => $conflictCount . ' pengajuan bentrok',
                'description' => 'Perlu keputusan prioritas melalui analisis SAW.',
                'url' => 'admin_bookings.php',
            ];
        }
        if ((int)$summary['no_show_today'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-user-clock',
                'title' => $summary['no_show_today'] . ' no-show hari ini',
                'description' => 'Periksa riwayat pemakaian ruang hari ini.',
                'url' => 'admin_history.php?status=completed',
            ];
        }
        if ($offlineDisplays > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-display',
                'title' => $offlineDisplays . ' display offline',
                'description' => 'Monitor tidak aktif dalam dua menit terakhir.',
                'url' => 'admin_displays.php',
            ];
        }
        if ($unconfiguredDisplays > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fa-plug-circle-xmark',
                'title' => $unconfiguredDisplays . ' ruang tanpa display',
                'description' => 'Daftarkan monitor pintu jika diperlukan.',
                'url' => 'admin_displays.php',
            ];
        }
        if ($maintenanceRooms > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fa-screwdriver-wrench',
                'title' => $maintenanceRooms . ' ruang maintenance',
                'description' => 'Ruangan tidak tersedia untuk pemesanan.',
                'url' => 'admin_rooms.php',
            ];
        }

        return $alerts;
    }
}

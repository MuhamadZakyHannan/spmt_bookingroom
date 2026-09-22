<?php
require_once __DIR__ . '/../core/Database.php';

/**
 * Menyediakan data ringkas untuk panel admin pada sidebar dashboard.
 */
class AdminDashboardService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function getSnapshot(): array {
        if (!$this->db) {
            return $this->emptySnapshot();
        }

        return [
            'pending_count' => $this->getPendingCount(),
            'pending_requests' => $this->getPendingRequests(),
            'room_displays' => $this->getRoomDisplays(),
        ];
    }

    private function emptySnapshot(): array {
        return [
            'pending_count' => 0,
            'pending_requests' => [],
            'room_displays' => [],
        ];
    }

    private function getPendingCount(): int {
        $query = $this->db->query(
            "SELECT COUNT(*) FROM bookings WHERE status = 'pending' AND date >= CURDATE()"
        );

        return (int)$query->fetchColumn();
    }

    private function getPendingRequests(int $limit = 3): array {
        $limit = max(1, min(10, $limit));
        $sql = "SELECT
                    b.id, b.title, b.date, b.start_time, b.end_time,
                    IFNULL(b.user_name, u.name) AS user_name,
                    r.name AS room_name,
                    d.id AS document_id,
                    d.original_name AS document_name,
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
                LEFT JOIN booking_documents d
                  ON d.booking_id = b.id AND d.document_type = 'supporting_document'
                WHERE b.status = 'pending' AND b.date >= CURDATE()
                ORDER BY b.date ASC, b.start_time ASC, b.created_at ASC
                LIMIT {$limit}";

        return $this->db->query($sql)->fetchAll();
    }

    private function getRoomDisplays(): array {
        $sql = "SELECT
                    r.id, r.code, r.name,
                    display.id AS display_id,
                    display.display_name,
                    display.last_active AS display_last_active
                FROM rooms r
                LEFT JOIN room_displays display ON display.id = (
                    SELECT d1.id
                    FROM room_displays d1
                    WHERE d1.room_id = r.id
                    ORDER BY d1.id ASC
                    LIMIT 1
                )
                ORDER BY r.code ASC";

        $rooms = $this->db->query($sql)->fetchAll();
        foreach ($rooms as &$room) {
            if (empty($room['display_id'])) {
                $room['display_status'] = 'unconfigured';
            } elseif (!empty($room['display_last_active']) && time() - strtotime($room['display_last_active']) <= 120) {
                $room['display_status'] = 'online';
            } else {
                $room['display_status'] = 'offline';
            }
        }
        unset($room);

        return $rooms;
    }
}

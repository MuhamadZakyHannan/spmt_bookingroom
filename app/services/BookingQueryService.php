<?php

/**
 * Menyediakan query baca booking untuk dashboard, kalender, dan pengelolaan.
 */
final class BookingQueryService
{
    /** Menyiapkan dependensi yang dibutuhkan oleh BookingQueryService. */
    public function __construct(private PDO $db)
    {
    }

    /** Mengambil data today active count. */
    public function getTodayActiveCount(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM bookings WHERE date = CURDATE() AND status IN ('confirmed', 'completed')"
        )->fetchColumn();
    }

    /** Mengambil data today. */
    public function getToday(): array
    {
        $statement = $this->db->query(
            "SELECT b.*, r.name as room_name, u.name as user_name, u.avatar as user_avatar
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             JOIN users u ON b.user_id = u.id
             WHERE b.date = CURDATE() AND b.status IN ('confirmed', 'completed')
             ORDER BY b.start_time ASC"
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Mengambil data division monthly usage count. */
    public function getDivisionMonthlyUsageCount(string $department, string $date): int
    {
        if ($department === '') return 0;
        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE user_dept = ? AND MONTH(date) = MONTH(?) AND YEAR(date) = YEAR(?)
               AND status IN ('confirmed', 'completed')"
        );
        $statement->execute([$department, $date, $date]);
        return (int) $statement->fetchColumn();
    }

    /** Mengambil data by user id. */
    public function getByUserId(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT b.*, r.name as room_name, r.code as room_code, r.location,
                    d.id AS document_id, d.original_name AS document_name,
                    d.mime_type AS document_mime_type, d.size_bytes AS document_size_bytes
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             LEFT JOIN booking_documents d
               ON d.booking_id = b.id AND d.document_type = 'supporting_document'
             WHERE b.user_id = ?
             ORDER BY CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END, b.id DESC"
        );
        $statement->execute([$userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Mengambil data by id. */
    public function getById(int $bookingId)
    {
        if ($bookingId <= 0) return false;
        $statement = $this->db->prepare(
            "SELECT b.*, r.name AS room_name, r.code AS room_code, r.location,
                    IFNULL(b.user_name, u.name) AS requester_name,
                    IFNULL(b.user_dept, u.department) AS requester_department,
                    d.id AS document_id, d.original_name AS document_name,
                    d.mime_type AS document_mime_type, d.size_bytes AS document_size_bytes
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             JOIN users u ON u.id = b.user_id
             LEFT JOIN booking_documents d
               ON d.booking_id = b.id AND d.document_type = 'supporting_document'
             WHERE b.id = ? LIMIT 1"
        );
        $statement->execute([$bookingId]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /** Mengambil seluruh data booking query dengan dukungan pagination. */
    public function getAll(string $search = '', string $status = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT b.*, r.name as room_name, r.code as room_code,
                       IFNULL(b.user_name, u.name) as user_name,
                       u.username as user_email,
                       IFNULL(b.user_dept, 'Internal') as user_dept,
                       d.id AS document_id, d.original_name AS document_name,
                       d.mime_type AS document_mime_type, d.size_bytes AS document_size_bytes
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN users u ON b.user_id = u.id
                LEFT JOIN booking_documents d
                  ON d.booking_id = b.id AND d.document_type = 'supporting_document'
                WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (b.title LIKE ? OR u.name LIKE ? OR r.name LIKE ? OR IFNULL(b.user_name, '') LIKE ?)";
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if ($status !== '') {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        $sql .= " ORDER BY CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END, b.id DESC";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Menghitung total data booking query untuk keperluan pagination server-side. */
    public function countAll(string $search = '', string $status = ''): int
    {
        $sql = "SELECT COUNT(*) FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN users u ON b.user_id = u.id
                WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (b.title LIKE ? OR u.name LIKE ? OR r.name LIKE ? OR IFNULL(b.user_name, '') LIKE ?)";
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if ($status !== '') {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    /** Mengambil data calendar events dengan dukungan filter ruangan dan pencarian. */
    public function getCalendarEvents(int $roomId = 0, string $search = ''): array
    {
        $sql = "SELECT b.*, r.name as room_name,
                       IFNULL(b.user_name, u.name) as user_name,
                       IFNULL(b.user_dept, 'Internal') as user_dept
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN users u ON b.user_id = u.id
                WHERE b.status IN ('confirmed', 'completed')";
        $params = [];
        if ($roomId > 0) {
            $sql .= ' AND b.room_id = ?';
            $params[] = $roomId;
        }
        if ($search !== '') {
            $sql .= " AND (b.title LIKE ? OR u.name LIKE ? OR r.name LIKE ? OR IFNULL(b.user_name, '') LIKE ? OR IFNULL(b.purpose, '') LIKE ?)";
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term, $term);
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

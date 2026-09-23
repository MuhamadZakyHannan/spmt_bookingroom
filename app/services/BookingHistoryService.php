<?php

/**
 * Menangani pembacaan dan ringkasan riwayat booking.
 *
 * Service hanya menerima koneksi dari pemanggil sehingga tidak membuat
 * dependency database tersembunyi dan mudah diuji secara terisolasi.
 */
final class BookingHistoryService
{
    /** Menyiapkan dependensi yang dibutuhkan oleh BookingHistoryService. */
    public function __construct(private PDO $db)
    {
    }

    /**
     * Mengambil riwayat booking berdasarkan filter laporan administrator.
     */
    public function getHistory(array $filters = []): array
    {
        $sql = "SELECT b.*, r.name as room_name, r.code as room_code, r.location as room_location,
                       IFNULL(b.user_name, u.name) as user_name,
                       u.username as user_email,
                       IFNULL(b.user_dept, 'Internal') as user_dept
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN users u ON b.user_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= ' AND b.date >= ?';
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= ' AND b.date <= ?';
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['room_id'])) {
            $sql .= ' AND b.room_id = ?';
            $params[] = (int) $filters['room_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND b.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE ? OR u.name LIKE ? OR r.name LIKE ?
                      OR IFNULL(b.user_name, '') LIKE ? OR IFNULL(b.user_dept, '') LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term, $term, $term);
        }

        $sql .= ' ORDER BY b.date DESC, b.start_time DESC, b.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Menghasilkan KPI ringkas dari kumpulan riwayat yang sudah difilter.
     */
    public function getSummary(array $filters = []): array
    {
        $bookings = $this->getHistory($filters);
        $confirmedCount = 0;
        $completedCount = 0;
        $pendingCount = 0;
        $cancelledCount = 0;
        $totalAttendees = 0;
        $totalMinutes = 0;

        foreach ($bookings as $booking) {
            $status = $booking['status'] ?? '';
            if (in_array($status, ['confirmed', 'completed'], true)) {
                $confirmedCount++;
            }
            if ($status === 'completed') {
                $completedCount++;
            }
            if ($status === 'pending') {
                $pendingCount++;
            }
            if ($status === 'cancelled') {
                $cancelledCount++;
            }
            $totalAttendees += (int) ($booking['attendees_count'] ?? 1);

            $start = strtotime($booking['date'] . ' ' . $booking['start_time']);
            $end = strtotime($booking['date'] . ' ' . $booking['end_time']);
            if ($end > $start) {
                $totalMinutes += ($end - $start) / 60;
            }
        }

        return [
            'total_bookings' => count($bookings),
            'confirmed_count' => $confirmedCount,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'cancelled_count' => $cancelledCount,
            'total_attendees' => $totalAttendees,
            'total_hours' => round($totalMinutes / 60, 1),
            'bookings' => $bookings,
        ];
    }
}

<?php

/**
 * Mendeteksi kelompok jadwal beririsan dan menyimpan hasil keputusan admin.
 */
final class BookingConflictService
{
    /** Menyiapkan dependensi yang dibutuhkan oleh BookingConflictService. */
    public function __construct(private PDO $db)
    {
    }

    /**
     * Mengelompokkan booking yang terhubung oleh irisan waktu pada ruang/tanggal sama.
     */
    public function getGroups(): array
    {
        $statement = $this->db->query(
            "SELECT b.*, r.name as room_name, r.code as room_code,
                    r.capacity as room_capacity, r.location as room_location,
                    IFNULL(b.user_name, u.name) as user_name,
                    u.username as user_email,
                    IFNULL(b.user_dept, 'Internal') as user_dept,
                    d.id AS document_id, d.original_name AS document_name
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN booking_documents d
               ON d.booking_id = b.id AND d.document_type = 'supporting_document'
             WHERE b.status IN ('pending', 'confirmed')
             ORDER BY b.date ASC, b.room_id ASC, b.start_time ASC"
        );
        $allBookings = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (!$allBookings) return [];

        $bookingsByRoomAndDate = [];
        foreach ($allBookings as $booking) {
            $key = $booking['room_id'] . '_' . $booking['date'];
            $bookingsByRoomAndDate[$key][] = $booking;
        }

        $conflictGroups = [];
        $groupId = 1;
        foreach ($bookingsByRoomAndDate as $bookings) {
            if (count($bookings) < 2) continue;

            $bookingCount = count($bookings);
            $adjacency = array_fill(0, $bookingCount, []);
            for ($left = 0; $left < $bookingCount; $left++) {
                $leftStart = strtotime($bookings[$left]['start_time']);
                $leftEnd = strtotime($bookings[$left]['end_time']);

                for ($right = $left + 1; $right < $bookingCount; $right++) {
                    $rightStart = strtotime($bookings[$right]['start_time']);
                    $rightEnd = strtotime($bookings[$right]['end_time']);
                    if ($leftStart < $rightEnd && $leftEnd > $rightStart) {
                        $adjacency[$left][] = $right;
                        $adjacency[$right][] = $left;
                    }
                }
            }

            $visited = array_fill(0, $bookingCount, false);
            for ($index = 0; $index < $bookingCount; $index++) {
                if ($visited[$index]) continue;

                $clusterIndices = [];
                $queue = [$index];
                $visited[$index] = true;
                while ($queue) {
                    $current = array_shift($queue);
                    $clusterIndices[] = $current;
                    foreach ($adjacency[$current] as $neighbor) {
                        if (!$visited[$neighbor]) {
                            $visited[$neighbor] = true;
                            $queue[] = $neighbor;
                        }
                    }
                }

                if (count($clusterIndices) < 2) continue;

                $groupBookings = [];
                $hasPending = false;
                foreach ($clusterIndices as $clusterIndex) {
                    $booking = $bookings[$clusterIndex];
                    $hasPending = $hasPending || $booking['status'] === 'pending';
                    $groupBookings[] = $booking;
                }
                if (!$hasPending) continue;

                $first = $groupBookings[0];
                $conflictGroups[] = [
                    'group_id' => $groupId++,
                    'room_id' => $first['room_id'],
                    'room_name' => $first['room_name'],
                    'room_code' => $first['room_code'],
                    'date' => $first['date'],
                    'bookings' => $groupBookings,
                ];
            }
        }

        return $conflictGroups;
    }

    /**
     * Menyetujui pemenang dan membatalkan booking lain dalam satu transaksi.
     */
    public function resolve(int $winnerId, array $loserIds): bool
    {
        try {
            $this->db->beginTransaction();
            $winner = $this->db->prepare(
                "UPDATE bookings SET status = 'confirmed', status_reason = NULL WHERE id = ?"
            );
            $winner->execute([$winnerId]);

            if ($loserIds) {
                $placeholders = implode(',', array_fill(0, count($loserIds), '?'));
                $losers = $this->db->prepare(
                    "UPDATE bookings SET status = 'cancelled', status_reason = ?
                     WHERE id IN ($placeholders)"
                );
                $losers->execute(array_merge([
                    BookingLifecycleService::REASON_CONFLICT_NOT_SELECTED,
                ], $loserIds));
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Gagal menyelesaikan konflik booking: ' . $exception->getMessage());
            return false;
        }
    }
}

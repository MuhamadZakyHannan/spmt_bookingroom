<?php

/**
 * Menangani penulisan jadwal dengan lock dan kebijakan konflik transaksional.
 */
final class BookingScheduleService
{
    private int $lastInsertId = 0;

    /** Menyiapkan dependensi yang dibutuhkan oleh BookingScheduleService. */
    public function __construct(private PDO $db)
    {
    }

    /** Memvalidasi conflict. */
    public function checkConflict(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        int $excludeId = 0
    ) {
        $statement = $this->db->prepare(
            "SELECT id, title, start_time, end_time, status
             FROM bookings
             WHERE room_id = ? AND date = ?
               AND status IN ('confirmed', 'pending') AND id != ?
               AND start_time < ? AND end_time > ?
             ORDER BY CASE WHEN status = 'confirmed' THEN 0 ELSE 1 END, start_time ASC
             LIMIT 1"
        );
        $statement->execute([$roomId, $date, $excludeId, $endTime, $startTime]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /** Menambahkan data with policy. */
    public function createWithPolicy(array $data, bool $isAdmin): array
    {
        $today = date('Y-m-d');
        $currentTime = date('H:i');
        $startTime = substr((string) ($data['start_time'] ?? ''), 0, 5);
        if ($data['date'] < $today || ($data['date'] === $today && $startTime <= $currentTime)) {
            return [
                'success' => false,
                'reason' => 'past_time',
                'message' => 'Waktu mulai pemesanan tidak boleh mendahului waktu saat ini (sudah terlewat). Silakan sesuaikan jam pemesanan Anda.',
            ];
        }

        try {
            $this->db->beginTransaction();
            $room = $this->lockRoom((int) $data['room_id']);
            $validation = $this->validateRoom($room, (int) $data['attendees_count']);
            if ($validation !== null) return $this->rollbackWith($validation);

            $conflicts = $this->lockConflicts($data, 0);
            $classification = $this->classifyConflicts($conflicts);
            if ($classification['confirmed'] !== null) {
                return $this->rollbackWith([
                    'success' => false,
                    'reason' => 'confirmed_conflict',
                    'conflict' => $classification['confirmed'],
                    'room' => $room,
                ]);
            }

            $data['status'] = $classification['has_pending']
                ? 'pending'
                : ($isAdmin ? 'confirmed' : 'pending');
            if (!$this->insert($data)) throw new RuntimeException('Gagal menyimpan booking.');

            $this->db->commit();
            return [
                'success' => true,
                'status' => $data['status'],
                'pending_conflict' => $classification['has_pending'],
                'booking_id' => $this->lastInsertId,
            ];
        } catch (Throwable $exception) {
            return $this->transactionFailure('Booking transaction error', $exception);
        }
    }

    /** Memperbarui with policy. */
    public function updateWithPolicy(
        int $bookingId,
        array $data,
        int $actorUserId,
        bool $isAdmin
    ): array {
        try {
            $this->db->beginTransaction();
            if (!$this->bookingExists($bookingId)) {
                return $this->rollbackWith(['success' => false, 'reason' => 'booking_not_found']);
            }

            $room = $this->lockRoom((int) $data['room_id']);
            $booking = $this->lockBooking($bookingId);
            if (!$booking) {
                return $this->rollbackWith(['success' => false, 'reason' => 'booking_not_found']);
            }

            $status = (string) $booking['status'];
            $ownerCanEdit = (int) $booking['user_id'] === $actorUserId && $status === 'pending';
            $adminCanEdit = $isAdmin && in_array($status, ['pending', 'confirmed'], true);
            if (!$ownerCanEdit && !$adminCanEdit) {
                return $this->rollbackWith([
                    'success' => false,
                    'reason' => 'forbidden',
                    'status' => $status,
                ]);
            }

            $validation = $this->validateRoom($room, (int) $data['attendees_count']);
            if ($validation !== null) return $this->rollbackWith($validation);

            $classification = $this->classifyConflicts($this->lockConflicts($data, $bookingId));
            if ($classification['confirmed'] !== null) {
                return $this->rollbackWith([
                    'success' => false,
                    'reason' => 'confirmed_conflict',
                    'conflict' => $classification['confirmed'],
                    'room' => $room,
                ]);
            }

            $oldRoomId = (int) $booking['room_id'];
            $newRoomId = (int) $data['room_id'];
            $isRelocated = ($isAdmin && $status === 'confirmed' && $oldRoomId !== $newRoomId);

            if (!$this->update($bookingId, $data)) {
                throw new RuntimeException('Gagal memperbarui booking.');
            }

            $this->db->commit();
            return [
                'success' => true,
                'status' => $status,
                'pending_conflict' => $classification['has_pending'],
                'booking_id' => $bookingId,
                'is_relocated' => $isRelocated,
                'old_room_id' => $oldRoomId,
                'new_room_id' => $newRoomId,
            ];
        } catch (Throwable $exception) {
            return $this->transactionFailure('Booking update transaction error', $exception);
        }
    }

    /** Membuat data booking schedule baru. */
    public function create(array $data): bool
    {
        return $this->insert($data);
    }

    /** Mengambil data last insert id. */
    public function getLastInsertId(): int
    {
        return $this->lastInsertId;
    }

    /** Mengunci room selama transaksi. */
    private function lockRoom(int $roomId)
    {
        $statement = $this->db->prepare(
            'SELECT id, name, capacity, status FROM rooms WHERE id = ? FOR UPDATE'
        );
        $statement->execute([$roomId]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /** Menjalankan proses booking exists pada booking schedule. */
    private function bookingExists(int $bookingId): bool
    {
        $statement = $this->db->prepare('SELECT id FROM bookings WHERE id = ?');
        $statement->execute([$bookingId]);
        return (bool) $statement->fetchColumn();
    }

    /** Mengunci booking selama transaksi. */
    private function lockBooking(int $bookingId)
    {
        $statement = $this->db->prepare(
            'SELECT id, user_id, room_id, status FROM bookings WHERE id = ? FOR UPDATE'
        );
        $statement->execute([$bookingId]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /** Memvalidasi room. */
    private function validateRoom($room, int $attendeeCount): ?array
    {
        if (!$room) return ['success' => false, 'reason' => 'room_not_found'];
        if (($room['status'] ?? 'available') === 'maintenance') {
            return ['success' => false, 'reason' => 'maintenance', 'room' => $room];
        }
        if ($attendeeCount > (int) $room['capacity']) {
            return ['success' => false, 'reason' => 'insufficient_capacity', 'room' => $room];
        }
        return null;
    }

    /** Mengunci conflicts selama transaksi. */
    private function lockConflicts(array $data, int $excludeId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, status, start_time, end_time
             FROM bookings
             WHERE room_id = ? AND date = ? AND id != ?
               AND status IN ('confirmed', 'pending')
               AND start_time < ? AND end_time > ?
             ORDER BY CASE WHEN status = 'confirmed' THEN 0 ELSE 1 END, start_time ASC
             FOR UPDATE"
        );
        $statement->execute([
            (int) $data['room_id'],
            $data['date'],
            $excludeId,
            $data['end_time'],
            $data['start_time'],
        ]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Menentukan conflicts. */
    private function classifyConflicts(array $conflicts): array
    {
        $confirmed = null;
        $hasPending = false;
        foreach ($conflicts as $conflict) {
            if ($conflict['status'] === 'confirmed') {
                $confirmed = $conflict;
                break;
            }
            $hasPending = true;
        }
        return ['confirmed' => $confirmed, 'has_pending' => $hasPending];
    }

    /** Menambahkan data booking schedule. */
    private function insert(array $data): bool
    {
        $activityType = $data['activity_type'] ?? 'internal_divisi';
        try {
            $statement = $this->db->prepare(
                'INSERT INTO bookings
                    (user_id, room_id, title, date, start_time, end_time, purpose,
                     activity_type, attendees_count, status, user_name, user_dept)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $success = $statement->execute([
                $data['user_id'], $data['room_id'], $data['title'], $data['date'],
                $data['start_time'], $data['end_time'], $data['purpose'], $activityType,
                $data['attendees_count'], $data['status'], $data['user_name'] ?? null,
                $data['user_dept'] ?? null,
            ]);
        } catch (PDOException $exception) {
            if (!$this->isMissingColumnError($exception)) throw $exception;
            $statement = $this->db->prepare(
                'INSERT INTO bookings
                    (user_id, room_id, title, date, start_time, end_time, purpose,
                     attendees_count, status, user_name, user_dept)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $success = $statement->execute([
                $data['user_id'], $data['room_id'], $data['title'], $data['date'],
                $data['start_time'], $data['end_time'], $data['purpose'],
                $data['attendees_count'], $data['status'], $data['user_name'] ?? null,
                $data['user_dept'] ?? null,
            ]);
        }

        if ($success) $this->lastInsertId = (int) $this->db->lastInsertId();
        return $success;
    }

    /** Memperbarui booking schedule. */
    private function update(int $bookingId, array $data): bool
    {
        $activityType = $data['activity_type'] ?? 'internal_divisi';
        $statusReason = array_key_exists('status_reason', $data) ? $data['status_reason'] : null;
        $adminNotes = array_key_exists('admin_notes', $data) ? $data['admin_notes'] : null;

        try {
            if ($statusReason !== null || $adminNotes !== null) {
                $statement = $this->db->prepare(
                    'UPDATE bookings
                     SET room_id = ?, title = ?, date = ?, start_time = ?, end_time = ?,
                         purpose = ?, activity_type = ?, attendees_count = ?, user_name = ?, user_dept = ?,
                         status_reason = COALESCE(?, status_reason),
                         admin_notes = COALESCE(?, admin_notes)
                     WHERE id = ?'
                );
                return $statement->execute([
                    $data['room_id'], $data['title'], $data['date'], $data['start_time'],
                    $data['end_time'], $data['purpose'], $activityType, $data['attendees_count'],
                    $data['user_name'] ?? null, $data['user_dept'] ?? null,
                    $statusReason, $adminNotes,
                    $bookingId,
                ]);
            }

            $statement = $this->db->prepare(
                'UPDATE bookings
                 SET room_id = ?, title = ?, date = ?, start_time = ?, end_time = ?,
                     purpose = ?, activity_type = ?, attendees_count = ?, user_name = ?, user_dept = ?
                 WHERE id = ?'
            );
            return $statement->execute([
                $data['room_id'], $data['title'], $data['date'], $data['start_time'],
                $data['end_time'], $data['purpose'], $activityType, $data['attendees_count'],
                $data['user_name'] ?? null, $data['user_dept'] ?? null, $bookingId,
            ]);
        } catch (PDOException $exception) {
            if (!$this->isMissingColumnError($exception)) throw $exception;
            $statement = $this->db->prepare(
                'UPDATE bookings
                 SET room_id = ?, title = ?, date = ?, start_time = ?, end_time = ?,
                     purpose = ?, attendees_count = ?, user_name = ?, user_dept = ?
                 WHERE id = ?'
            );
            return $statement->execute([
                $data['room_id'], $data['title'], $data['date'], $data['start_time'],
                $data['end_time'], $data['purpose'], $data['attendees_count'],
                $data['user_name'] ?? null, $data['user_dept'] ?? null, $bookingId,
            ]);
        }
    }

    /** Memeriksa apakah missing column error terpenuhi. */
    private function isMissingColumnError(PDOException $exception): bool
    {
        return $exception->getCode() === '42S22'
            || (int) ($exception->errorInfo[1] ?? 0) === 1054;
    }

    /** Membatalkan transaksi dan mengembalikan hasil kegagalan. */
    private function rollbackWith(array $result): array
    {
        if ($this->db->inTransaction()) $this->db->rollBack();
        return $result;
    }

    /** Menjalankan proses transaction failure pada booking schedule. */
    private function transactionFailure(string $context, Throwable $exception): array
    {
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log($context . ': ' . $exception->getMessage());
        return ['success' => false, 'reason' => 'database_error'];
    }
}

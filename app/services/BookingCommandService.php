<?php

/**
 * Menangani perubahan status dan penghapusan booking di luar proses penjadwalan.
 */
final class BookingCommandService
{
    /** Menyiapkan dependensi yang dibutuhkan oleh BookingCommandService. */
    public function __construct(private PDO $db)
    {
    }

    /** Membatalkan booking yang masih diizinkan oleh aturan status dan kepemilikan. */
    public function cancel(int $bookingId, int $userId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            $statement = $this->db->prepare(
                "UPDATE bookings SET status = 'cancelled', status_reason = ? WHERE id = ?"
            );
            return $statement->execute([
                BookingLifecycleService::REASON_CANCELLED_BY_ADMIN,
                $bookingId,
            ]);
        }

        $statement = $this->db->prepare(
            "UPDATE bookings SET status = 'cancelled', status_reason = ?
             WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')"
        );
        return $statement->execute([
            BookingLifecycleService::REASON_CANCELLED_BY_USER,
            $bookingId,
            $userId,
        ]);
    }

    /** Membatalkan booking oleh admin dengan menyimpan catatan alasan pembatalan. */
    public function cancelByAdmin(int $bookingId, string $reason): bool
    {
        $cleanReason = trim($reason);
        $statement = $this->db->prepare(
            "UPDATE bookings SET status = 'cancelled', status_reason = ?, admin_notes = ? WHERE id = ?"
        );
        return $statement->execute([
            BookingLifecycleService::REASON_CANCELLED_BY_ADMIN,
            $cleanReason !== '' ? $cleanReason : 'Dibatalkan oleh Administrator.',
            $bookingId,
        ]);
    }

    /** Mengalihkan ruangan booking ke ruangan alternatif dengan verifikasi bentrok jadwal. */
    public function relocateRoom(int $bookingId, int $newRoomId, string $reason): array
    {
        if ($bookingId <= 0 || $newRoomId <= 0) {
            return ['success' => false, 'message' => 'Pemesanan atau ruangan pengganti tidak valid.'];
        }

        $bStmt = $this->db->prepare('SELECT id, room_id, date, start_time, end_time, title, user_id FROM bookings WHERE id = ?');
        $bStmt->execute([$bookingId]);
        $booking = $bStmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            return ['success' => false, 'message' => 'Data pemesanan tidak ditemukan.'];
        }

        if ((int) $booking['room_id'] === $newRoomId) {
            return ['success' => false, 'message' => 'Ruangan tujuan sama dengan ruangan pemesanan saat ini.'];
        }

        $rStmt = $this->db->prepare('SELECT id, name, status FROM rooms WHERE id IN (?, ?)');
        $rStmt->execute([(int) $booking['room_id'], $newRoomId]);
        $rooms = $rStmt->fetchAll(PDO::FETCH_ASSOC);
        $roomsById = [];
        foreach ($rooms as $r) {
            $roomsById[(int) $r['id']] = $r;
        }

        $oldRoomName = $roomsById[(int) $booking['room_id']]['name'] ?? 'Ruangan Asal';
        $newRoom = $roomsById[$newRoomId] ?? null;
        if (!$newRoom || ($newRoom['status'] ?? '') === 'maintenance') {
            return ['success' => false, 'message' => 'Ruangan tujuan tidak ditemukan atau sedang dalam perbaikan (maintenance).'];
        }
        $newRoomName = $newRoom['name'];

        // Cek bentrok jadwal di ruangan baru pada tanggal dan rentang jam tersebut
        $conflictStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE room_id = ? AND date = ? AND status = 'confirmed' AND id != ?
               AND start_time < ? AND end_time > ?"
        );
        $conflictStmt->execute([
            $newRoomId,
            $booking['date'],
            $bookingId,
            $booking['end_time'],
            $booking['start_time']
        ]);
        if ((int) $conflictStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => "Ruangan {$newRoomName} sudah terisi jadwal lain pada waktu tersebut."];
        }

        $cleanReason = trim($reason);
        $notesText = 'Ruangan dialihkan dari ' . $oldRoomName . ' ke ' . $newRoomName . '.' . ($cleanReason !== '' ? ' Alasan: ' . $cleanReason : '');
        $updateStmt = $this->db->prepare(
            "UPDATE bookings SET room_id = ?, status = 'confirmed', status_reason = ?, admin_notes = ? WHERE id = ?"
        );
        $ok = $updateStmt->execute([
            $newRoomId,
            BookingLifecycleService::REASON_RELOCATED_BY_ADMIN,
            $notesText,
            $bookingId,
        ]);

        return [
            'success' => $ok,
            'message' => $ok ? "Ruangan berhasil dialihkan ke {$newRoomName}." : 'Gagal memperbarui data pengalihan ruangan.',
            'booking_id' => $bookingId,
            'user_id' => (int) $booking['user_id'],
            'old_room_name' => $oldRoomName,
            'new_room_name' => $newRoomName,
            'reason' => $cleanReason,
        ];
    }

    /** Memperbarui status. */
    public function updateStatus(int $bookingId, string $status): bool
    {
        if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
            return false;
        }
        $reason = $status === 'cancelled'
            ? BookingLifecycleService::REASON_CANCELLED_BY_ADMIN
            : null;
        $statement = $this->db->prepare(
            'UPDATE bookings SET status = ?, status_reason = ? WHERE id = ?'
        );
        return $statement->execute([$status, $reason, $bookingId]);
    }

    /** Menghapus data booking command beserta relasi terkait. */
    public function delete(int $bookingId): bool
    {
        $documents = $this->documentFiles($bookingId);
        $statement = $this->db->prepare('DELETE FROM bookings WHERE id = ?');
        $deleted = $statement->execute([$bookingId]);
        if ($deleted) $this->removeDocumentFiles($documents);
        return $deleted;
    }

    /** Menjalankan proses document files pada booking command. */
    private function documentFiles(int $bookingId): array
    {
        if ($bookingId <= 0) return [];
        try {
            $statement = $this->db->prepare(
                'SELECT stored_name FROM booking_documents WHERE booking_id = ?'
            );
            $statement->execute([$bookingId]);
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $exception) {
            error_log('Gagal membaca file dokumen booking: ' . $exception->getMessage());
            return [];
        }
    }

    /** Menghapus atau mereset document files. */
    private function removeDocumentFiles(array $storedNames): void
    {
        if (!$storedNames) return;
        $documents = new BookingDocumentService();
        foreach ($storedNames as $storedName) {
            $documents->remove((string) $storedName);
        }
    }
}

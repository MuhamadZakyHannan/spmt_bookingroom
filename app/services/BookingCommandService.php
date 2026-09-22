<?php

/**
 * Menangani perubahan status dan penghapusan booking di luar proses penjadwalan.
 */
final class BookingCommandService
{
    public function __construct(private PDO $db)
    {
    }

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

    public function delete(int $bookingId): bool
    {
        $documents = $this->documentFiles($bookingId);
        $statement = $this->db->prepare('DELETE FROM bookings WHERE id = ?');
        $deleted = $statement->execute([$bookingId]);
        if ($deleted) $this->removeDocumentFiles($documents);
        return $deleted;
    }

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

    private function removeDocumentFiles(array $storedNames): void
    {
        if (!$storedNames) return;
        $documents = new BookingDocumentService();
        foreach ($storedNames as $storedName) {
            $documents->remove((string) $storedName);
        }
    }
}

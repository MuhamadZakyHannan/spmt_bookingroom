<?php
require_once __DIR__ . '/../core/BaseModel.php';

class BookingDocumentModel extends BaseModel
{
    public const TYPE_SUPPORTING_DOCUMENT = 'supporting_document';

    /** Mengambil data for booking. */
    public function getForBooking(int $bookingId, string $documentType = self::TYPE_SUPPORTING_DOCUMENT)
    {
        if (!$this->db || $bookingId <= 0) return false;
        $statement = $this->db->prepare(
            'SELECT * FROM booking_documents WHERE booking_id = ? AND document_type = ? LIMIT 1'
        );
        $statement->execute([$bookingId, $documentType]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /** Mengambil data by id. */
    public function getById(int $documentId)
    {
        if (!$this->db || $documentId <= 0) return false;
        $statement = $this->db->prepare(
            "SELECT d.*, b.user_id AS booking_user_id, b.status AS booking_status
             FROM booking_documents d
             JOIN bookings b ON b.id = d.booking_id
             WHERE d.id = ?
             LIMIT 1"
        );
        $statement->execute([$documentId]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Menyimpan atau mengganti satu jenis dokumen dan mengembalikan nama file lama.
     */
    public function replace(int $bookingId, int $uploadedBy, array $metadata): array
    {
        if (!$this->db) return ['success' => false, 'reason' => 'database_unavailable'];

        try {
            $this->db->beginTransaction();
            $currentStatement = $this->db->prepare(
                'SELECT id, stored_name FROM booking_documents WHERE booking_id = ? AND document_type = ? FOR UPDATE'
            );
            $currentStatement->execute([$bookingId, self::TYPE_SUPPORTING_DOCUMENT]);
            $current = $currentStatement->fetch(PDO::FETCH_ASSOC);

            $statement = $this->db->prepare(
                "INSERT INTO booking_documents
                    (booking_id, document_type, original_name, stored_name, mime_type, size_bytes, sha256, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    original_name = VALUES(original_name),
                    stored_name = VALUES(stored_name),
                    mime_type = VALUES(mime_type),
                    size_bytes = VALUES(size_bytes),
                    sha256 = VALUES(sha256),
                    uploaded_by = VALUES(uploaded_by),
                    updated_at = CURRENT_TIMESTAMP"
            );
            $statement->execute([
                $bookingId,
                self::TYPE_SUPPORTING_DOCUMENT,
                $metadata['original_name'],
                $metadata['stored_name'],
                $metadata['mime_type'],
                $metadata['size_bytes'],
                $metadata['sha256'],
                $uploadedBy,
            ]);

            $documentId = $current
                ? (int) $current['id']
                : (int) $this->db->lastInsertId();
            $this->db->commit();

            return [
                'success' => true,
                'document_id' => $documentId,
                'old_stored_name' => $current['stored_name'] ?? null,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Booking document metadata error: ' . $exception->getMessage());
            return ['success' => false, 'reason' => 'database_error'];
        }
    }
}

<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Menjaga transisi status booking yang ditentukan oleh waktu.
 */
class BookingLifecycleService
{
    public const REASON_EXPIRED = 'expired';
    public const REASON_CANCELLED_BY_USER = 'cancelled_by_user';
    public const REASON_CANCELLED_BY_ADMIN = 'cancelled_by_admin';
    public const REASON_CONFLICT_NOT_SELECTED = 'conflict_not_selected';

    private $db;

    public function __construct($connection = null)
    {
        $this->db = $connection ?: Database::getInstance()->getConnection();
    }

    /**
     * Membatalkan pengajuan yang belum disetujui ketika waktu mulai telah tiba.
     */
    public function expirePendingBookings(?DateTimeInterface $now = null): int
    {
        if (!$this->db) return 0;

        $reference = $now ?: new DateTimeImmutable('now');
        $statement = $this->db->prepare(
            "UPDATE bookings
             SET status = 'cancelled', status_reason = ?
             WHERE status = 'pending'
               AND TIMESTAMP(date, start_time) <= ?"
        );
        $statement->execute([
            self::REASON_EXPIRED,
            $reference->format('Y-m-d H:i:s'),
        ]);
        $expiredCount = $statement->rowCount();

        if ($expiredCount > 0) {
            $notificationStatement = $this->db->prepare(
                "UPDATE notifications n
                 JOIN bookings b ON b.id = n.booking_id
                 SET n.is_read = 1, n.read_at = ?
                 WHERE n.type = 'booking_pending'
                   AND n.is_read = 0
                   AND b.status = 'cancelled'
                   AND b.status_reason = ?"
            );
            $notificationStatement->execute([
                $reference->format('Y-m-d H:i:s'),
                self::REASON_EXPIRED,
            ]);
        }

        return $expiredCount;
    }
}

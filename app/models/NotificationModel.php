<?php
require_once __DIR__ . '/../core/BaseModel.php';

class NotificationModel extends BaseModel {

    /**
     * Membuat satu notifikasi untuk setiap akun admin ketika booking baru pending.
     * Kegagalan notifikasi tidak boleh membatalkan booking yang sudah tersimpan.
     */
    public function createForPendingBooking($bookingId) {
        if (!$this->db || $bookingId <= 0) return false;

        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO notifications
                    (recipient_user_id, booking_id, type, title, message)
                 SELECT
                    u.id,
                    b.id,
                    'booking_pending',
                    'Booking baru menunggu persetujuan',
                    CONCAT(IFNULL(b.user_name, requester.name), ' mengajukan ', b.title, ' di ', r.name)
                 FROM bookings b
                 JOIN users requester ON requester.id = b.user_id
                 JOIN rooms r ON r.id = b.room_id
                 CROSS JOIN users u
                 WHERE b.id = ?
                   AND b.status = 'pending'
                   AND u.role IN ('admin', 'super_admin')"
            );
            return $stmt->execute([$bookingId]);
        } catch (Throwable $e) {
            error_log('Gagal membuat notifikasi booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Menyegarkan isi notifikasi setelah pengajuan pending diedit dan memastikan
     * administrator baru tetap menerima notifikasi untuk booking tersebut.
     */
    public function refreshForPendingBooking(int $bookingId): bool {
        if (!$this->db || $bookingId <= 0) return false;

        try {
            $statement = $this->db->prepare(
                "UPDATE notifications n
                 JOIN bookings b ON b.id = n.booking_id
                 JOIN users requester ON requester.id = b.user_id
                 JOIN rooms r ON r.id = b.room_id
                 SET n.title = 'Booking diperbarui dan menunggu persetujuan',
                     n.message = CONCAT(IFNULL(b.user_name, requester.name), ' memperbarui ', b.title, ' di ', r.name),
                     n.is_read = 0,
                     n.read_at = NULL
                 WHERE n.booking_id = ?
                   AND n.type = 'booking_pending'
                   AND b.status = 'pending'"
            );
            $statement->execute([$bookingId]);
            return $this->createForPendingBooking($bookingId);
        } catch (Throwable $exception) {
            error_log('Gagal menyegarkan notifikasi booking: ' . $exception->getMessage());
            return false;
        }
    }

    public function getUnreadCount($recipientUserId) {
        if (!$this->db || $recipientUserId <= 0) return 0;

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM notifications
                 WHERE recipient_user_id = ? AND is_read = 0"
            );
            $stmt->execute([$recipientUserId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Gagal membaca jumlah notifikasi: ' . $e->getMessage());
            return 0;
        }
    }

    public function markAllAsRead($recipientUserId) {
        if (!$this->db || $recipientUserId <= 0) return false;

        try {
            $stmt = $this->db->prepare(
                "UPDATE notifications
                 SET is_read = 1, read_at = NOW()
                 WHERE recipient_user_id = ? AND is_read = 0"
            );
            return $stmt->execute([$recipientUserId]);
        } catch (Throwable $e) {
            error_log('Gagal menandai notifikasi sebagai dibaca: ' . $e->getMessage());
            return false;
        }
    }
}

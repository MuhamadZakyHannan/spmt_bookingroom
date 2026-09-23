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

    /**
     * Membuat notifikasi untuk pemilik booking saat dibatalkan oleh admin beserta alasannya.
     */
    public function createForCancelledBooking(int $bookingId, string $reason): bool {
        if (!$this->db || $bookingId <= 0) return false;

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications
                    (recipient_user_id, booking_id, type, title, message)
                 SELECT
                    b.user_id,
                    b.id,
                    'booking_cancelled_by_admin',
                    'Pemesanan Dibatalkan oleh Admin',
                    CONCAT('Pemesanan \"', b.title, '\" dibatalkan. Alasan: ', ?)
                 FROM bookings b
                 WHERE b.id = ?
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    message = VALUES(message),
                    is_read = 0,
                    read_at = NULL,
                    created_at = NOW()"
            );
            $cleanReason = mb_substr(trim($reason) !== '' ? trim($reason) : 'Jadwal dibatalkan oleh Administrator.', 0, 180);
            return $stmt->execute([$cleanReason, $bookingId]);
        } catch (Throwable $e) {
            error_log('Gagal membuat notifikasi pembatalan booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Membuat notifikasi untuk pemilik booking saat ruangan dialihkan oleh admin beserta alasannya.
     */
    public function createForRelocatedBooking(int $bookingId, string $oldRoomName, string $newRoomName, string $reason): bool {
        if (!$this->db || $bookingId <= 0) return false;

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications
                    (recipient_user_id, booking_id, type, title, message)
                 SELECT
                    b.user_id,
                    b.id,
                    'booking_relocated_by_admin',
                    'Ruangan Rapat Dialihkan oleh Admin',
                    CONCAT('Pemesanan \"', b.title, '\" dialihkan dari ', ?, ' ke ', ?, '. Alasan: ', ?)
                 FROM bookings b
                 WHERE b.id = ?
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    message = VALUES(message),
                    is_read = 0,
                    read_at = NULL,
                    created_at = NOW()"
            );
            $cleanReason = mb_substr(trim($reason) !== '' ? trim($reason) : 'Perubahan operasional ruangan.', 0, 140);
            return $stmt->execute([$oldRoomName, $newRoomName, $cleanReason, $bookingId]);
        } catch (Throwable $e) {
            error_log('Gagal membuat notifikasi pengalihan ruangan: ' . $e->getMessage());
            return false;
        }
    }

    /** Mengambil data unread count. */
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

    /** Menjalankan proses mark all as read pada notification. */
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

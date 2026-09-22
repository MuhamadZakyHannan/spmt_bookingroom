<?php
require_once __DIR__ . '/../core/Database.php';

class NotificationModel {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

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
                   AND u.role = 'admin'"
            );
            return $stmt->execute([$bookingId]);
        } catch (Throwable $e) {
            error_log('Gagal membuat notifikasi booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Membuat notifikasi attendance untuk semua admin.
     * Definisi pesan ditempatkan di sini agar service tidak mengetahui format UI.
     */
    public function createForAttendanceEvent($bookingId, $type) {
        $content = [
        ];

        if (!$this->db || $bookingId <= 0 || !isset($content[$type])) return false;

        [$title, $verb] = $content[$type];

        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO notifications
                    (recipient_user_id, booking_id, type, title, message)
                 SELECT u.id, b.id, ?, ?,
                        CONCAT(IFNULL(b.user_name, requester.name), ?, b.title, ' di ', r.name)
                 FROM bookings b
                 JOIN users requester ON requester.id = b.user_id
                 JOIN rooms r ON r.id = b.room_id
                 CROSS JOIN users u
                 WHERE b.id = ? AND u.role = 'admin'"
            );
            return $stmt->execute([$type, $title, $verb, $bookingId]);
        } catch (Throwable $e) {
            error_log('Gagal membuat notifikasi attendance: ' . $e->getMessage());
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

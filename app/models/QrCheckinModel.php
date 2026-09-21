<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AttendancePolicy.php';

class QrCheckinModel {
    public const TOKEN_TTL_SECONDS = 45;

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Menerbitkan token opaque untuk booking yang sedang berada di jendela
     * check-in. Token hanya diberikan kepada URL display dengan token terdaftar.
     */
    public function issueForDisplay($displayToken) {
        if (!$this->db || empty($displayToken)) return null;

        $displayStmt = $this->db->prepare(
            "SELECT d.id, d.room_id, d.display_name, r.name AS room_name
             FROM room_displays d
             JOIN rooms r ON r.id = d.room_id
             WHERE d.display_token = ?"
        );
        $displayStmt->execute([$displayToken]);
        $display = $displayStmt->fetch();
        if (!$display) return null;

        $now = date('Y-m-d H:i:s');
        $bookingStmt = $this->db->prepare(
            "SELECT b.id, b.title, b.start_time, b.end_time, b.user_id
             FROM bookings b
             WHERE b.room_id = ?
               AND b.status = 'confirmed'
               AND b.attendance_status = 'scheduled'
               AND ? >= DATE_SUB(TIMESTAMP(b.date, b.start_time), INTERVAL 15 MINUTE)
               AND ? <= DATE_ADD(TIMESTAMP(b.date, b.start_time), INTERVAL 15 MINUTE)
             ORDER BY b.start_time ASC, b.id ASC
             LIMIT 1"
        );
        $bookingStmt->execute([$display['room_id'], $now, $now]);
        $booking = $bookingStmt->fetch();
        if (!$booking) return null;

        $rawToken = $this->base64UrlEncode(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);

        try {
            $this->db->beginTransaction();
            $cleanup = $this->db->prepare(
                "DELETE FROM booking_checkin_tokens
                 WHERE expires_at < DATE_SUB(?, INTERVAL 1 DAY)"
            );
            $cleanup->execute([$now]);

            $insert = $this->db->prepare(
                "INSERT INTO booking_checkin_tokens
                    (booking_id, room_id, display_id, token_hash, expires_at)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insert->execute([
                $booking['id'],
                $display['room_id'],
                $display['id'],
                $tokenHash,
                $expiresAt,
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Gagal menerbitkan token QR: ' . $e->getMessage());
            return null;
        }

        return [
            'token' => $rawToken,
            'expires_at' => $expiresAt,
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'booking_id' => (int)$booking['id'],
            'booking_title' => $booking['title'],
            'room_name' => $display['room_name'],
            'start_time' => substr($booking['start_time'], 0, 5),
            'end_time' => substr($booking['end_time'], 0, 5),
        ];
    }

    public function inspect($rawToken, $userId) {
        if (!$this->db || empty($rawToken) || $userId <= 0) {
            return ['valid' => false, 'message' => 'Token QR tidak valid.'];
        }

        $stmt = $this->db->prepare(
            "SELECT t.id AS token_id, t.expires_at, t.used_at,
                    b.*, r.name AS room_name, r.code AS room_code
             FROM booking_checkin_tokens t
             JOIN bookings b ON b.id = t.booking_id
             JOIN rooms r ON r.id = t.room_id
             WHERE t.token_hash = ?"
        );
        $stmt->execute([hash('sha256', $rawToken)]);
        $context = $stmt->fetch();

        return $this->validateContext($context, $userId);
    }

    public function consume($rawToken, $userId) {
        if (!$this->db || empty($rawToken) || $userId <= 0) {
            return ['success' => false, 'message' => 'Token QR tidak valid.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT t.id AS token_id, t.expires_at, t.used_at,
                        t.room_id AS token_room_id, t.display_id,
                        b.*, r.name AS room_name, r.code AS room_code
                 FROM booking_checkin_tokens t
                 JOIN bookings b ON b.id = t.booking_id
                 JOIN rooms r ON r.id = t.room_id
                 WHERE t.token_hash = ?
                 FOR UPDATE"
            );
            $stmt->execute([hash('sha256', $rawToken)]);
            $context = $stmt->fetch();
            $validation = $this->validateContext($context, $userId);

            if (!$validation['valid']) {
                throw new DomainException($validation['message']);
            }

            $now = date('Y-m-d H:i:s');
            $bookingUpdate = $this->db->prepare(
                "UPDATE bookings
                 SET attendance_status = 'checked_in', check_in_at = ?, attendance_updated_by = 'user'
                 WHERE id = ? AND user_id = ?
                   AND status = 'confirmed' AND attendance_status = 'scheduled'"
            );
            $bookingUpdate->execute([$now, $context['id'], $userId]);
            if ($bookingUpdate->rowCount() !== 1) {
                throw new DomainException('Booking sudah berubah atau telah melakukan check-in.');
            }

            $tokenUpdate = $this->db->prepare(
                "UPDATE booking_checkin_tokens
                 SET used_at = ?, used_by_user_id = ?
                 WHERE id = ? AND used_at IS NULL"
            );
            $tokenUpdate->execute([$now, $userId, $context['token_id']]);
            if ($tokenUpdate->rowCount() !== 1) {
                throw new DomainException('QR sudah pernah digunakan.');
            }

            // Token lain untuk booking yang sama langsung dinonaktifkan.
            $invalidate = $this->db->prepare(
                "UPDATE booking_checkin_tokens
                 SET expires_at = LEAST(expires_at, ?)
                 WHERE booking_id = ? AND used_at IS NULL"
            );
            $invalidate->execute([$now, $context['id']]);

            $this->createAdminNotification((int)$context['id']);
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Check-in QR berhasil. Ruangan kini ditandai sedang digunakan.',
                'booking_id' => (int)$context['id'],
            ];
        } catch (DomainException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Gagal memakai token QR: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Check-in QR gagal diproses.'];
        }
    }

    private function validateContext($context, $userId) {
        if (!$context) {
            return ['valid' => false, 'message' => 'Token QR tidak ditemukan. Pindai QR terbaru pada display pintu.'];
        }
        if (!empty($context['used_at'])) {
            return ['valid' => false, 'message' => 'QR ini sudah pernah digunakan. Pindai QR terbaru.'];
        }
        if (strtotime($context['expires_at']) <= time()) {
            return ['valid' => false, 'message' => 'QR telah kedaluwarsa. Pindai QR terbaru pada display pintu.'];
        }
        if ((int)$context['user_id'] !== (int)$userId) {
            return ['valid' => false, 'message' => 'QR ini hanya dapat digunakan oleh akun pemilik booking.'];
        }
        if (($context['status'] ?? '') !== 'confirmed' || ($context['attendance_status'] ?? '') !== 'scheduled') {
            return ['valid' => false, 'message' => 'Booking tidak lagi menunggu check-in.'];
        }

        $state = AttendancePolicy::getActionState($context);
        if (!$state['can_check_in']) {
            return ['valid' => false, 'message' => $state['message'] ?: 'Check-in belum tersedia.'];
        }

        $context['valid'] = true;
        $context['message'] = 'QR siap digunakan.';
        return $context;
    }

    private function createAdminNotification($bookingId) {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO notifications
                (recipient_user_id, booking_id, type, title, message)
             SELECT u.id, b.id, 'attendance_check_in', 'Check-in QR ruang rapat',
                    CONCAT(IFNULL(b.user_name, requester.name), ' telah check-in via QR untuk ', b.title, ' di ', r.name)
             FROM bookings b
             JOIN users requester ON requester.id = b.user_id
             JOIN rooms r ON r.id = b.room_id
             CROSS JOIN users u
             WHERE b.id = ? AND u.role = 'admin'"
        );
        return $stmt->execute([$bookingId]);
    }

    private function base64UrlEncode($bytes) {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

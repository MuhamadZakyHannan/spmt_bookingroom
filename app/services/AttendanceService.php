<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AttendancePolicy.php';
require_once __DIR__ . '/../models/NotificationModel.php';

/**
 * Menangani seluruh perubahan status attendance dan batas transaksi database.
 * BookingModel tetap menyediakan API lama sebagai facade untuk kompatibilitas.
 */
class AttendanceService {
    private $db;
    private $notifications;

    public function __construct($db = null, $notifications = null) {
        $this->db = $db ?: Database::getInstance()->getConnection();
        $this->notifications = $notifications ?: new NotificationModel($this->db);
    }

    public function processAutomaticTransitions(): array {
        $result = ['no_show' => [], 'checked_out' => []];
        if (!$this->db) return $result;

        $now = date('Y-m-d H:i:s');
        $graceMinutes = AttendancePolicy::graceMinutes();

        try {
            $this->db->beginTransaction();

            $noShowStmt = $this->db->prepare(
                "SELECT id FROM bookings
                 WHERE status = 'confirmed'
                   AND attendance_status = 'scheduled'
                   AND DATE_ADD(TIMESTAMP(date, start_time), INTERVAL {$graceMinutes} MINUTE) < ?
                 FOR UPDATE"
            );
            $noShowStmt->execute([$now]);
            $result['no_show'] = array_map('intval', $noShowStmt->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($result['no_show'])) {
                $this->updateAutomaticState(
                    $result['no_show'],
                    "status = 'completed', attendance_status = 'no_show', no_show_at = ?",
                    $now,
                    'attendance_no_show'
                );
            }

            $checkOutStmt = $this->db->prepare(
                "SELECT id FROM bookings
                 WHERE status = 'confirmed'
                   AND attendance_status = 'checked_in'
                   AND TIMESTAMP(date, end_time) <= ?
                 FOR UPDATE"
            );
            $checkOutStmt->execute([$now]);
            $result['checked_out'] = array_map('intval', $checkOutStmt->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($result['checked_out'])) {
                $this->updateAutomaticState(
                    $result['checked_out'],
                    "status = 'completed', attendance_status = 'checked_out', check_out_at = ?",
                    $now,
                    'attendance_check_out'
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            error_log('Gagal memproses attendance otomatis: ' . $e->getMessage());
        }

        return $result;
    }

    public function checkIn($bookingId, $userId): array {
        if (!$this->hasValidIdentifiers($bookingId, $userId)) {
            return $this->failure('Data booking tidak valid.');
        }

        try {
            $this->db->beginTransaction();
            $booking = $this->findOwnedBookingForUpdate($bookingId, $userId);

            if (!$booking) {
                throw new DomainException('Booking tidak ditemukan atau bukan milik akun Anda.');
            }

            $state = AttendancePolicy::getActionState($booking);
            if (!$state['can_check_in']) {
                throw new DomainException($state['message'] ?: 'Check-in tidak tersedia untuk booking ini.');
            }

            $now = date('Y-m-d H:i:s');
            $update = $this->db->prepare(
                "UPDATE bookings
                 SET attendance_status = 'checked_in', check_in_at = ?, attendance_updated_by = 'user'
                 WHERE id = ? AND user_id = ? AND status = 'confirmed' AND attendance_status = 'scheduled'"
            );
            $update->execute([$now, $bookingId, $userId]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Status booking berubah. Silakan muat ulang halaman.');
            }

            $this->notifications->createForAttendanceEvent($bookingId, 'attendance_check_in');
            $this->db->commit();
            return ['success' => true, 'message' => 'Check-in berhasil. Ruangan kini ditandai sedang digunakan.'];
        } catch (DomainException $e) {
            $this->rollBack();
            return $this->failure($e->getMessage());
        } catch (Throwable $e) {
            $this->rollBack();
            error_log('Gagal check-in: ' . $e->getMessage());
            return $this->failure('Check-in gagal diproses.');
        }
    }

    public function checkOut($bookingId, $userId): array {
        if (!$this->hasValidIdentifiers($bookingId, $userId)) {
            return $this->failure('Data booking tidak valid.');
        }

        try {
            $this->db->beginTransaction();
            $booking = $this->findOwnedBookingForUpdate($bookingId, $userId);

            if (!$booking) {
                throw new DomainException('Booking tidak ditemukan atau bukan milik akun Anda.');
            }

            $state = AttendancePolicy::getActionState($booking);
            if (!$state['can_check_out']) {
                throw new DomainException('Check-out hanya tersedia setelah akun Anda melakukan check-in.');
            }

            $now = date('Y-m-d H:i:s');
            $update = $this->db->prepare(
                "UPDATE bookings
                 SET status = 'completed', attendance_status = 'checked_out',
                     check_out_at = ?, attendance_updated_by = 'user'
                 WHERE id = ? AND user_id = ? AND status = 'confirmed' AND attendance_status = 'checked_in'"
            );
            $update->execute([$now, $bookingId, $userId]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Status booking berubah. Silakan muat ulang halaman.');
            }

            $this->notifications->createForAttendanceEvent($bookingId, 'attendance_check_out');
            $this->db->commit();
            return ['success' => true, 'message' => 'Check-out berhasil. Ruangan kembali tersedia.'];
        } catch (DomainException $e) {
            $this->rollBack();
            return $this->failure($e->getMessage());
        } catch (Throwable $e) {
            $this->rollBack();
            error_log('Gagal check-out: ' . $e->getMessage());
            return $this->failure('Check-out gagal diproses.');
        }
    }

    private function updateAutomaticState(array $bookingIds, string $assignments, string $now, string $notificationType): void {
        $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
        $params = array_merge([$now], $bookingIds);
        $update = $this->db->prepare(
            "UPDATE bookings
             SET {$assignments}, attendance_updated_by = 'system'
             WHERE id IN ({$placeholders})"
        );
        $update->execute($params);

        foreach ($bookingIds as $bookingId) {
            $this->notifications->createForAttendanceEvent($bookingId, $notificationType);
        }
    }

    private function findOwnedBookingForUpdate($bookingId, $userId) {
        $stmt = $this->db->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ? FOR UPDATE');
        $stmt->execute([$bookingId, $userId]);
        return $stmt->fetch();
    }

    private function hasValidIdentifiers($bookingId, $userId): bool {
        return $this->db && $bookingId > 0 && $userId > 0;
    }

    private function failure(string $message): array {
        return ['success' => false, 'message' => $message];
    }

    private function rollBack(): void {
        if ($this->db && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}

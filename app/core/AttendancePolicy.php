<?php

/**
 * Aturan waktu kehadiran booking. Semua DateTimeImmutable yang masuk harus
 * menggunakan zona waktu aplikasi (Asia/Jakarta).
 */
class AttendancePolicy {
    public const CHECK_IN_EARLY_MINUTES = 15;
    public const GRACE_MINUTES = 15;

    public static function getWindow(array $booking): array {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $start = new DateTimeImmutable(
            $booking['date'] . ' ' . $booking['start_time'],
            $timezone
        );
        $end = new DateTimeImmutable(
            $booking['date'] . ' ' . $booking['end_time'],
            $timezone
        );

        return [
            'start' => $start,
            'end' => $end,
            'check_in_opens_at' => $start->modify('-' . self::CHECK_IN_EARLY_MINUTES . ' minutes'),
            'check_in_deadline_at' => $start->modify('+' . self::GRACE_MINUTES . ' minutes'),
        ];
    }

    public static function getActionState(array $booking, ?DateTimeImmutable $now = null): array {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $now = $now ?: new DateTimeImmutable('now', $timezone);
        $window = self::getWindow($booking);
        $attendanceStatus = $booking['attendance_status'] ?? null;
        $bookingStatus = $booking['status'] ?? '';

        $state = [
            'can_check_in' => false,
            'can_check_out' => false,
            'state' => $attendanceStatus ?: 'not_required',
            'message' => '',
            'check_in_opens_at' => $window['check_in_opens_at']->format('Y-m-d H:i:s'),
            'check_in_deadline_at' => $window['check_in_deadline_at']->format('Y-m-d H:i:s'),
        ];

        if ($bookingStatus !== 'confirmed' || $attendanceStatus === null) {
            return $state;
        }

        if ($attendanceStatus === 'checked_in') {
            $state['can_check_out'] = $now < $window['end'];
            $state['message'] = $state['can_check_out']
                ? 'Rapat sedang berlangsung. Check-out tersedia.'
                : 'Menunggu auto check-out.';
            return $state;
        }

        if ($attendanceStatus !== 'scheduled') {
            return $state;
        }

        if ($now < $window['check_in_opens_at']) {
            $state['message'] = 'Check-in dibuka 15 menit sebelum jadwal.';
        } elseif ($now <= $window['check_in_deadline_at']) {
            $state['can_check_in'] = true;
            $state['message'] = 'Check-in tersedia sampai ' . $window['check_in_deadline_at']->format('H:i') . ' WIB.';
        } else {
            $state['message'] = 'Batas check-in telah lewat.';
        }

        return $state;
    }
}

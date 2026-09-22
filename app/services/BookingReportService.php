<?php

/**
 * Menyiapkan filter dan data presentasi untuk laporan booking.
 */
final class BookingReportService
{
    /** Menyiapkan dependensi laporan booking. */
    public function __construct(
        private BookingModel $bookings,
        private RoomModel $rooms
    ) {
    }

    /** Menyiapkan satu dataset konsisten untuk export CSV dan tampilan PDF. */
    public function prepare(array $input, string $generatedBy): array
    {
        $filters = self::normalizeFilters($input);
        $summary = $this->bookings->getBookingHistorySummary($filters);
        $roomId = (int) $filters['room_id'];

        return [
            'filters' => $filters,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'room_id' => $roomId,
            'status' => $filters['status'],
            'search' => $filters['search'],
            'summary' => $summary,
            'bookings' => $summary['bookings'],
            'totalBookings' => $summary['total_bookings'],
            'confirmedCount' => $summary['confirmed_count'],
            'pendingCount' => $summary['pending_count'],
            'totalHours' => $summary['total_hours'],
            'totalAttendees' => $summary['total_attendees'],
            'selectedRoom' => $roomId > 0 ? $this->rooms->getById($roomId) : null,
            'printDate' => date('d F Y, H:i'),
            'generatedBy' => $generatedBy,
        ];
    }

    /** Menormalkan filter laporan agar hanya nilai valid yang mencapai model. */
    public static function normalizeFilters(array $input): array
    {
        $status = trim((string) ($input['status'] ?? ''));
        if (!in_array($status, ['', 'pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $status = '';
        }

        $search = trim((string) ($input['search'] ?? ''));
        $search = function_exists('mb_substr') ? mb_substr($search, 0, 100) : substr($search, 0, 100);

        return [
            'start_date' => self::normalizeDate((string) ($input['start_date'] ?? '')),
            'end_date' => self::normalizeDate((string) ($input['end_date'] ?? '')),
            'room_id' => max(0, (int) ($input['room_id'] ?? 0)),
            'status' => $status,
            'search' => $search,
        ];
    }

    /** Mengubah booking menjadi baris CSV yang aman dibuka melalui spreadsheet. */
    public function csvRows(array $report): array
    {
        $rows = [];
        foreach ($report['bookings'] as $index => $booking) {
            $rows[] = array_map(
                [self::class, 'spreadsheetSafe'],
                [
                    $index + 1,
                    $booking['title'],
                    $booking['user_name'],
                    $booking['user_dept'] ?? 'Internal',
                    $booking['user_email'] ?? '-',
                    $booking['room_name'],
                    $booking['room_code'] ?? '-',
                    $booking['room_location'] ?? '-',
                    $booking['date'],
                    substr((string) $booking['start_time'], 0, 5),
                    substr((string) $booking['end_time'], 0, 5),
                    self::durationHours($booking),
                    $booking['attendees_count'] ?? 1,
                    $booking['purpose'] ?? '-',
                    booking_status_label($booking),
                    $booking['created_at'] ?? '-',
                ]
            );
        }
        return $rows;
    }

    /** Mengembalikan tanggal valid berformat ISO atau string kosong. */
    private static function normalizeDate(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) return '';
        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : '';
    }

    /** Menghitung durasi booking dalam satuan jam. */
    private static function durationHours(array $booking): float
    {
        $start = strtotime($booking['date'] . ' ' . $booking['start_time']);
        $end = strtotime($booking['date'] . ' ' . $booking['end_time']);
        return $end > $start ? round(($end - $start) / 3600, 2) : 0.0;
    }

    /** Mencegah spreadsheet mengeksekusi nilai teks sebagai formula. */
    private static function spreadsheetSafe($value)
    {
        if (!is_string($value)) return $value;
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }
}

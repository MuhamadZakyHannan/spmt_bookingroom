<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Menyajikan status ketersediaan ruangan untuk satu rentang waktu.
 *
 * Service ini hanya membaca data. Keputusan akhir ketika menyimpan booking
 * tetap dilakukan secara atomik oleh BookingModel agar tidak dapat dilewati
 * melalui manipulasi antarmuka.
 */
class RoomAvailabilityService
{
    private $db;

    public function __construct($connection = null)
    {
        $this->db = $connection ?: Database::getInstance()->getConnection();
    }

    public function getAvailability(string $date, string $startTime, string $endTime, int $attendeesCount): array
    {
        if (! $this->db) {
            throw new RuntimeException('Koneksi database tidak tersedia.');
        }

        $rooms = $this->db->query(
            'SELECT id, code, name, capacity, location, facilities, status
             FROM rooms
             ORDER BY name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $conflictStatement = $this->db->prepare(
            "SELECT room_id, status, start_time, end_time
             FROM bookings
             WHERE date = ?
               AND status IN ('pending', 'confirmed')
               AND start_time < ?
               AND end_time > ?
             ORDER BY start_time ASC"
        );
        $conflictStatement->execute([$date, $endTime, $startTime]);

        $conflictsByRoom = [];
        foreach ($conflictStatement->fetchAll(PDO::FETCH_ASSOC) as $conflict) {
            $conflictsByRoom[(int) $conflict['room_id']][] = $conflict;
        }

        $results = [];
        $summary = [
            'available' => 0,
            'pending_conflict' => 0,
            'confirmed_conflict' => 0,
            'unavailable' => 0,
        ];

        foreach ($rooms as $room) {
            $roomId = (int) $room['id'];
            $availability = $this->classifyRoom(
                $room,
                $conflictsByRoom[$roomId] ?? [],
                $attendeesCount
            );
            $summary[$availability['summary_group']]++;
            unset($availability['summary_group']);

            $results[] = array_merge([
                'id' => $roomId,
                'code' => (string) ($room['code'] ?? ''),
                'name' => (string) $room['name'],
                'capacity' => (int) $room['capacity'],
                'location' => (string) ($room['location'] ?? '-'),
            ], $availability);
        }

        return [
            'date' => $date,
            'start_time' => substr($startTime, 0, 5),
            'end_time' => substr($endTime, 0, 5),
            'attendees_count' => $attendeesCount,
            'summary' => $summary,
            'rooms' => $results,
        ];
    }

    private function classifyRoom(array $room, array $conflicts, int $attendeesCount): array
    {
        if (($room['status'] ?? 'available') === 'maintenance') {
            return $this->result('maintenance', false, 'Perawatan', 'Ruangan sedang dalam perawatan.', 'unavailable');
        }

        if ($attendeesCount > (int) $room['capacity']) {
            return $this->result(
                'insufficient_capacity',
                false,
                'Kapasitas kurang',
                'Kapasitas maksimal ' . (int) $room['capacity'] . ' orang.',
                'unavailable'
            );
        }

        $confirmed = array_values(array_filter($conflicts, static function (array $conflict): bool {
            return $conflict['status'] === 'confirmed';
        }));

        if ($confirmed) {
            return $this->result(
                'confirmed_conflict',
                false,
                'Sudah terpakai',
                'Jadwal terkonfirmasi ' . $this->formatRanges($confirmed) . '.',
                'confirmed_conflict',
                count($confirmed)
            );
        }

        $pending = array_values(array_filter($conflicts, static function (array $conflict): bool {
            return $conflict['status'] === 'pending';
        }));

        if ($pending) {
            return $this->result(
                'pending_conflict',
                true,
                'Ada pengajuan lain',
                count($pending) . ' pengajuan bersaing; prioritas akan dianalisis dengan SAW.',
                'pending_conflict',
                count($pending)
            );
        }

        return $this->result('available', true, 'Tersedia', 'Tidak ada jadwal yang beririsan.', 'available');
    }

    private function result(
        string $status,
        bool $selectable,
        string $label,
        string $message,
        string $summaryGroup,
        int $conflictCount = 0
    ): array {
        return [
            'availability_status' => $status,
            'selectable' => $selectable,
            'label' => $label,
            'message' => $message,
            'conflict_count' => $conflictCount,
            'summary_group' => $summaryGroup,
        ];
    }

    private function formatRanges(array $conflicts): string
    {
        $ranges = array_map(static function (array $conflict): string {
            return substr((string) $conflict['start_time'], 0, 5)
                . '–'
                . substr((string) $conflict['end_time'], 0, 5);
        }, $conflicts);

        $ranges = array_values(array_unique($ranges));
        if (count($ranges) <= 3) {
            return implode(', ', $ranges);
        }

        return implode(', ', array_slice($ranges, 0, 3)) . ' (+' . (count($ranges) - 3) . ' jadwal)';
    }
}

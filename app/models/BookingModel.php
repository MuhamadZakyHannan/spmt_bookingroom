<?php

require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Facade kompatibilitas untuk domain booking.
 *
 * Controller lama tetap menggunakan API ini, sedangkan setiap tanggung jawab
 * dikerjakan oleh service khusus dengan koneksi PDO yang sama.
 */
class BookingModel extends BaseModel
{
    private ?BookingScheduleService $scheduleService = null;
    private ?BookingQueryService $queryService = null;
    private ?BookingCommandService $commandService = null;
    private ?BookingHistoryService $historyService = null;
    private ?BookingConflictService $conflictService = null;
    private ?BookingStatisticsService $statisticsService = null;

    /** Menyiapkan dependensi yang dibutuhkan oleh BookingModel. */
    public function __construct(?PDO $connection = null)
    {
        parent::__construct($connection);
        if ($this->db) (new BookingLifecycleService($this->db))->expirePendingBookings();
    }

    /** Mengambil data today active bookings count. */
    public function getTodayActiveBookingsCount()
    {
        return $this->db ? $this->queries()->getTodayActiveCount() : 0;
    }

    /** Mengambil data today bookings. */
    public function getTodayBookings()
    {
        return $this->db ? $this->queries()->getToday() : [];
    }

    /** Memvalidasi conflict. */
    public function checkConflict($roomId, $date, $startTime, $endTime, $excludeId = 0)
    {
        if (!$this->db) return false;
        return $this->schedules()->checkConflict(
            (int) $roomId,
            (string) $date,
            (string) $startTime,
            (string) $endTime,
            (int) $excludeId
        );
    }

    /** Menambahkan data with schedule policy. */
    public function createWithSchedulePolicy(array $data, bool $isAdmin): array
    {
        return $this->db
            ? $this->schedules()->createWithPolicy($data, $isAdmin)
            : ['success' => false, 'reason' => 'database_unavailable'];
    }

    /** Memperbarui with schedule policy. */
    public function updateWithSchedulePolicy(
        int $bookingId,
        array $data,
        int $actorUserId,
        bool $isAdmin
    ): array {
        return $this->db
            ? $this->schedules()->updateWithPolicy($bookingId, $data, $actorUserId, $isAdmin)
            : ['success' => false, 'reason' => 'database_unavailable'];
    }

    /** Membuat data booking baru. */
    public function create($data)
    {
        return $this->db ? $this->schedules()->create((array) $data) : false;
    }

    /** Mengambil data last insert id. */
    public function getLastInsertId()
    {
        return $this->scheduleService?->getLastInsertId() ?? 0;
    }

    /** Mengambil data division monthly usage count. */
    public function getDivisionMonthlyUsageCount($dept, $date)
    {
        return $this->db
            ? $this->queries()->getDivisionMonthlyUsageCount((string) $dept, (string) $date)
            : 0;
    }

    /** Mengambil data conflicting groups. */
    public function getConflictingGroups()
    {
        return $this->db ? $this->conflicts()->getGroups() : [];
    }

    /** Menentukan conflict. */
    public function resolveConflict($winnerId, array $loserIds)
    {
        return $this->db ? $this->conflicts()->resolve((int) $winnerId, $loserIds) : false;
    }

    /** Mengambil data by user id. */
    public function getByUserId($userId)
    {
        return $this->db ? $this->queries()->getByUserId((int) $userId) : [];
    }

    /** Mengambil data by id. */
    public function getById(int $bookingId)
    {
        return $this->db ? $this->queries()->getById($bookingId) : false;
    }

    /** Membatalkan booking sesuai identitas pengguna dan kewenangan Administrator. */
    public function cancel($bookingId, $userId, $isAdmin = false)
    {
        return $this->db
            ? $this->commands()->cancel((int) $bookingId, (int) $userId, (bool) $isAdmin)
            : false;
    }

    /** Mengambil data all bookings. */
    public function getAllBookings($search = '', $status = '')
    {
        return $this->db
            ? $this->queries()->getAll((string) $search, (string) $status)
            : [];
    }

    /** Memperbarui status. */
    public function updateStatus($bookingId, $status)
    {
        return $this->db
            ? $this->commands()->updateStatus((int) $bookingId, (string) $status)
            : false;
    }

    /** Menghapus data booking beserta relasi terkait. */
    public function delete($bookingId)
    {
        return $this->db ? $this->commands()->delete((int) $bookingId) : false;
    }

    /** Mengambil data calendar events. */
    public function getCalendarEvents($roomId = 0)
    {
        return $this->db ? $this->queries()->getCalendarEvents((int) $roomId) : [];
    }

    /** Mengambil data booking history. */
    public function getBookingHistory($filters = [])
    {
        return $this->db ? $this->history()->getHistory((array) $filters) : [];
    }

    /** Mengambil data booking history summary. */
    public function getBookingHistorySummary($filters = [])
    {
        if (!$this->db) {
            return [
                'total_bookings' => 0,
                'confirmed_count' => 0,
                'pending_count' => 0,
                'total_attendees' => 0,
                'total_hours' => 0,
                'bookings' => [],
            ];
        }
        return $this->history()->getSummary((array) $filters);
    }

    /** Mengambil data statistics data. */
    public function getStatisticsData($filters = [])
    {
        return $this->db
            ? $this->statistics()->getData((array) $filters)
            : BookingStatisticsService::emptyResult();
    }

    /** Menjalankan proses schedules pada booking. */
    private function schedules(): BookingScheduleService
    {
        return $this->scheduleService ??= new BookingScheduleService($this->db);
    }

    /** Menjalankan proses queries pada booking. */
    private function queries(): BookingQueryService
    {
        return $this->queryService ??= new BookingQueryService($this->db);
    }

    /** Menjalankan proses commands pada booking. */
    private function commands(): BookingCommandService
    {
        return $this->commandService ??= new BookingCommandService($this->db);
    }

    /** Meneruskan permintaan ke riwayat booking. */
    private function history(): BookingHistoryService
    {
        return $this->historyService ??= new BookingHistoryService($this->db);
    }

    /** Menjalankan proses conflicts pada booking. */
    private function conflicts(): BookingConflictService
    {
        return $this->conflictService ??= new BookingConflictService($this->db);
    }

    /** Meneruskan permintaan ke statistik penggunaan ruangan. */
    private function statistics(): BookingStatisticsService
    {
        return $this->statisticsService ??= new BookingStatisticsService($this->db);
    }
}

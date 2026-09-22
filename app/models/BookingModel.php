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

    public function __construct(?PDO $connection = null)
    {
        parent::__construct($connection);
        if ($this->db) (new BookingLifecycleService($this->db))->expirePendingBookings();
    }

    public function getTodayActiveBookingsCount()
    {
        return $this->db ? $this->queries()->getTodayActiveCount() : 0;
    }

    public function getTodayBookings()
    {
        return $this->db ? $this->queries()->getToday() : [];
    }

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

    public function createWithSchedulePolicy(array $data, bool $isAdmin): array
    {
        return $this->db
            ? $this->schedules()->createWithPolicy($data, $isAdmin)
            : ['success' => false, 'reason' => 'database_unavailable'];
    }

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

    public function create($data)
    {
        return $this->db ? $this->schedules()->create((array) $data) : false;
    }

    public function getLastInsertId()
    {
        return $this->scheduleService?->getLastInsertId() ?? 0;
    }

    public function getDivisionMonthlyUsageCount($dept, $date)
    {
        return $this->db
            ? $this->queries()->getDivisionMonthlyUsageCount((string) $dept, (string) $date)
            : 0;
    }

    public function getConflictingGroups()
    {
        return $this->db ? $this->conflicts()->getGroups() : [];
    }

    public function resolveConflict($winnerId, array $loserIds)
    {
        return $this->db ? $this->conflicts()->resolve((int) $winnerId, $loserIds) : false;
    }

    public function getByUserId($userId)
    {
        return $this->db ? $this->queries()->getByUserId((int) $userId) : [];
    }

    public function getById(int $bookingId)
    {
        return $this->db ? $this->queries()->getById($bookingId) : false;
    }

    public function cancel($bookingId, $userId, $isAdmin = false)
    {
        return $this->db
            ? $this->commands()->cancel((int) $bookingId, (int) $userId, (bool) $isAdmin)
            : false;
    }

    public function getAllBookings($search = '', $status = '')
    {
        return $this->db
            ? $this->queries()->getAll((string) $search, (string) $status)
            : [];
    }

    public function updateStatus($bookingId, $status)
    {
        return $this->db
            ? $this->commands()->updateStatus((int) $bookingId, (string) $status)
            : false;
    }

    public function delete($bookingId)
    {
        return $this->db ? $this->commands()->delete((int) $bookingId) : false;
    }

    public function getCalendarEvents($roomId = 0)
    {
        return $this->db ? $this->queries()->getCalendarEvents((int) $roomId) : [];
    }

    public function getBookingHistory($filters = [])
    {
        return $this->db ? $this->history()->getHistory((array) $filters) : [];
    }

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

    public function getStatisticsData($filters = [])
    {
        return $this->db
            ? $this->statistics()->getData((array) $filters)
            : BookingStatisticsService::emptyResult();
    }

    private function schedules(): BookingScheduleService
    {
        return $this->scheduleService ??= new BookingScheduleService($this->db);
    }

    private function queries(): BookingQueryService
    {
        return $this->queryService ??= new BookingQueryService($this->db);
    }

    private function commands(): BookingCommandService
    {
        return $this->commandService ??= new BookingCommandService($this->db);
    }

    private function history(): BookingHistoryService
    {
        return $this->historyService ??= new BookingHistoryService($this->db);
    }

    private function conflicts(): BookingConflictService
    {
        return $this->conflictService ??= new BookingConflictService($this->db);
    }

    private function statistics(): BookingStatisticsService
    {
        return $this->statisticsService ??= new BookingStatisticsService($this->db);
    }
}

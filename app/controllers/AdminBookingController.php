<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Mengelola persetujuan, penghapusan, dan analisis konflik booking.
 */
final class AdminBookingController extends Controller
{
    private BookingModel $bookings;

    public function __construct()
    {
        $this->bookings = $this->model('BookingModel');
    }

    public function index(): void
    {
        $this->requireAdmin();
        if ($this->isPost()) $this->handleAction();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        [$analyses, $bookingIds] = $this->analyzeConflicts();

        $this->view('admin/bookings', [
            'bookings' => $this->bookings->getAllBookings($search, $status),
            'search' => $search,
            'status_filter' => $status,
            'conflict_analyses' => $analyses,
            'conflict_booking_ids' => $bookingIds,
        ]);
    }

    private function handleAction(): void
    {
        $this->validateCsrf('admin_bookings.php');
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'apply_saw_decision') {
            $winnerId = (int) ($_POST['winner_id'] ?? 0);
            $loserIds = array_filter(array_map(
                'intval',
                explode(',', (string) ($_POST['loser_ids'] ?? ''))
            ));
            if ($winnerId > 0) {
                $success = $this->bookings->resolveConflict($winnerId, $loserIds);
                set_flash(
                    $success ? 'success' : 'danger',
                    $success
                        ? 'Rekomendasi keputusan berhasil diterapkan. Jadwal terpilih disetujui dan jadwal bentrok lainnya dibatalkan.'
                        : 'Gagal menerapkan keputusan jadwal.'
                );
            }
            $this->redirect('admin_bookings.php');
        }

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        if ($bookingId > 0 && $action === 'update_status') {
            $this->bookings->updateStatus($bookingId, trim((string) ($_POST['status'] ?? 'confirmed')));
            set_flash('success', 'Status pemesanan berhasil diperbarui.');
        } elseif ($bookingId > 0 && $action === 'delete') {
            $this->bookings->delete($bookingId);
            set_flash('success', 'Data pemesanan berhasil dihapus.');
        }

        $this->redirect(($_POST['return_to'] ?? '') === 'dashboard.php'
            ? 'dashboard.php'
            : 'admin_bookings.php');
    }

    private function analyzeConflicts(): array
    {
        $analyses = [];
        $usage = fn($department, $date) =>
            $this->bookings->getDivisionMonthlyUsageCount($department, $date);

        foreach ($this->bookings->getConflictingGroups() as $group) {
            $analysis = SawService::analyzeConflictGroup($group['bookings'], $usage);
            if ($analysis) $analyses[] = array_merge($group, ['saw' => $analysis]);
        }

        $bookingIds = [];
        foreach ($analyses as $analysis) {
            foreach ($analysis['bookings'] as $booking) {
                $bookingIds[$booking['id']] = [
                    'group_id' => $analysis['group_id'],
                    'room_name' => $analysis['room_name'],
                ];
            }
        }
        return [$analyses, $bookingIds];
    }
}

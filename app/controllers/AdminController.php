<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Facade kompatibilitas untuk entry point administrator yang sudah ada.
 */
final class AdminController extends Controller
{
    /** Meneruskan permintaan ke pengelolaan ruangan. */
    public function rooms(): void
    {
        (new AdminRoomController())->index();
    }

    /** Meneruskan permintaan ke pengelolaan booking. */
    public function bookings(): void
    {
        (new AdminBookingController())->index();
    }

    /** Meneruskan permintaan ke riwayat booking. */
    public function history(): void
    {
        (new AdminHistoryController())->index();
    }

    /** Meneruskan permintaan ke pengelolaan pengguna. */
    public function users(): void
    {
        (new AdminUserController())->index();
    }

    /** Meneruskan permintaan ke pengelolaan monitor display. */
    public function displays(): void
    {
        (new AdminDisplayController())->index();
    }

    /** Meneruskan permintaan ke statistik penggunaan ruangan. */
    public function statistics(): void
    {
        (new AdminStatisticsController())->index();
    }
}

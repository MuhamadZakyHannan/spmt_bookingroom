<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Facade kompatibilitas untuk entry point administrator yang sudah ada.
 */
final class AdminController extends Controller
{
    public function rooms(): void
    {
        (new AdminRoomController())->index();
    }

    public function bookings(): void
    {
        (new AdminBookingController())->index();
    }

    public function history(): void
    {
        (new AdminHistoryController())->index();
    }

    public function users(): void
    {
        (new AdminUserController())->index();
    }

    public function displays(): void
    {
        (new AdminDisplayController())->index();
    }

    public function statistics(): void
    {
        (new AdminStatisticsController())->index();
    }
}

<?php
/**
 * Initialization Bootstrap File
 * MeetSpace - Sistem Reservasi Ruangan PT Pelabuhan Indonesia (Persero)
 */

// 1. Load Main Configuration & Database Connection
require_once __DIR__ . '/../config.php';

// 2. Load Core Classes
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/SawService.php';

// 3. Load Models
if (file_exists(__DIR__ . '/models/BookingModel.php')) {
    require_once __DIR__ . '/models/BookingModel.php';
}
if (file_exists(__DIR__ . '/models/RoomModel.php')) {
    require_once __DIR__ . '/models/RoomModel.php';
}
if (file_exists(__DIR__ . '/models/UserModel.php')) {
    require_once __DIR__ . '/models/UserModel.php';
}
if (file_exists(__DIR__ . '/models/DisplayModel.php')) {
    require_once __DIR__ . '/models/DisplayModel.php';
}

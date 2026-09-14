<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/BookingController.php';

$controller = new BookingController();
$controller->create();

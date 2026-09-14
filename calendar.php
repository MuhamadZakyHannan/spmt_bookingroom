<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/CalendarController.php';

$controller = new CalendarController();
$controller->index();

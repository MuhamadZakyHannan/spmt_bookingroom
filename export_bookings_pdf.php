<?php

require_once __DIR__ . '/config.php';

$controller = new BookingExportController();
$controller->pdf();

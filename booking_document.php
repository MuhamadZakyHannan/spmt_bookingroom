<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/BookingDocumentController.php';

$controller = new BookingDocumentController();
$controller->download();

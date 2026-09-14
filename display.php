<?php
/**
 * Room Display Signage Kiosk Entry Point
 * URL examples:
 * - display.php?token=DISP-ALPHA-01
 * - display.php?room=1
 * - display.php (loads default display)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/DisplayController.php';

$controller = new DisplayController();
$controller->show();

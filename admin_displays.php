<?php
/**
 * Admin Room Display Management Entry Point
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/AdminController.php';

$controller = new AdminController();
$controller->displays();

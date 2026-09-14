<?php
/**
 * API Endpoint for Room Display Live Status Polling
 * Returns JSON status, current meeting, next booking, and today's schedule.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/controllers/DisplayController.php';

$controller = new DisplayController();
$controller->apiStatus();

<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/DisplayController.php';

$controller = new DisplayController();
$controller->lobby();

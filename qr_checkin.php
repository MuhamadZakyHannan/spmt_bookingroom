<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/QrCheckinController.php';

$controller = new QrCheckinController();
$controller->handle();

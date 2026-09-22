<?php

require_once dirname(__DIR__) . '/app/core/Environment.php';

Environment::load(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/core/autoload.php';
require_once dirname(__DIR__) . '/app/core/helpers.php';

$pdo = ApplicationBootstrap::boot();

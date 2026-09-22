<?php

if (!defined('MEETSPACE_ROOT')) define('MEETSPACE_ROOT', dirname(__DIR__));
if (!defined('APP_ENV')) define('APP_ENV', Environment::get('APP_ENV', 'local_lan'));
if (!defined('APP_DEBUG')) define('APP_DEBUG', Environment::bool('APP_DEBUG', false));
if (!defined('APP_ALLOW_REGISTRATION')) define('APP_ALLOW_REGISTRATION', Environment::bool('APP_ALLOW_REGISTRATION', false));
if (!defined('SESSION_IDLE_TIMEOUT')) define('SESSION_IDLE_TIMEOUT', Environment::int('SESSION_IDLE_TIMEOUT', 7200, 900, 86400));
if (!defined('SESSION_COOKIE_PATH')) define('SESSION_COOKIE_PATH', Environment::get('SESSION_COOKIE_PATH', '/Room_Booking_System/'));
if (!defined('TRUST_PROXY_HEADERS')) define('TRUST_PROXY_HEADERS', Environment::bool('TRUST_PROXY_HEADERS', false));

if (!defined('APP_LOG_PATH')) {
    $defaultLogPath = dirname(MEETSPACE_ROOT, 2)
        . DIRECTORY_SEPARATOR . 'private'
        . DIRECTORY_SEPARATOR . 'Room_Booking_System'
        . DIRECTORY_SEPARATOR . 'logs'
        . DIRECTORY_SEPARATOR . 'application.log';
    define('APP_LOG_PATH', Environment::get('APP_LOG_PATH', $defaultLogPath));
}

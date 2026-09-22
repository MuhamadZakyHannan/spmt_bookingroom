<?php

if (!defined('DB_HOST')) define('DB_HOST', Environment::get('DB_HOST', 'localhost'));
if (!defined('DB_USER')) define('DB_USER', Environment::get('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', Environment::get('DB_PASS', ''));
if (!defined('DB_NAME')) define('DB_NAME', Environment::get('DB_NAME', 'meetspace_db'));

if (!defined('BOOKING_DOCUMENT_STORAGE')) {
    $defaultDocumentStorage = dirname(MEETSPACE_ROOT, 2)
        . DIRECTORY_SEPARATOR . 'private'
        . DIRECTORY_SEPARATOR . 'Room_Booking_System'
        . DIRECTORY_SEPARATOR . 'booking-documents';
    define('BOOKING_DOCUMENT_STORAGE', Environment::get('BOOKING_DOCUMENT_STORAGE', $defaultDocumentStorage));
}

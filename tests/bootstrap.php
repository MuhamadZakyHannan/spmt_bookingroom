<?php

// Test runner tidak boleh dieksekusi melalui Apache atau browser.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

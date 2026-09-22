<?php

/**
 * Mengirim response JSON konsisten dan menghentikan eksekusi endpoint.
 */
final class ApiResponse
{
    public static function send(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_APOS
                | JSON_HEX_AMP
                | JSON_HEX_QUOT
        );
        exit;
    }

    public static function error(string $message, int $status, string $code): never
    {
        self::send([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ], $status);
    }
}

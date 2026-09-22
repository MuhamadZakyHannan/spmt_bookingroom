<?php

/**
 * Guard bersama untuk method HTTP, autentikasi, role, dan CSRF endpoint API.
 */
final class ApiRequest
{
    /** Memvalidasi method. */
    public static function requireMethod(string ...$allowedMethods): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $allowedMethods = array_map('strtoupper', $allowedMethods);
        if (!in_array($method, $allowedMethods, true)) {
            header('Allow: ' . implode(', ', $allowedMethods));
            ApiResponse::error('Metode tidak diizinkan.', 405, 'method_not_allowed');
        }
        return $method;
    }

    /** Memvalidasi login. */
    public static function requireLogin(): void
    {
        if (!is_logged_in()) {
            ApiResponse::error('Sesi login telah berakhir.', 401, 'unauthenticated');
        }
    }

    /** Memvalidasi admin. */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!is_admin()) {
            ApiResponse::error('Akses Administrator diperlukan.', 403, 'forbidden');
        }
    }

    /** Memvalidasi csrf. */
    public static function requireCsrf(): void
    {
        if (!verify_csrf_token()) {
            ApiResponse::error('Token CSRF tidak valid.', 403, 'invalid_csrf_token');
        }
    }
}

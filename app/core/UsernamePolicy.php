<?php

class UsernamePolicy
{
    public const MIN_LENGTH = 3;
    public const MAX_LENGTH = 100;

    /** Memformat username. */
    public static function normalize(string $username): string
    {
        return strtolower(trim($username));
    }

    /** Menjalankan proses validation error pada username. */
    public static function validationError(string $username): ?string
    {
        $username = trim($username);
        $length = function_exists('mb_strlen') ? mb_strlen($username) : strlen($username);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return 'Username harus terdiri dari 3 sampai 100 karakter.';
        }
        if (!preg_match('/^[A-Za-z0-9._\-]+$/', $username)) {
            return 'Username hanya boleh menggunakan huruf, angka, titik, garis bawah, dan tanda hubung.';
        }
        return null;
    }
}

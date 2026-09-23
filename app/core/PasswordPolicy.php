<?php

final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    /** Menjalankan proses validation error pada password. */
    public static function validationError(string $password): ?string
    {
        $isValid = strlen($password) >= self::MIN_LENGTH
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password);

        return $isValid
            ? null
            : 'Password minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, serta angka.';
    }
}

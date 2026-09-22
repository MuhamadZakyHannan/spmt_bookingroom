<?php

final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public static function validationError(string $password): ?string
    {
        $isValid = strlen($password) >= self::MIN_LENGTH
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);

        return $isValid
            ? null
            : 'Password minimal 8 karakter dan wajib mengandung huruf besar, huruf kecil, angka, serta simbol unik.';
    }
}

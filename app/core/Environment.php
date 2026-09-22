<?php

/**
 * Memuat dan membaca konfigurasi environment tanpa menimpa nilai dari sistem.
 */
final class Environment
{
    /**
     * Memuat pasangan KEY=VALUE dari file konfigurasi lokal.
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) continue;
            if (getenv($key) !== false || array_key_exists($key, $_ENV)) continue;

            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    /**
     * Mengambil nilai string atau nilai default ketika variabel tidak tersedia.
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);
        return $value === false ? $default : (string) $value;
    }

    /**
     * Mengambil nilai boolean dengan dukungan true/false, yes/no, dan 1/0.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = getenv($key);
        if ($value === false || trim((string) $value) === '') return $default;
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Mengambil nilai integer dan membatasinya pada rentang yang diberikan.
     */
    public static function int(string $key, int $default, int $minimum, int $maximum): int
    {
        $value = filter_var(getenv($key), FILTER_VALIDATE_INT);
        if ($value === false) return $default;
        return max($minimum, min($maximum, (int) $value));
    }
}

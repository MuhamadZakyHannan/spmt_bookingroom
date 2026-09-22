<?php

/**
 * Memvalidasi dan menyimpan gambar ruangan ke direktori aset publik.
 */
final class RoomImageService
{
    private const MAX_SIZE_BYTES = 5 * 1024 * 1024;
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function store(?array $file, string $fallback): string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return $fallback;
        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE_BYTES) return $fallback;

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS, true)) return $fallback;
        if (!$this->hasAllowedMime($temporaryPath) || @getimagesize($temporaryPath) === false) return $fallback;

        $directory = dirname(__DIR__, 2) . '/public/rooms/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return $fallback;
        }

        $filename = 'room_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        return move_uploaded_file($temporaryPath, $directory . $filename)
            ? 'public/rooms/' . $filename
            : $fallback;
    }

    private function hasAllowedMime(string $path): bool
    {
        if (!function_exists('finfo_open')) return true;
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$fileInfo) return false;
        $mime = finfo_file($fileInfo, $path);
        finfo_close($fileInfo);
        return in_array($mime, self::MIME_TYPES, true);
    }
}

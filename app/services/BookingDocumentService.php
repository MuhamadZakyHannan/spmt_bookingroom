<?php

/**
 * Validasi dan penyimpanan dokumen booking di direktori privat.
 * Metadata database ditangani oleh BookingDocumentModel.
 */
class BookingDocumentService
{
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private $storagePath;

    /** Menyiapkan dependensi yang dibutuhkan oleh BookingDocumentService. */
    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = rtrim($storagePath ?: BOOKING_DOCUMENT_STORAGE, "\\/");
    }

    /** Memvalidasi data booking document sebelum diproses. */
    public function validate(?array $file): array
    {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if (!$file || $errorCode === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'provided' => false];
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['success' => false, 'provided' => true, 'error' => $this->uploadErrorMessage($errorCode)];
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_file($temporaryPath) || !is_readable($temporaryPath)) {
            return ['success' => false, 'provided' => true, 'error' => 'File dokumen tidak dapat dibaca.'];
        }

        $actualSize = filesize($temporaryPath);
        if ($actualSize === false || $actualSize < 1) {
            return ['success' => false, 'provided' => true, 'error' => 'File dokumen kosong atau tidak valid.'];
        }
        if ($actualSize > self::MAX_SIZE_BYTES) {
            return ['success' => false, 'provided' => true, 'error' => 'Ukuran dokumen maksimal 5 MB.'];
        }

        if (!function_exists('finfo_open')) {
            return ['success' => false, 'provided' => true, 'error' => 'Validasi tipe file tidak tersedia pada server.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $temporaryPath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        if (!$mimeType || !isset(self::MIME_EXTENSIONS[$mimeType])) {
            return ['success' => false, 'provided' => true, 'error' => 'Format dokumen harus PDF, JPG, atau PNG.'];
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = $mimeType === 'image/jpeg' ? ['jpg', 'jpeg'] : [self::MIME_EXTENSIONS[$mimeType]];
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'provided' => true, 'error' => 'Ekstensi file tidak sesuai dengan isi dokumen.'];
        }

        if (str_starts_with($mimeType, 'image/') && @getimagesize($temporaryPath) === false) {
            return ['success' => false, 'provided' => true, 'error' => 'File gambar rusak atau tidak valid.'];
        }
        if ($mimeType === 'application/pdf') {
            $handle = fopen($temporaryPath, 'rb');
            $signature = $handle ? fread($handle, 5) : '';
            if ($handle) fclose($handle);
            if ($signature !== '%PDF-') {
                return ['success' => false, 'provided' => true, 'error' => 'Struktur file PDF tidak valid.'];
            }
        }

        $originalName = basename((string) ($file['name'] ?? 'dokumen'));
        $originalName = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName));
        if ($originalName === '') {
            $originalName = 'dokumen.' . self::MIME_EXTENSIONS[$mimeType];
        }

        return [
            'success' => true,
            'provided' => true,
            'tmp_name' => $temporaryPath,
            'original_name' => function_exists('mb_substr')
                ? mb_substr($originalName, 0, 255)
                : substr($originalName, 0, 255),
            'mime_type' => $mimeType,
            'extension' => self::MIME_EXTENSIONS[$mimeType],
            'size_bytes' => (int) $actualSize,
            'sha256' => hash_file('sha256', $temporaryPath),
        ];
    }

    /** Menyimpan data booking document ke penyimpanan. */
    public function store(array $validatedFile): array
    {
        if (empty($validatedFile['provided']) || empty($validatedFile['success'])) {
            return ['success' => false, 'error' => 'Dokumen belum siap disimpan.'];
        }
        if (!is_uploaded_file($validatedFile['tmp_name'])) {
            return ['success' => false, 'error' => 'Sumber unggahan dokumen tidak valid.'];
        }
        if (!$this->ensureStorageDirectory()) {
            return ['success' => false, 'error' => 'Direktori penyimpanan dokumen tidak tersedia.'];
        }

        $storedName = bin2hex(random_bytes(24)) . '.' . $validatedFile['extension'];
        $destination = $this->storagePath . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($validatedFile['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Dokumen gagal disimpan ke penyimpanan privat.'];
        }
        @chmod($destination, 0600);

        return ['success' => true, 'stored_name' => $storedName, 'path' => $destination];
    }

    /** Menentukan path. */
    public function resolvePath(string $storedName): ?string
    {
        if (!preg_match('/^[a-f0-9]{48}\.(?:pdf|jpg|png)$/', $storedName)) {
            return null;
        }
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $storedName;
        return is_file($path) && is_readable($path) ? $path : null;
    }

    /** Menghapus atau mereset booking document. */
    public function remove(string $storedName): bool
    {
        if (!preg_match('/^[a-f0-9]{48}\.(?:pdf|jpg|png)$/', $storedName)) {
            return false;
        }
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $storedName;
        return !is_file($path) || @unlink($path);
    }

    /** Memvalidasi storage directory. */
    private function ensureStorageDirectory(): bool
    {
        if (is_dir($this->storagePath)) {
            return is_writable($this->storagePath);
        }
        return @mkdir($this->storagePath, 0700, true) && is_writable($this->storagePath);
    }

    /** Menjalankan proses upload error message pada booking document. */
    private function uploadErrorMessage(int $errorCode): string
    {
        if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'Ukuran dokumen melebihi batas server.';
        }
        if ($errorCode === UPLOAD_ERR_PARTIAL) {
            return 'Unggahan dokumen terputus. Silakan unggah kembali.';
        }
        return 'Dokumen gagal diunggah. Silakan coba kembali.';
    }
}

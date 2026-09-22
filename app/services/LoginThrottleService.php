<?php

require_once __DIR__ . '/../core/Database.php';

/**
 * Membatasi percobaan login gagal berdasarkan kombinasi username dan alamat IP.
 */
class LoginThrottleService
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_SECONDS = 900;

    private $db;

    /**
     * Membuat service dengan koneksi yang diberikan atau koneksi aplikasi.
     */
    public function __construct($connection = null)
    {
        $this->db = $connection ?: Database::getInstance()->getConnection();
    }

    /**
     * Memeriksa apakah kombinasi identitas sedang diblokir sementara.
     */
    public function status(string $username, string $ipAddress, ?DateTimeInterface $now = null): array
    {
        if (!$this->db) {
            return ['blocked' => false, 'retry_after' => 0, 'attempts' => 0];
        }

        $reference = $now ?: new DateTimeImmutable('now');
        $windowStart = $reference->sub(new DateInterval('PT' . self::WINDOW_SECONDS . 'S'));
        $statement = $this->db->prepare(
            'SELECT COUNT(*) AS attempts, MIN(attempted_at) AS first_attempt
             FROM login_attempts
             WHERE username_hash = ? AND ip_hash = ? AND attempted_at >= ?'
        );
        $statement->execute([
            $this->identityHash($username),
            $this->identityHash($ipAddress),
            $windowStart->format('Y-m-d H:i:s'),
        ]);
        $result = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $attempts = (int) ($result['attempts'] ?? 0);
        $retryAfter = 0;

        if ($attempts >= self::MAX_ATTEMPTS && !empty($result['first_attempt'])) {
            $firstAttempt = new DateTimeImmutable((string) $result['first_attempt']);
            $unlockAt = $firstAttempt->add(new DateInterval('PT' . self::WINDOW_SECONDS . 'S'));
            $retryAfter = max(1, $unlockAt->getTimestamp() - $reference->getTimestamp());
        }

        return [
            'blocked' => $attempts >= self::MAX_ATTEMPTS,
            'retry_after' => $retryAfter,
            'attempts' => $attempts,
        ];
    }

    /**
     * Mencatat satu kegagalan login tanpa menyimpan username atau IP mentah.
     */
    public function recordFailure(string $username, string $ipAddress, ?DateTimeInterface $now = null): void
    {
        if (!$this->db) return;

        $reference = $now ?: new DateTimeImmutable('now');
        $statement = $this->db->prepare(
            'INSERT INTO login_attempts (username_hash, ip_hash, attempted_at) VALUES (?, ?, ?)'
        );
        $statement->execute([
            $this->identityHash($username),
            $this->identityHash($ipAddress),
            $reference->format('Y-m-d H:i:s'),
        ]);

        $cleanupBefore = $reference->sub(new DateInterval('P1D'))->format('Y-m-d H:i:s');
        $cleanup = $this->db->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
        $cleanup->execute([$cleanupBefore]);
    }

    /**
     * Menghapus riwayat kegagalan setelah login berhasil.
     */
    public function clear(string $username, string $ipAddress): void
    {
        if (!$this->db) return;

        $statement = $this->db->prepare(
            'DELETE FROM login_attempts WHERE username_hash = ? AND ip_hash = ?'
        );
        $statement->execute([
            $this->identityHash($username),
            $this->identityHash($ipAddress),
        ]);
    }

    /**
     * Menghasilkan fingerprint satu arah untuk identitas throttle.
     */
    private function identityHash(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }
}

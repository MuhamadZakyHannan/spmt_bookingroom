-- Percobaan login gagal untuk rate limiting lokal tanpa menyimpan identitas mentah.

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempt_identity (username_hash, ip_hash, attempted_at),
    INDEX idx_login_attempt_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

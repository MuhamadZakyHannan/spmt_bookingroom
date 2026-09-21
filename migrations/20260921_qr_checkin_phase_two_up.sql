-- Tahap lanjutan: token QR check-in berumur pendek dan sekali pakai.

CREATE TABLE IF NOT EXISTS booking_checkin_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    display_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    used_by_user_id INT NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (display_id) REFERENCES room_displays(id) ON DELETE CASCADE,
    FOREIGN KEY (used_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_qr_display_expiry (display_id, expires_at, used_at),
    INDEX idx_qr_booking_expiry (booking_id, expires_at, used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

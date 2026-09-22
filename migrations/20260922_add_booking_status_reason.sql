-- Alasan status membedakan pembatalan manual, hasil konflik, dan kedaluwarsa.

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS status_reason VARCHAR(50) NULL AFTER status,
    ADD INDEX IF NOT EXISTS idx_bookings_status_reason (status, status_reason);

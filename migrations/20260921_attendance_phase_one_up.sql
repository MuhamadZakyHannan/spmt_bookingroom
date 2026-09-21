-- Tahap awal attendance tracking (MySQL 5.7+ / MariaDB 10.2+)
-- Jalankan sekali pada meetspace_db sebelum memakai fitur check-in/check-out.

ALTER TABLE bookings
    ADD COLUMN attendance_status ENUM('scheduled', 'checked_in', 'checked_out', 'no_show') NULL DEFAULT NULL AFTER status,
    ADD COLUMN check_in_at DATETIME NULL DEFAULT NULL AFTER attendance_status,
    ADD COLUMN check_out_at DATETIME NULL DEFAULT NULL AFTER check_in_at,
    ADD COLUMN no_show_at DATETIME NULL DEFAULT NULL AFTER check_out_at,
    ADD COLUMN attendance_updated_by ENUM('user', 'system') NULL DEFAULT NULL AFTER no_show_at,
    ADD INDEX idx_bookings_attendance_auto (status, attendance_status, date, start_time, end_time);

-- Booking lama yang sudah berlalu sengaja tidak diubah menjadi no-show.
UPDATE bookings
SET attendance_status = 'scheduled'
WHERE status = 'confirmed'
  AND date >= CURDATE()
  AND attendance_status IS NULL;

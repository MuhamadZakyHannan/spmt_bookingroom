-- Rollback data dan skema fitur attendance tahap awal.
-- Status booking yang berubah karena check-out/no-show dikembalikan ke confirmed.

DELETE FROM notifications
WHERE type IN ('attendance_check_in', 'attendance_check_out', 'attendance_no_show');

UPDATE bookings
SET status = 'confirmed'
WHERE attendance_status IN ('checked_out', 'no_show');

ALTER TABLE bookings
    DROP INDEX idx_bookings_attendance_auto,
    DROP COLUMN attendance_updated_by,
    DROP COLUMN no_show_at,
    DROP COLUMN check_out_at,
    DROP COLUMN check_in_at,
    DROP COLUMN attendance_status;

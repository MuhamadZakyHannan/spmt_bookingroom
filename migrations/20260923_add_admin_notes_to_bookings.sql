-- Menambahkan kolom admin_notes untuk menyimpan catatan/alasan dari admin saat pembatalan atau pengalihan ruangan/jadwal.

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL AFTER status_reason;

-- Menyimpan divisi pengguna agar form booking dapat terisi otomatis.
-- Jalankan satu kali setelah database.sql diimpor.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS department VARCHAR(100) NULL AFTER email,
    ADD INDEX IF NOT EXISTS idx_users_department (department);

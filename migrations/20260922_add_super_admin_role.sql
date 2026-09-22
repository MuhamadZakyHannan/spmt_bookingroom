-- Menambahkan role tertinggi untuk akun developer/pengelola sistem.
-- Jalankan satu kali setelah 20260922_add_department_to_users.sql.

ALTER TABLE users
    MODIFY COLUMN role ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user';

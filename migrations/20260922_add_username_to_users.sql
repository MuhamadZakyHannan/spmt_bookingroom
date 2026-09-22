-- Menambahkan identitas login berbasis username tanpa menghapus data email lama.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS username VARCHAR(100) NULL AFTER name;

UPDATE users
SET username = LOWER(
    CASE
        WHEN email LIKE '%@%' THEN SUBSTRING_INDEX(email, '@', 1)
        ELSE email
    END
)
WHERE username IS NULL OR username = '';

-- Jika ada local-part email yang sama, tambahkan id agar migrasi tetap deterministik.
UPDATE users u
JOIN (
    SELECT username
    FROM users
    GROUP BY username
    HAVING COUNT(*) > 1
) duplicates ON duplicates.username = u.username
SET u.username = CONCAT(u.username, '_', u.id);

ALTER TABLE users
    MODIFY username VARCHAR(100) NOT NULL,
    ADD UNIQUE INDEX IF NOT EXISTS uq_users_username (username);

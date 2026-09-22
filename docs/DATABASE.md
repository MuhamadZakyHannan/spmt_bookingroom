# Database

MeetSpace menggunakan MariaDB dari XAMPP. Semua request aplikasi memperoleh
koneksi dari `Database::getInstance()` sehingga konfigurasi PDO, error mode,
prepared statement, dan timezone konsisten.

## Instalasi baru

1. Buat database kosong bernama `meetspace_db` dengan charset `utf8mb4`.
2. Import `database/schema.sql` melalui phpMyAdmin atau terminal.
3. Jalankan seluruh migrasi dengan akun administrator database:

```powershell
$env:DB_USER = "root"
$env:DB_PASS = "password-administrator-database"
php scripts/apply_migrations.php
Remove-Item Env:DB_USER, Env:DB_PASS
```

4. Buat akun database khusus aplikasi dan beri hak `SELECT`, `INSERT`,
   `UPDATE`, serta `DELETE` pada `meetspace_db`.
5. Simpan kredensial akun aplikasi di `.env`, bukan di source code.

Skema final tidak memuat akun, password, booking, token display, atau data
operasional. Akun Super Admin pertama harus dibuat secara terkontrol setelah
database disiapkan.

### Menyiapkan Super Admin pertama

Untuk database yang benar-benar kosong, lakukan bootstrap hanya dari PC server:

1. Ubah sementara `APP_ALLOW_REGISTRATION="true"` pada `.env`.
2. Buka aplikasi melalui `http://localhost/Room_Booking_System/`, lalu daftarkan
   akun pengelola teknis dengan password yang kuat.
3. Ubah role akun tersebut melalui phpMyAdmin atau akun migrasi:

```sql
UPDATE users
SET role = 'super_admin'
WHERE username = 'username-pengelola';
```

4. Kembalikan `APP_ALLOW_REGISTRATION="false"` sebelum aplikasi dapat diakses
   dari jaringan LAN, lalu masuk ulang agar role session tersinkronisasi.

Langkah ini hanya diperlukan satu kali. Akun berikutnya dibuat oleh Super Admin
melalui menu **Kelola Pengguna**.

## Migrasi

Runner `scripts/apply_migrations.php` menjalankan file SQL berdasarkan urutan
nama. Runner menggunakan advisory lock MariaDB sehingga hanya satu proses yang
dapat berjalan pada satu waktu.

Setiap migrasi yang diterapkan dicatat bersama checksum SHA-256. File migrasi
lama tidak boleh diedit setelah diterapkan. Buat file migrasi baru untuk setiap
perubahan skema agar riwayat database tetap dapat diaudit.

## Koneksi dan timezone

- Native prepared statement selalu aktif.
- Error PDO dilempar sebagai exception dan dicatat ke log privat.
- Hasil query menggunakan associative array secara default.
- Koneksi memiliki timeout lima detik.
- Setiap session database menggunakan offset `+07:00` agar `NOW()`, `CURDATE()`,
  dan proses kedaluwarsa booking konsisten dengan WIB.

## Hak akses

Akun web tidak memerlukan `CREATE`, `ALTER`, `DROP`, `GRANT`, atau akses ke
database `mysql`. Hak tersebut hanya digunakan oleh administrator ketika
menjalankan migrasi atau pemulihan.

MariaDB pada PC server sebaiknya menggunakan `bind-address=127.0.0.1` karena
perangkat LAN hanya mengakses Apache, bukan port database. Jangan menjalankan
MariaDB dengan `skip-grant-tables` pada operasi normal.

## Backup dan pemulihan

Backup database harus disimpan bersama backup direktori dokumen privat. Jangan
menguji restore pada database aktif. Gunakan database sementara, verifikasi
jumlah tabel dan relasi, kemudian hapus database sementara setelah pemeriksaan.

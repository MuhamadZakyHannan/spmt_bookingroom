# Keamanan Server Jaringan Lokal

Aplikasi dijalankan melalui XAMPP pada satu PC yang aktif 24 jam dan hanya
ditujukan untuk perangkat dalam jaringan LAN/Wi-Fi internal.

## Perlindungan aplikasi

- Hanya entry point PHP di root dan aset dalam `public/` yang boleh dilayani.
- `app`, `docs`, `migrations`, `node_modules`, `scripts`, `src`, dan `tests`
  ditolak oleh Apache.
- Pendaftaran mandiri nonaktif secara default. Akun dibuat melalui menu
  **Kelola Pengguna** oleh Administrator.
- Lima kegagalan login dari kombinasi username dan alamat IP yang sama akan
  diblokir selama 15 menit.
- Username dan alamat IP pada rate limiter disimpan sebagai fingerprint
  SHA-256, bukan teks mentah.
- Session dibatasi pada path aplikasi dan berakhir setelah dua jam tidak aktif.
- Detail error tidak ditampilkan kepada pengguna dan ditulis ke log privat.
- Content Security Policy, frame protection, referrer policy, dan permissions
  policy dikirim pada response aplikasi.

## Konfigurasi `.env`

Salin `.env.example` ke `.env`, gunakan `APP_DEBUG="false"`, lalu sesuaikan
`SESSION_COOKIE_PATH` jika nama folder aplikasi berubah. Jangan menyimpan `.env`
ke Git atau membagikannya melalui folder bersama.

Gunakan akun database khusus aplikasi. Akun tersebut cukup diberi hak `SELECT`,
`INSERT`, `UPDATE`, dan `DELETE` terhadap `meetspace_db`. Migrasi database tetap
dijalankan dengan akun administrator secara manual. Kredensial sementara dapat
diberikan melalui environment terminal sehingga tidak disimpan dalam `.env`:

```powershell
$env:DB_USER = "root"
$env:DB_PASS = "password-administrator-database"
php scripts/apply_migrations.php
Remove-Item Env:DB_USER, Env:DB_PASS
```

## Firewall Windows

Izinkan Apache hanya pada profil jaringan **Private**. Jangan mengaktifkan rule
Apache untuk profil Public dan jangan membuat port forwarding pada router.
Perangkat pengguna cukup mengakses:

```text
http://IP-PC-SERVER/Room_Booking_System/
```

Gunakan DHCP reservation atau alamat IP statis agar URL tidak berubah.

## MySQL

Aplikasi web dan MySQL berjalan pada PC yang sama sehingga port 3306 tidak
perlu dibuka ke perangkat LAN. Konfigurasi XAMPP saat audit masih belum
mengaktifkan `bind-address=127.0.0.1`. Sebelum mengubah `my.ini`, pastikan tidak
ada aplikasi lain yang memerlukan akses MySQL jarak jauh, kemudian batasi MySQL
ke localhost dan restart layanan pada waktu pemeliharaan.

## Pemeriksaan setelah perubahan

Pastikan URL berikut menghasilkan HTTP 403 atau 404:

```text
/Room_Booking_System/tests/run_user_account_tests.php
/Room_Booking_System/scripts/apply_migrations.php
/Room_Booking_System/src/input.css
/Room_Booking_System/docs/FINALIZATION_BASELINE.md
```

Halaman login harus tetap menghasilkan HTTP 200 dan tidak menampilkan tautan
pendaftaran ketika `APP_ALLOW_REGISTRATION="false"`.

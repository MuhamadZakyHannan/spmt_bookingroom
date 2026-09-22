# MeetSpace — Sistem Pemesanan Ruang Rapat

MeetSpace adalah aplikasi pemesanan ruang rapat berbasis PHP dan MySQL untuk pengguna, administrator, monitor lobby, dan display pintu ruangan. Sistem menyediakan approval booking, deteksi jadwal bentrok, notifikasi admin, serta laporan pemakaian ruangan.

## Fitur utama

- Pemesanan ruang dan kalender jadwal.
- Pemeriksa ketersediaan ruangan secara langsung berdasarkan tanggal, waktu, dan jumlah peserta.
- Approval booking oleh administrator.
- Pengajuan yang belum disetujui otomatis kedaluwarsa saat waktu mulai tiba.
- Edit pengajuan dengan aturan akses berdasarkan pemilik, role, dan status booking.
- Lampiran surat pendukung PDF/JPG/PNG dengan penyimpanan privat dan akses terotorisasi.
- Analisis prioritas ketika jadwal bentrok menggunakan metode SAW.
- Status rapat pada monitor dihitung otomatis berdasarkan waktu jadwal.
- Notifikasi booking untuk administrator.
- Monitor lobby dan display pintu berbasis token.
- Pengelolaan ruangan, pengguna, role, dan monitor display.
- Riwayat, statistik, serta ekspor laporan CSV/PDF.
- Light mode dan dark mode.

## Persyaratan

- XAMPP dengan Apache, PHP 8.1 atau lebih baru, dan MySQL/MariaDB.
- Ekstensi PHP `pdo_mysql` aktif.
- Node.js dan npm hanya diperlukan jika ingin membangun ulang CSS.

## Instalasi

### 1. Letakkan aplikasi di XAMPP

Simpan proyek di:

```text
C:\xampp\htdocs\Room_Booking_System
```

### 2. Siapkan database

1. Jalankan Apache dan MySQL dari XAMPP Control Panel.
2. Buka `http://localhost/phpmyadmin/`.
3. Import skema utama `database/schema.sql` ke MySQL.
4. Terapkan migrasi database melalui terminal dari direktori proyek:

```powershell
php scripts/apply_migrations.php
```

Runner mengunci proses migrasi dan mencatat checksum setiap file sehingga aman
dipanggil kembali serta dapat mendeteksi migrasi lama yang berubah.
5. Setelah migrasi selesai, buat akun database khusus aplikasi melalui phpMyAdmin. Ganti password contoh sebelum menjalankan SQL:

```sql
CREATE USER IF NOT EXISTS 'meetspace_app'@'localhost' IDENTIFIED BY 'ganti-password-kuat';
GRANT SELECT, INSERT, UPDATE, DELETE ON meetspace_db.* TO 'meetspace_app'@'localhost';
FLUSH PRIVILEGES;
```

6. Pastikan database bernama `meetspace_db`, atau sesuaikan `DB_NAME` pada `.env`.

Skema final tidak berisi akun, password, booking, atau token. Direktori
`database/` diblokir oleh konfigurasi Apache sehingga skema tidak dapat diunduh
melalui aplikasi.

### 3. Buat konfigurasi lokal

Salin `.env.example` menjadi `.env`, kemudian sesuaikan nilainya:

```dotenv
APP_ENV="local_lan"
APP_DEBUG="false"
APP_ALLOW_REGISTRATION="false"
DB_HOST="localhost"
DB_USER="meetspace_app"
DB_PASS="ganti-password-kuat"
DB_NAME="meetspace_db"
BOOKING_DOCUMENT_STORAGE="C:/xampp/private/Room_Booking_System/booking-documents"
```

File `.env` tidak disimpan ke Git.

Panduan pembatasan Apache, MySQL, session, dan Windows Firewall tersedia di
[`docs/LOCAL_NETWORK_SECURITY.md`](docs/LOCAL_NETWORK_SECURITY.md). Struktur,
migrasi, serta hak akses database dijelaskan di
[`docs/DATABASE.md`](docs/DATABASE.md).
Rencana perapian kode jangka panjang dicatat di
[`docs/REFACTORING_ROADMAP.md`](docs/REFACTORING_ROADMAP.md).

### 4. Buka aplikasi

```text
http://localhost/Room_Booking_System/
```

Skema instalasi baru tidak menyediakan akun demo. Siapkan akun Super Admin
pertama secara terkontrol, kemudian buat akun operasional melalui menu
**Kelola Pengguna**.

## Panduan pengguna

### Membuat akun dan masuk

1. Hubungi Administrator untuk pembuatan akun. Pendaftaran mandiri dinonaktifkan secara default pada server LAN.
2. Administrator membuat akun melalui menu **Kelola Pengguna** menggunakan password awal minimal 8 karakter yang memuat huruf besar, huruf kecil, angka, dan simbol.
3. Masuk melalui halaman **Login**, kemudian ganti password awal melalui Administrator jika diperlukan.

Gunakan password yang panjang dan mengandung kombinasi huruf besar, huruf kecil, angka, serta simbol unik.

### Memesan ruangan

1. Masuk ke menu **Pesan Ruangan**.
2. Tentukan tanggal, waktu, dan jumlah peserta. Panel **Ketersediaan Ruangan** akan diperbarui otomatis.
3. Pilih kartu ruangan berdasarkan status berikut:
   - **Hijau — Tersedia:** tidak ada jadwal yang beririsan;
   - **Kuning — Sudah diajukan:** terdapat pengajuan lain yang masih menunggu persetujuan, tetapi ruangan tetap dapat dipilih;
   - **Merah — Sudah terkonfirmasi:** ada booking confirmed dan ruangan tidak dapat dipilih;
   - **Abu-abu — Tidak memenuhi:** ruangan sedang dirawat atau kapasitasnya tidak cukup.
4. Isi jenis kegiatan dan agenda, lalu kirim pengajuan.
5. Jika rapat memerlukan surat resmi, unggah pada bagian **Dokumen Pendukung**. Format yang didukung adalah PDF, JPG, dan PNG dengan ukuran maksimal 5 MB.
6. Booking dari user berstatus `pending` sampai disetujui admin. Booking yang dibuat admin dan tidak bentrok langsung terkonfirmasi.
7. Pantau status melalui **Booking Saya** atau **Kalender Jadwal**.

Pengajuan yang masih `pending` ketika waktu mulai tiba otomatis dipindahkan ke status **Kedaluwarsa**. Status ini dibedakan dari penolakan atau pembatalan manual dan tidak lagi dihitung sebagai konflik jadwal.

Pengajuan berstatus `pending` dapat diedit oleh pemiliknya melalui tombol **Edit Pengajuan** pada **Booking Saya**. Setelah disimpan, status tetap `pending` dan jadwal diperiksa ulang. Pemilik tidak dapat mengedit booking yang sudah `confirmed`; perubahan booking `pending` atau `confirmed` tersebut hanya dapat dilakukan Admin atau Super Admin.

Dokumen dapat dilihat oleh pemilik booking, Admin, dan Super Admin melalui tautan **Surat Pendukung**. Jika dokumen belum tersedia ketika booking dibuat, gunakan tombol **Tambah Surat Pendukung** pada menu **Booking Saya** selama status masih `pending` atau `confirmed`. Alur ini hanya mengunggah dokumen dan tidak mengubah jadwal maupun status booking. Tombol berubah menjadi **Ganti Surat Pendukung** setelah dokumen tersedia. File fisik disimpan di direktori `BOOKING_DOCUMENT_STORAGE` di luar `htdocs`; database hanya menyimpan metadata, checksum, dan nama file acak.

Jika beberapa pengajuan `pending` menginginkan ruangan dan waktu yang beririsan, Administrator akan meninjau dan menentukan prioritasnya. Jadwal yang sudah `confirmed` diblokir sejak form dan diperiksa ulang oleh server saat penyimpanan.

### Menggunakan kalender

Menu **Kalender Jadwal** menyediakan dua tampilan tanpa mode mingguan:

- **Bulan** untuk melihat jadwal dalam grid kalender;
- **Agenda** untuk melihat daftar jadwal pada bulan aktif.

Pada tampilan Agenda, nama hari dan tanggal ditampilkan dalam satu header lengkap, misalnya **Selasa, 22 September 2026**.

Gunakan tombol **Hari ini**, panah sebelumnya/berikutnya, pencarian agenda, dan filter ruangan untuk mempersempit jadwal. Klik sebuah agenda untuk membuka detail. Klik tanggal hari ini atau tanggal mendatang yang masih kosong untuk membuka form booking dengan tanggal tersebut terisi otomatis.

## Panduan administrator

### Mengelola booking

Dashboard admin tetap menggunakan tampilan katalog ruangan. Pada kolom kanan tersedia **Jadwal Hari Ini**, daftar ringkas **Persetujuan Peminjaman**, dan **Monitoring Display**. Admin dapat menyetujui atau menolak pengajuan tanpa meninggalkan dashboard; pengajuan yang bentrok diarahkan ke analisis SAW.

Gunakan menu **Kelola Semua Booking** untuk:

- menyetujui atau menolak pengajuan;
- mengedit booking berstatus `pending` atau `confirmed`;
- meninjau jadwal yang bentrok;
- melihat rekomendasi prioritas SAW;
- memantau status serta detail peminjaman.

Notifikasi baru dapat dibuka melalui ikon lonceng pada header admin.

### Menjalankan kedaluwarsa otomatis

Aplikasi menyelaraskan pengajuan kedaluwarsa setiap kali halaman booking, dashboard admin, atau pemeriksa ketersediaan dibuka. Agar proses tetap berjalan tanpa menunggu ada pengguna yang membuka aplikasi, jadwalkan skrip berikut melalui **Windows Task Scheduler** setiap satu menit:

- Program/script: `C:\xampp\php\php.exe`
- Add arguments: `C:\xampp\htdocs\Room_Booking_System\scripts\expire_pending_bookings.php`
- Start in: `C:\xampp\htdocs\Room_Booking_System`

Skrip aman dijalankan berulang kali. Hanya booking berstatus `pending` dengan waktu mulai yang sudah tiba yang diubah menjadi **Kedaluwarsa**; booking terkonfirmasi tidak terpengaruh.

### Mengelola ruangan

Gunakan menu **Kelola Ruangan** untuk menambah atau mengubah ruangan, kapasitas, lokasi, fasilitas, status, dan foto.

Foto dapat diunggah melalui halaman admin dalam format JPEG, PNG, atau WebP. Resolusi yang disarankan adalah 800×600 sampai 1920×1080. File upload disimpan di `public/rooms/`.

Foto bawaan yang tersedia:

| Ruangan | File |
| --- | --- |
| Kalibaru Timur | `public/rooms/KalTim.jpeg` |
| Kalibaru Barat | `public/rooms/KalBar.jpeg` |
| Samudera | `public/rooms/Samudra.jpeg` |
| Nusantara | `public/rooms/Nusantara.jpeg` |
| Pelabuhan Dalam | `public/rooms/Peldam.jpeg` |
| Pelra | `public/rooms/Pelra.jpeg` |

### Mengelola pengguna

Menu **Kelola Pengguna** digunakan untuk mencari dan melihat akun. Super Admin memperoleh tombol **Edit** untuk memperbarui nama, username, divisi, role, dan password opsional. Username harus unik; password baru minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol.

Role `super_admin` dikhususkan untuk pengelola teknis sistem. Super admin memiliki seluruh akses administrator dan menjadi satu-satunya role yang dapat mengubah role atau menghapus akun lain. Akun super admin tidak dapat diubah atau dihapus dari halaman pengelolaan pengguna.

### Mengelola monitor display

1. Buka **Kelola Monitor Display**.
2. Pilih ruangan dan isi nama monitor.
3. Biarkan token kosong agar dibuat otomatis, atau masukkan token khusus yang unik.
4. Buka URL yang ditampilkan pada perangkat di depan pintu ruangan.

Format URL display pintu:

```text
http://localhost/Room_Booking_System/display.php?token=DISPLAY_TOKEN
```

Monitor lobby dapat dibuka melalui:

```text
http://localhost/Room_Booking_System/display_lobby.php
```

Display memperbarui status ruangan secara berkala. Rapat otomatis berstatus **Berlangsung** berdasarkan waktu mulai dan selesai, tanpa check-in. Status berlangsung ditampilkan dengan warna hijau.

Kolom attendance lama pada database tidak perlu dihapus. Versi aplikasi ini mengabaikannya agar perubahan aman untuk database yang sudah berisi riwayat booking.

### Riwayat dan laporan

Gunakan menu **Riwayat Booking** untuk memfilter data berdasarkan tanggal, ruangan, status, atau kata kunci. Laporan dapat diekspor sebagai CSV atau tampilan PDF/print.

## Pengembangan

### Membangun ulang CSS

CSS hasil build sudah tersedia di `public/css/tailwind.min.css`. Jika kelas Tailwind diubah:

```powershell
npm install
npm run build:css
```

Untuk mode pemantauan selama pengembangan:

```powershell
npm run watch:css
```

### Menjalankan pengujian

Pastikan MySQL aktif dan database uji dapat diakses, lalu jalankan:

```powershell
php tests/run_schedule_status_tests.php
php tests/run_admin_dashboard_tests.php
php tests/run_room_availability_tests.php
php tests/run_booking_edit_tests.php
php tests/run_booking_document_tests.php
php tests/run_booking_document_http_tests.php
php tests/run_calendar_ui_tests.php
php tests/run_role_hierarchy_tests.php
php tests/run_user_account_tests.php
php tests/run_booking_expiration_tests.php
php tests/run_security_hardening_tests.php
php tests/run_database_configuration_tests.php
php tests/run_model_architecture_tests.php
php tests/run_booking_domain_refactor_tests.php
php tests/run_presentation_architecture_tests.php
php tests/run_api_architecture_tests.php
```

Pengujian membuat data sementara dan membersihkannya kembali setelah selesai.

## Pemecahan masalah

- **Database tidak tersambung:** periksa Apache/MySQL, isi `.env`, nama database, dan ekstensi `pdo_mysql`.
- **Perubahan warna tidak muncul:** jalankan `npm run build:css`, lalu lakukan hard refresh pada browser.
- **Display tidak menemukan ruangan:** periksa token melalui menu **Kelola Monitor Display** dan pastikan token pada URL sesuai.

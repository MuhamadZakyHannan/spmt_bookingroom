# MeetSpace — Sistem Pemesanan Ruang Rapat

MeetSpace adalah aplikasi pemesanan ruang rapat berbasis PHP dan MySQL untuk pengguna, administrator, monitor lobby, dan display pintu ruangan. Sistem menyediakan approval booking, deteksi jadwal bentrok, check-in/check-out berbasis akun, notifikasi admin, serta laporan pemakaian ruangan.

## Fitur utama

- Pemesanan ruang dan kalender jadwal.
- Approval booking oleh administrator.
- Analisis prioritas ketika jadwal bentrok menggunakan metode SAW.
- Check-in dan check-out oleh akun pemilik booking.
- Grace period, auto no-show, dan auto check-out.
- Notifikasi booking dan attendance untuk administrator.
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
3. Import skema utama `C:\xampp\private\Room_Booking_System\database.sql` ke MySQL.
4. Pastikan database bernama `meetspace_db`, atau sesuaikan `DB_NAME` pada `.env`.

Skema utama sengaja disimpan di luar `htdocs` agar tidak dapat diunduh melalui web server.

### 3. Buat konfigurasi lokal

Salin `.env.example` menjadi `.env`, kemudian sesuaikan nilainya:

```dotenv
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="meetspace_db"

ATTENDANCE_CHECK_IN_EARLY_MINUTES=15
ATTENDANCE_GRACE_MINUTES=15
```

File `.env` tidak disimpan ke Git.

### 4. Pasang migrasi attendance

Jalankan dari PowerShell pada direktori proyek:

```powershell
php scripts/run_attendance_migration.php up
```

Perintah ini aman dijalankan kembali; migrasi akan dilewati jika sudah terpasang.

### 5. Buka aplikasi

```text
http://localhost/Room_Booking_System/
```

Jika memakai data seed bawaan, akun demo yang tersedia adalah:

| Role | Email | Password |
| --- | --- | --- |
| User | `budi@company.com` | `password123` |
| Administrator | `sarah@company.com` | `password123` |

Ganti password demo sebelum aplikasi digunakan di lingkungan produksi.

## Panduan pengguna

### Membuat akun dan masuk

1. Pilih **Daftar** untuk membuat akun baru.
2. Isi nama, email, password minimal 6 karakter, dan konfirmasi password.
3. Masuk melalui halaman **Login**.

Gunakan password yang panjang dan mengandung kombinasi huruf besar, huruf kecil, angka, serta simbol unik.

### Memesan ruangan

1. Masuk ke menu **Pesan Ruangan**.
2. Pilih ruangan, tanggal, waktu, jenis kegiatan, jumlah peserta, dan isi agenda.
3. Kirim pengajuan.
4. Booking dari user berstatus `pending` sampai disetujui admin. Booking yang dibuat admin dan tidak bentrok langsung terkonfirmasi.
5. Pantau status melalui **Booking Saya** atau **Kalender Jadwal**.

Jika jadwal bertabrakan, pengajuan tetap dicatat sebagai pending agar administrator dapat menentukan prioritas.

### Menggunakan kalender

Menu **Kalender Jadwal** menyediakan dua tampilan tanpa mode mingguan:

- **Bulan** untuk melihat jadwal dalam grid kalender;
- **Agenda** untuk melihat daftar jadwal pada bulan aktif.

Gunakan tombol **Hari ini**, panah sebelumnya/berikutnya, pencarian agenda, dan filter ruangan untuk mempersempit jadwal. Klik sebuah agenda untuk membuka detail. Klik tanggal hari ini atau tanggal mendatang yang masih kosong untuk membuka form booking dengan tanggal tersebut terisi otomatis.

### Check-in dan check-out

Check-in dilakukan melalui menu **Booking Saya** menggunakan akun pemilik booking. Sistem tidak menggunakan QR Code.

- Tombol **Check-in** tersedia mulai 15 menit sebelum jadwal, atau mengikuti `ATTENDANCE_CHECK_IN_EARLY_MINUTES`.
- Batas check-in adalah 15 menit setelah jadwal mulai, atau mengikuti `ATTENDANCE_GRACE_MINUTES`.
- Booking yang melewati batas tanpa check-in otomatis menjadi `no_show`.
- Setelah check-in, tombol berubah menjadi **Check-out**.
- Jika pengguna belum check-out sampai waktu rapat selesai, sistem melakukan auto check-out.
- Booking yang sudah check-in tidak dapat dibatalkan sebelum check-out.

Warna tombol check-in/check-out otomatis mengikuti light mode dan dark mode.

## Panduan administrator

### Mengelola booking

Gunakan menu **Kelola Semua Booking** untuk:

- menyetujui atau menolak pengajuan;
- meninjau jadwal yang bentrok;
- melihat rekomendasi prioritas SAW;
- memantau status check-in, check-out, dan no-show.

Notifikasi baru dapat dibuka melalui ikon lonceng pada header admin.

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

Menu **Kelola Pengguna** digunakan untuk mencari pengguna, mengubah role `user`/`admin`, dan menghapus akun. Administrator tidak dapat menghapus akun yang sedang dipakai sendiri.

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

Display memperbarui status ruangan secara berkala dan membedakan kondisi terjadwal, menunggu check-in, berlangsung, serta tersedia.

### Riwayat dan laporan

Gunakan menu **Riwayat Booking** untuk memfilter data berdasarkan tanggal, ruangan, status, atau kata kunci. Laporan dapat diekspor sebagai CSV atau tampilan PDF/print.

## Menjalankan attendance otomatis

Halaman user, admin, dan display dapat memicu pemrosesan status otomatis. Agar transisi tetap tepat waktu meskipun halaman tidak sedang terbuka, jadwalkan perintah berikut setiap menit melalui Windows Task Scheduler:

```powershell
php C:\xampp\htdocs\Room_Booking_System\scripts\process_attendance.php
```

Gunakan direktori proyek sebagai **Start in** pada konfigurasi task.

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
php tests/run_attendance_tests.php
```

Pengujian membuat data sementara dan membersihkannya kembali setelah selesai.

### Struktur attendance

- `app/core/AttendancePolicy.php`: aturan jendela waktu dan ketersediaan aksi.
- `app/core/AttendancePresentation.php`: label, ikon, dan tema tombol.
- `app/services/AttendanceService.php`: transaksi check-in, check-out, no-show, dan auto check-out.
- `app/models/NotificationModel.php`: notifikasi admin.
- `app/models/BookingModel.php`: facade kompatibilitas untuk controller dan endpoint lama.
- `scripts/process_attendance.php`: pemrosesan otomatis melalui CLI.

## Rollback attendance

Cadangkan database terlebih dahulu. Untuk menghapus fitur attendance dari skema database:

```powershell
php scripts/run_attendance_migration.php down
```

Migrasi `down` menghapus notifikasi attendance, mengembalikan booking hasil auto check-out/no-show menjadi `confirmed`, lalu menghapus kolom attendance. SQL manual tersedia di `migrations/20260921_attendance_phase_one_down.sql`.

Untuk mengembalikan perubahan kode, gunakan riwayat Git sesuai commit yang ingin dibatalkan. Jangan menjalankan `git reset --hard` pada worktree yang memiliki perubahan lokal.

## Pemecahan masalah

- **Database tidak tersambung:** periksa Apache/MySQL, isi `.env`, nama database, dan ekstensi `pdo_mysql`.
- **Kolom attendance tidak ditemukan:** jalankan migrasi attendance `up`.
- **Status otomatis terlambat:** pastikan Windows Task Scheduler menjalankan `scripts/process_attendance.php` setiap menit.
- **Perubahan warna tidak muncul:** jalankan `npm run build:css`, lalu lakukan hard refresh pada browser.
- **Display tidak menemukan ruangan:** periksa token melalui menu **Kelola Monitor Display** dan pastikan token pada URL sesuai.

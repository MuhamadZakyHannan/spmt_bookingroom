# MeetSpace - Sistem Pemesanan Ruangan Rapat



## 🛠️ Petunjuk Instalasi di XAMPP

### Langkah 1: Copy Folder Aplikasi
1. Buka folder instalasi XAMPP Anda (biasanya di `C:\xampp\htdocs\` di Windows atau `/Applications/XAMPP/htdocs/` di macOS).
2. Salin seluruh isi folder ini ke dalam folder baru di htdocs, misalnya `C:\xampp\htdocs\meetspace\`.

### Langkah 2: Import Database MySQL
1. Buka XAMPP Control Panel dan jalankan service **Apache** dan **MySQL**.
2. Buka browser dan akses **phpMyAdmin** di `http://localhost/phpmyadmin/`.
3. Klik menu **Import** di bagian atas phpMyAdmin.
4. Pilih file **`C:\xampp\private\Room_Booking_System\database.sql`**. File skema sengaja disimpan di luar `htdocs` agar tidak dapat diunduh melalui Apache.
5. Klik **Go** / **Kirim** untuk mengimpor database. Script ini secara otomatis membuat database `meetspace_db` beserta tabel dan data awal.

### Langkah 3: Konfigurasi Database
File `config.php` & `app/core/Database.php` sudah disetel untuk standar XAMPP:
- **Host:** `localhost`
- **User:** `root`
- **Password:** `""` (kosong)
- **Database:** `meetspace_db`

### Langkah 4: Jalankan Aplikasi
Buka browser dan kunjungi:
`http://localhost/meetspace/`

---

## 🔑 Akun Demo Bawaan (2 Akun: 1 Admin & 1 User) - Password: `password123`

| Nama | Email | Role |
| :--- | :--- | :--- |
| **Budi Santoso** | `budi@company.com` | User |
| **Sarah Jenkins** | `sarah@company.com` | Administrator |

---

## 🚀 Ringkasan Sistem
1. **Tiga Jenis Akses, Dua Jenis Akun**: 
   - **User**: Login untuk reservasi ruangan, cek kalender, dan kelola booking sendiri.
   - **Admin**: Login untuk kelola ruangan, user, seluruh booking, dan konfigurasi display monitor.
   - **Room Display (Digital Signage Kiosk)**: **Tanpa Login**. Diakses via URL unik bertoken khusus (misal `/display.php?token=DISP-ALPHA-01`).
2. **Kiosk Digital Signage Pintu Ruangan**: Setiap ruangan dapat memiliki 1 monitor display yang ditempatkan di depan pintu. Layar ini menampilkan:
   - Jam digital dan tanggal real-time.
   - Status ruangan saat ini: 🟢 **TERSEDIA (AVAILABLE)** atau 🔴 **SEDANG DIGUNAKAN (OCCUPIED)** beserta detail meeting & progress bar sisa waktu rapat.
   - Kartu **Next Booking** (rapat berikutnya yang akan datang).
   - Timeline jadwal rapat lengkap hari ini.
   - **Auto-Refresh via AJAX**: Otomatis memperbarui status dan sisa waktu setiap 10 detik tanpa perlu refresh halaman manual.
3. **Tanpa Jabatan/Departemen**: Pengelolaan pengguna dibuat sederhana tanpa kerumitan pengisian jabatan/departemen.
4. **Model Layer (`app/models/`)**: Seluruh query SQL dan logika bisnis (seperti validasi bentrok jadwal/collision detection dan status live ruangan) terisolasi sepenuhnya dalam Class Model.
5. **View Layer (`app/views/`)**: Seluruh kode UI/HTML terpisah di dalam folder views, menerima data bersih dari Controller tanpa ada query SQL langsung.
6. **Controller Layer (`app/controllers/`)**: Menangani alur request pengkondisian, sanitasi input, pemanggilan model, dan rendering template view.
7. **Core Framework (`app/core/`)**: Memiliki Router Engine (`App.php`), Base Controller (`Controller.php`), dan Singleton Database Handler (`Database.php`).

---

## Uji Coba Tahap Awal: Check-in / Check-out

Fitur tahap awal aktif dengan aturan berikut:

- Tombol check-in hanya dapat digunakan oleh akun pemilik booking.
- Check-in dibuka 15 menit sebelum jadwal dan ditutup 15 menit setelah jadwal mulai.
- Booking yang belum check-in setelah grace period otomatis menjadi `no_show`.
- Booking yang sudah check-in otomatis check-out saat jam selesai.
- Display pintu/lobby membedakan `TERJADWAL`, `MENUNGGU CHECK-IN`, dan `BERLANGSUNG`.
- Check-in, check-out, dan no-show menghasilkan notifikasi untuk akun admin.

Migrasi database dapat dijalankan berulang dengan aman:

```powershell
php scripts/run_attendance_migration.php up
```

Untuk ketepatan transisi tanpa bergantung pada polling display/admin, jadwalkan perintah berikut setiap menit melalui Windows Task Scheduler:

```powershell
php C:\xampp\htdocs\Room_Booking_System\scripts\process_attendance.php
```

### Mengembalikan kondisi sebelum uji coba

Snapshot sebelum fitur berada pada commit `96ebda9` di branch `trial/tahap-awal-attendance`. Dari branch ini, rollback dilakukan dengan urutan:

```powershell
php scripts/run_attendance_migration.php down
git revert <commit-fitur-tahap-awal>
```

Migrasi `down` menghapus notifikasi attendance, mengembalikan booking hasil check-out/no-show ke `confirmed`, lalu menghapus kolom attendance. File SQL manual tersedia di `migrations/20260921_attendance_phase_one_down.sql`.

Pengujian regresi fitur:

```powershell
php tests/run_attendance_tests.php
```

## Uji Coba Tahap Lanjutan: QR Check-in

QR check-in tampil pada monitor pintu hanya jika display dibuka menggunakan token perangkat yang terdaftar, misalnya:

```text
http://localhost/Room_Booking_System/display.php?token=DISP-KBT-01
```

Alur keamanannya:

- QR hanya diterbitkan saat booking berada dalam jendela check-in tahap awal.
- Token acak terikat pada booking, ruangan, dan display yang menerbitkannya.
- Token berlaku 45 detik, disimpan sebagai hash, dan hanya dapat dipakai satu kali.
- Pemindai wajib login menggunakan akun pemilik booking.
- Setelah tahap QR aktif, check-in manual pada halaman Booking Saya dinonaktifkan; check-in wajib melalui QR monitor pintu.
- Token lain untuk booking yang sama dinonaktifkan setelah check-in berhasil.
- Display yang dibuka tanpa token perangkat tetap menampilkan jadwal, tetapi tidak memperoleh QR.
- Panel QR/status berada permanen di sisi kanan monitor. Saat booking sudah check-in, panel menampilkan konfirmasi dan QR tidak ditampilkan lagi.

Migrasi tahap QR:

```powershell
php scripts/run_qr_migration.php up
```

Untuk membatalkan tahap QR tanpa menghapus fitur attendance tahap awal, jalankan migrasi `down` sebelum me-revert commit tahap lanjutan:

```powershell
php scripts/run_qr_migration.php down
git revert <commit-fitur-qr>
```

Pengujian tahap QR:

```powershell
php tests/run_qr_checkin_tests.php
```

# MeetSpace — Sistem Manajemen Pemesanan Ruang Rapat
### PT Pelindo Multi Terminal (SPMT)

> **Buku Panduan Lengkap (System Guidebook & Technical Documentation)**  
> Versi: 2.4.0 (Enterprise Edition) · PHP 8.1+ · MySQL/MariaDB · Tailwind CSS · FullCalendar v6 · Status Uji: **29/29 Test Suite PASS (100%)**

---

## Daftar Isi (Table of Contents)

1. [Gambaran Sistem & Konsep Bisnis](#1-gambaran-sistem--konsep-bisnis)
   - [Tujuan & Latar Belakang](#tujuan--latar-belakang)
   - [Matriks Hak Akses & Wewenang (Role Matrix)](#matriks-hak-akses--wewenang-role-matrix)
   - [Siklus Hidup Status Pemesanan Terpusat](#siklus-hidup-status-pemesanan-terpusat)
2. [Spesifikasi & Kebutuhan Sistem](#2-spesifikasi--kebutuhan-sistem)
3. [Panduan Instalasi & Konfigurasi Cepat](#3-panduan-instalasi--konfigurasi-cepat)
   - [Penempatan Berkas Proyek](#langkah-1-penempatan-berkas-proyek)
   - [Persiapan Database & Skema](#langkah-2-persiapan-database--skema)
   - [Konfigurasi Berkas Lingkungan (.env)](#langkah-3-konfigurasi-berkas-lingkungan-env)
   - [Penyimpanan Berkas Privat Dokumen Pendukung](#langkah-4-penyimpanan-berkas-privat-dokumen-pendukung)
   - [Inisialisasi Akun Pertama](#langkah-5-inisialisasi-akun-pertama)
4. [Panduan Pengguna (User Guide)](#4-panduan-pengguna-user-guide)
   - [Masuk ke Sistem (Login)](#41-masuk-ke-sistem-login)
   - [Alur Pemesanan Ruangan (Booking Flow)](#42-alur-pemesanan-ruangan-booking-flow)
   - [Memahami Indikator Warna Ketersediaan Ruangan](#43-memahami-indikator-warna-ketersediaan-ruangan)
   - [Pengunggahan Dokumen / Surat Pendukung](#44-pengunggahan-dokumen--surat-pendukung)
   - [Mengelola & Memantau Pemesanan (Booking Saya)](#45-mengelola--memantau-pemesanan-booking-saya)
   - [Navigasi Kalender Jadwal Rapat](#46-navigasi-kalender-jadwal-rapat)
5. [Panduan Administrator (Admin Guide)](#5-panduan-administrator-admin-guide)
   - [Dashboard Administrator & Monitoring Cepat](#51-dashboard-administrator--monitoring-cepat)
   - [Persetujuan, Pembatalan, dan Relokasi Pemesanan](#52-persetujuan-pembatalan-dan-relokasi-pemesanan)
   - [Sistem Pendukung Keputusan (SPK) Metode SAW](#53-sistem-pendukung-keputusan-spk-metode-saw)
   - [Otomatisasi Kedaluwarsa Pemesanan (Task Scheduler)](#54-otomatisasi-kedaluwarsa-pemesanan-task-scheduler)
   - [Pengelolaan Ruangan & Fasilitas](#55-pengelolaan-ruangan--fasilitas)
   - [Pengelolaan Pengguna & Divisi SPMT (Super Admin)](#56-pengelolaan-pengguna--divisi-spmt-super-admin)
   - [Laporan Riwayat, Analisis Statistik & Ekspor Dokumen](#57-laporan-riwayat-analisis-statistik--ekspor-dokumen)
6. [Panduan Digital Signage & Kiosk Display](#6-panduan-digital-signage--kiosk-display)
   - [Display Pintu Ruangan (Door Display)](#61-display-pintu-ruangan-door-display)
   - [Monitor Lobby (Lobby Information Board)](#62-monitor-lobby-lobby-information-board)
7. [Panduan Arsitektur & Rekayasa Perangkat Lunak](#7-panduan-arsitektur--rekayasa-perangkat-lunak)
   - [Pola Arsitektur (Layered MVC & Services)](#71-pola-arsitektur-layered-mvc--services)
   - [Skalabilitas Data Besar (Pagination & Debounce)](#72-skalabilitas-data-besar-pagination--debounce)
   - [Pipeline CSS Bersama (Tailwind CSS)](#73-pipeline-css-bersama-tailwind-css)
   - [Standar PHPDoc & JSDoc](#74-standar-phpdoc--jsdoc)
8. [Panduan Pengujian Sistem (Quality Assurance)](#8-panduan-pengujian-sistem-quality-assurance)
9. [Pemecahan Masalah (Troubleshooting & FAQ)](#9-pemecahan-masalah-troubleshooting--faq)

---

## 1. Gambaran Sistem & Konsep Bisnis

### Tujuan & Latar Belakang
**MeetSpace** adalah aplikasi pengelolaan dan pemesanan ruang rapat korporat yang dirancang khusus untuk memenuhi kebutuhan operasional kantor **PT Pelindo Multi Terminal (SPMT)**. Aplikasi ini mengintegrasikan seluruh proses reservasi ruang kerja, mulai dari pengecekan ketersediaan waktu nyata (*real-time availability check*), pengajuan berbasis divisi, lampiran surat kedinasan, persetujuan bertingkat oleh Administrator, penyelesaian jadwal bentrok berbasis algoritma **Simple Additive Weighting (SAW)**, hingga tampilan papan digital (*digital signage kiosk*) di lobby dan depan pintu ruangan rapat.

```
       [ Pegawai / Divisi ]
                │ (Ajukan Booking + Lampiran)
                ▼
      [ Pengecekan Jadwal ] ──► (Bentrok?) ──► [ Rekomendasi SPK SAW ]
                │                                      │
                ├──────────────────────────────────────┘
                ▼
     [ Approval Administrator ] ──► (Disetujui / Dialihkan / Ditolak)
                │
                ├─────────────────────────────────────────────────┐
                ▼                                                 ▼
   [ Kalender & Notifikasi ]                         [ Digital Signage Kiosk ]
  - Booking Saya (Pengguna)                         - Display Pintu Ruang Rapat
  - Laporan & Statistik (Admin)                     - TV Monitor Lobby Utama
```

---

### Matriks Hak Akses & Wewenang (Role Matrix)

Sistem membagi pengguna ke dalam tiga tingkatan wewenang yang tegas:

| Modul / Fitur | User (Pegawai) | Admin | Super Admin | Keterangan |
| :--- | :---: | :---: | :---: | :--- |
| **Katalog & Jadwal Ruangan** | Dilindungi | Dilindungi | Dilindungi | Melihat status ruangan & kalender |
| **Pemesanan Ruang Rapat** | Dilindungi | Langsung Disetujui | Langsung Disetujui | Pengajuan User berstatus `pending` |
| **Edit Booking Milik Sendiri** | Hanya Pending | Bebas | Bebas | Pengguna hanya dapat mengubah booking `pending` |
| **Surat / Dokumen Pendukung** | Tambah / Ganti | Unduh / Tinjau | Unduh / Tinjau | Akses privat terenkripsi token |
| **Persetujuan (Approve/Reject)** | Tidak | Dilindungi | Dilindungi | Keputusan reservasi di admin panel |
| **Pembatalan Transparan** | Booking Sendiri | Dilindungi (Berita Alasan) | Dilindungi (Berita Alasan) | Alasan tersimpan di riwayat & badge |
| **Relokasi Ruangan Rapat** | Tidak | Dilindungi (Berita Alasan) | Dilindungi (Berita Alasan) | Memindahkan ruangan tanpa ubah waktu |
| **Resolusi Konflik (SPK SAW)** | Tidak | Dilindungi | Dilindungi | Memilih pemenang jadwal bentrok |
| **Kelola Master Ruangan** | Tidak | Dilindungi | Dilindungi | Tambah, ubah fasilitas, foto ruang |
| **Kelola Monitor Display** | Tidak | Dilindungi | Dilindungi | Token display pintu & lobby |
| **Laporan & Statistik Ekspor** | Tidak | Dilindungi (CSV/PDF) | Dilindungi (CSV/PDF) | Laporan resmi berstandar korporat |
| **Kelola Akun & Role User** | Tidak | Tidak | Dilindungi | Tambah/Edit/Hapus User & ganti role |
| **Proteksi Super Admin** | Tidak | Tidak | Terkunci Otomatis | Super Admin terakhir tidak bisa dihapus |

---

### Siklus Hidup Status Pemesanan Terpusat

Seluruh status reservasi di aplikasi diatur secara terpusat melalui helper [booking_status_label()](file:///c:/xampp/htdocs/Room_Booking_System/app/core/helpers.php) dan [booking_status_badge()](file:///c:/xampp/htdocs/Room_Booking_System/app/core/helpers.php):

```
                     ┌──────────────────┐
                     │     PENDING      │ ◄── Pengajuan baru dari Pegawai
                     └────────┬─────────┘
                              │
          ┌───────────────────┼───────────────────┐
          │ (Waktu tiba)      │ (Ditolak Admin)   │ (Disetujui Admin)
          ▼                   ▼                   ▼
    ┌───────────┐       ┌───────────┐       ┌───────────┐
    │  EXPIRED  │       │ CANCELLED │       │ CONFIRMED │
    │Kedaluwarsa│       │Dibatalkan │       │ Disetujui │
    └───────────┘       └───────────┘       └─────┬─────┘
                                                  │
                                                  │ (Waktu rapat berlangsung)
                                                  ▼
                                            ┌───────────┐
                                            │  ONGOING  │ (Tampil di Monitor Display)
                                            │Berlangsung│
                                            └─────┬─────┘
                                                  │
                                                  │ (Waktu rapat selesai)
                                                  ▼
                                            ┌───────────┐
                                            │ COMPLETED │
                                            │  Selesai  │
                                            └───────────┘
```

1. **Pending (Menunggu Persetujuan)**: Pengajuan baru menunggu evaluasi admin.
2. **Confirmed (Disetujui)**: Pengajuan telah disetujui. Ruangan resmi terkunci untuk jadwal tersebut.
3. **Confirmed (Dialihkan)**: Booking disetujui namun dipindahkan ke ruangan lain oleh Admin beserta alasan pengalihan.
4. **Cancelled (Dibatalkan)**: Dibatalkan secara mandiri oleh pemesan atau dibatalkan oleh Admin disertai catatan alasan pembatalan.
5. **Expired (Kedaluwarsa)**: Pengajuan yang belum disetujui saat jam mulai rapat tiba otomatis kedaluwarsa oleh sistem (tidak dianggap bentrok).
6. **Completed (Selesai)**: Rapat yang telah melewati jam selesai jadwalnya secara otomatis terdata sebagai rapat selesai dan masuk ke statistik riwayat resmi.

---

## 2. Spesifikasi & Kebutuhan Sistem

### Lingkungan Server (Minimum Requirements)
- **Web Server**: Apache 2.4+ (mendukung modul `mod_rewrite` dan file `.htaccess`)
- **PHP**: Versi **8.1 atau lebih baru**
  - Ekstensi PHP Wajib: `pdo`, `pdo_mysql`, `json`, `mbstring`, `fileinfo`, `gd` (untuk kompresi foto/denah)
- **Database Server**: MySQL 8.0+ atau MariaDB 10.4+ (Default port: `3306`)
- **Node.js & npm** (Opsional, hanya jika ingin mengompilasi ulang CSS Tailwind dari `src/input.css`)

---

## 3. Panduan Instalasi & Konfigurasi Cepat

### Langkah 1: Penempatan Berkas Proyek
Ekstrak atau clone repositori ini ke dalam direktori root web server lokal (XAMPP):
```text
C:\xampp\htdocs\Room_Booking_System
```

### Langkah 2: Persiapan Database & Skema
1. Jalankan modul **Apache** dan **MySQL** dari **XAMPP Control Panel**.
2. Akses phpMyAdmin di peramban: `http://localhost/phpmyadmin/`.
3. Buat database baru bernama `meetspace_db` dengan *collation* `utf8mb4_unicode_ci`.
4. Import skema utama dari berkas:
   ```text
   database/schema.sql
   ```
5. Jalankan runner migrasi otomatis dari terminal PowerShell di direktori proyek:
   ```powershell
   php scripts/apply_migrations.php
   ```
   *Runner ini memiliki perlindungan lock concurrency dan checksum otomatis untuk memastikan tabel terindeks dengan benar.*
6. Buat akun database berprivilese terbatas demi keamanan sistem:
   ```sql
   CREATE USER IF NOT EXISTS 'meetspace_app'@'localhost' IDENTIFIED BY 'PasswordKuat123#';
   GRANT SELECT, INSERT, UPDATE, DELETE ON meetspace_db.* TO 'meetspace_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Langkah 3: Konfigurasi Berkas Lingkungan (.env)
Salin berkas `.env.example` menjadi `.env` di root direktori proyek:
```powershell
Copy-Item .env.example .env
```
Buka berkas `.env` dan sesuaikan nilainya:
```dotenv
APP_ENV="local_lan"
APP_DEBUG="false"
APP_ALLOW_REGISTRATION="false"

# Konfigurasi Koneksi Database
DB_HOST="localhost"
DB_USER="meetspace_app"
DB_PASS="PasswordKuat123#"
DB_NAME="meetspace_db"

# Lokasi Penyimpanan Dokumen Pendukung (Wajib di luar web root demi keamanan)
BOOKING_DOCUMENT_STORAGE="C:/xampp/private/Room_Booking_System/booking-documents"
```

### Langkah 4: Penyimpanan Berkas Privat Dokumen Pendukung
Demi menjaga kerahasiaan surat kedinasan, berkas upload **tidak boleh** diletakkan di dalam folder `htdocs` publik. Buat folder penyimpanan privat:
```powershell
New-Item -ItemType Directory -Path "C:\xampp\private\Room_Booking_System\booking-documents" -Force
```
*Aplikasi menyajikan berkas ini melalui streaming binary terotentikasi ([booking_document.php](file:///c:/xampp/htdocs/Room_Booking_System/booking_document.php)) yang memeriksa izin pengguna sebelum menyajikan berkas.*

### Langkah 5: Inisialisasi Akun Pertama
Jalankan skrip inisialisasi akun Super Admin bawaan sistem:
```powershell
php scripts/init_super_admin.php
```
Akses sistem di browser:
```text
http://localhost/Room_Booking_System/
```

---

## 4. Panduan Pengguna (User Guide)

### 4.1 Masuk ke Sistem (Login)
1. Akses halaman utama atau klik menu **Masuk**.
2. Masukkan **Username** (format alfanumerik huruf kecil, strip, atau titik) dan **Password**.
3. Jika pendaftaran publik diizinkan oleh sistem, tombol registrasi akan aktif. Pada lingkungan korporat LAN, akun dibuat secara terpusat oleh Super Admin demi validasi divisi resmi.

### 4.2 Alur Pemesanan Ruangan (Booking Flow)
1. Buka menu **Pesan Ruangan** (`booking.php`).
2. Masukkan **Tanggal Rapat**, **Jam Mulai**, dan **Jam Selesai**.
   > [!IMPORTANT]
   > Sistem menerapkan validasi waktu nyata (*real-time time constraint*). Pengguna **tidak dapat** memesan jam yang sudah terlewat di hari yang sama (misal jam saat ini 18:00, namun memesan jam 15:00). Sistem akan memberikan peringatan interaktif untuk menyesuaikan jam.
3. Masukkan **Estimasi Peserta**.

### 4.3 Memahami Indikator Warna Ketersediaan Ruangan
Panel ketersediaan ruangan akan menganalisis jadwal dan menampilkan kartu ruangan:
- 🟢 **Hijau (Tersedia)**: Ruangan kosong dan memenuhi kapasitas. Siap untuk dipesan.
- 🟡 **Kuning (Sudah Diajukan / Bersaing)**: Terdapat divisi lain yang mengajukan pada waktu yang sama dan masih berstatus `pending`. Anda **tetap dapat mengajukan**, dan sistem SPK SAW akan membantu Admin menilai prioritas.
- 🔴 **Merah (Sudah Terkonfirmasi)**: Ruangan telah resmi disetujui untuk rapat lain. Ruangan terkunci dan tidak dapat dipilih.
- ⚪ **Abu-abu (Kapasitas Kurang / Perawatan)**: Ruangan tidak mencukupi jumlah peserta yang diisi atau sedang dalam status pemeliharaan (*maintenance*).

### 4.4 Pengunggahan Dokumen / Surat Pendukung
- Pada bagian formulir **Surat Pendukung**, pilih berkas lampiran (opsional namun disarankan untuk rapat penting / direksi).
- Format yang didukung: **PDF**, **JPG**, dan **PNG** (Maksimal **5 MB**).
- Berkas diverifikasi keasliannya (*MIME magic bytes*) untuk mencegah eksploitasi berkas berbahaya.

### 4.5 Mengelola & Memantau Pemesanan (Booking Saya)
Pada menu **Booking Saya** (`my_bookings.php`), pengguna dapat:
- **Melihat Status**: Kartu pemesanan menampilkan badge resmi terpusat.
- **Melihat Alasan Pembatalan / Pengalihan**: Jika rapat dibatalkan atau dipindahkan oleh Admin, kotak catatan alasan berwarna khusus akan tampil transparan pada kartu Anda.
- **Mengedit Pemesanan**: Tombol **Edit Pengajuan** aktif selama status masih `pending`. Pengguna dapat menyesuaikan agenda atau jam tanpa membuat pengajuan baru.
- **Tambah / Ganti Surat Pendukung**: Surat dinas yang menyusul dapat diunggah langsung dari kartu pemesanan tanpa mengulang jadwal.
- **Batalkan Booking**: Pembatalan mandiri sebelum acara dimulai.

### 4.6 Navigasi Kalender Jadwal Rapat
Buka menu **Kalender** (`calendar.php`):
- **Tampilan Bulan**: Grid kalender lengkap bulanan. Tanggal hari ini ditandai lingkaran biru.
- **Tampilan Agenda**: Daftar linier jadwal harian yang ramah layar sentuh ponsel (otomatis aktif di layar < 640px).
- **Pencarian Cepat (*Instant Live Search*)**: Kotak pencarian di pojok kanan atas memfilter agenda berdasarkan judul, ruangan, pemesan, dan catatan tujuan secara instan (0ms) dari memori browser berkat proteksi *debounce* dan integrasi FullCalendar v6 `refetchEvents()`.
- **Pemesanan Cepat via Kalender**: Mengklik tanggal masa depan yang kosong pada kalender akan langsung mengarahkan Anda ke formulir booking dengan tanggal yang telah terisi otomatis.

---

## 5. Panduan Administrator (Admin Guide)

### 5.1 Dashboard Administrator & Monitoring Cepat
Saat Administrator masuk, halaman utama menampilkan **Admin Command Center**:
- **Statistik Cepat**: Jumlah pemesanan menunggu persetujuan hari ini, rapat aktif berlangsung, total ruangan, dan pemanfaatan.
- **Jadwal Hari Ini**: Daftar timeline rapat per jam.
- **Persetujuan Cepat**: Menyetujui atau menolak pemesanan langsung dari kartu dasbor.

---

### 5.2 Persetujuan, Pembatalan, dan Relokasi Pemesanan
Pada menu **Kelola Pemesanan** (`admin_bookings.php`):
1. **Persetujuan (*Approve*)**: Mengubah status menjadi `confirmed` dan mengunci ruangan.
2. **Pembatalan oleh Admin (*Admin Cancel*)**:
   - Klik tombol **Batal**. Modal konfirmasi akan meminta **Alasan Pembatalan**.
   - Alasan ini wajib diisi dan akan tampil transparan pada riwayat pemesan serta laporan resmi PDF.
3. **Pengalihan Ruangan (*Relocate Room*)**:
   - Jika sebuah ruangan utama mendadak dibutuhkan untuk agenda darurat Direksi, Admin dapat memindahkan rapat yang sudah ada ke ruangan lain tanpa membatalkannya.
   - Klik tombol **Alihkan**, pilih ruangan pengganti yang kosong, dan masukkan catatan pengalihan.
   - Status otomatis berubah menjadi **Disetujui (Dialihkan)**.

---

### 5.3 Sistem Pendukung Keputusan (SPK) Metode SAW
Ketika dua atau lebih divisi mengajukan ruangan yang sama pada jam yang beririsan, sistem mendeteksinya sebagai **Kelompok Konflik** dan membukanya di tab **Analisis Konflik (SPK SAW)**.

Metode **Simple Additive Weighting (SAW)** menghitung matriks ternormalisasi dan nilai preferensi ($V_i$) secara otomatis:

$$V_i = \sum_{j=1}^{n} w_j \cdot r_{ij}$$

#### Tabel Bobot & Kriteria SAW:
| Kode | Nama Kriteria | Sifat | Bobot | Penjelasan Kriteria |
| :---: | :--- | :---: | :---: | :--- |
| **K1** | Tingkat Kepentingan Kegiatan | *Benefit* | **30%** | Skala 1–5: Direksi/Eksternal (5), Koordinasi Antar Divisi (4), Internal Divisi (3), Pelatihan (2), Rutin (1). |
| **K2** | Jumlah Peserta Rapat | *Benefit* | **20%** | Semakin banyak peserta rapat, semakin optimal pemanfaatan kapasitas ruang. |
| **K3** | Durasi Penggunaan | *Cost* | **15%** | Durasi yang lebih ringkas lebih disukai agar ruang dapat dipakai bergantian. |
| **K4** | Waktu Pengajuan (Kecepatan) | *Benefit* | **20%** | Pengajuan yang direncanakan jauh-jauh hari diberi prioritas dibanding mendadak. |
| **K5** | Frekuensi Pemakaian Divisi | *Cost* | **15%** | Divisi yang jarang menggunakan ruang pada bulan berjalan diberi kesempatan lebih. |

**Tindakan Resolusi Konflik**:
Admin cukup meninjau perangkingan nilai preferensi tertinggi (*Rekomendasi Utama*), lalu klik tombol **Pilih Pemenang**. Pemenang otomatis disetujui (`confirmed`), sedangkan pengajuan yang kalah otomatis dibatalkan disertai alasan keputusan SPK secara transparan.

---

### 5.4 Otomatisasi Kedaluwarsa Pemesanan (Task Scheduler)
Pengajuan `pending` yang tidak sempat disetujui hingga jam mulai rapat tiba tidak boleh menggantung di sistem. Sistem secara otomatis menyelaraskannya saat halaman dibuka.

Agar penyelarasan berjalan otomatis di latar belakang (*background*):
1. Buka **Windows Task Scheduler**.
2. Buat tugas baru bernama `MeetSpace_AutoExpire`.
3. Set pemicu: **Every 1 minute**.
4. Set aksi:
   - Program/script: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\Room_Booking_System\scripts\expire_pending_bookings.php`
   - Start in: `C:\xampp\htdocs\Room_Booking_System`

---

### 5.5 Pengelolaan Ruangan & Fasilitas
Menu **Kelola Ruangan** (`admin_rooms.php`) menyediakan kontrol master data:
- Menambah ruangan, kapasitas kursi, lokasi lantai/gedung, fasilitas pendukung (Proyektor, Video Conference, Mic Wireless, Sound System, Smart TV, AC).
- Mengunggah foto representatif ruangan (JPEG/PNG/WebP).
- Mengubah status ruangan ke **Tersedia** atau **Pemeliharaan (Maintenance)**.

Foto aset default ruangan yang tersedia di `public/rooms/`:
- `KalTim.jpeg` (Ruang Rapat Kalibaru Timur)
- `KalBar.jpeg` (Ruang Rapat Kalibaru Barat)
- `Samudra.jpeg` (Ruang Rapat Samudera)
- `Nusantara.jpeg` (Ruang Rapat Nusantara)
- `Peldam.jpeg` (Ruang Rapat Pelabuhan Dalam)
- `Pelra.jpeg` (Ruang Rapat Pelra)

---

### 5.6 Pengelolaan Pengguna & Divisi SPMT (Super Admin)
Khusus role **Super Admin**, menu **Kelola Pengguna** (`admin_users.php`) menyediakan pengelolaan akun terpusat:
- **Daftar Divisi Resmi SPMT**: Terstandarisasi via [Organization::DEPARTMENTS](file:///c:/xampp/htdocs/Room_Booking_System/app/core/Organization.php) (misal: *SPMT - Teknik & IT*, *SPMT - Operasional*, *SPMT - Rendal OPS*, dll.).
- **Kebijakan Sandi Kuat**: Password baru wajib minimal 8 karakter dengan kombinasi huruf kapital, huruf kecil, angka, dan simbol.
- **Proteksi Akun Terakhir**: Sistem secara cerdas mengunci penghapusan atau penurunan role jika akun tersebut adalah satu-satunya Super Admin yang tersisa di database.

---

### 5.7 Laporan Riwayat, Analisis Statistik & Ekspor Dokumen
Buka menu **Riwayat & Laporan** (`admin_history.php`):
- Filter multi-variabel: rentang tanggal, ruangan, status pemesanan, dan kata kunci nama/divisi.
- **Ekspor CSV**: Mengunduh data spreadsheet bersih yang telah diamankan dari serangan formula injection (`CSV Injection Sanitization`).
- **Ekspor PDF Resmi**: Menghasilkan dokumen cetak resmi berlogo **PT Pelindo Multi Terminal** dengan badge status terpusat (Disetujui, Selesai, Dibatalkan) dan rincian durasi serta pemohon.

---

## 6. Panduan Digital Signage & Kiosk Display

### 6.1 Display Pintu Ruangan (Door Display)
Dipasang pada tablet atau monitor kecil tepat di samping pintu masing-masing ruang rapat:
```text
http://localhost/Room_Booking_System/display.php?token=TOKEN_RUANGAN_ANDA
```
**Fitur Display Pintu**:
- Menampilkan nama ruangan dan kapasitas.
- **Status Otomatis**:
  - 🟢 **TERSEDIA**: Jam kosong, menampilkan hitung mundur menuju rapat berikutnya.
  - 🔴 **SEDANG DIGUNAKAN**: Ada rapat yang sedang berlangsung, menampilkan judul agenda, nama pemesan, dan sisa waktu rapat.
- **QR Code Pemesanan Instan**: Pemakai gedung yang berada di depan pintu dapat memindai QR Code untuk langsung membuka halaman reservasi ruangan tersebut.
- **Auto-Sync Latar Belakang**: Tampilan memperbarui data status secara otomatis via AJAX tanpa me-refresh halaman peramban.

---

### 6.2 Monitor Lobby (Lobby Information Board)
Dipasang pada Smart TV atau monitor besar di lobby utama gedung perkantoran:
```text
http://localhost/Room_Booking_System/display_lobby.php
```
- Menampilkan jadwal seluruh ruang rapat sepanjang hari ini dalam bentuk papan informasi bandara/stasiun (*flight information display style*).
- Auto-scroll otomatis jika agenda melebihi tinggi layar TV.
- Jam digital dan penanggalan resmi Indonesia waktu nyata.

---

## 7. Panduan Arsitektur & Rekayasa Perangkat Lunak

### 7.1 Pola Arsitektur (Layered MVC & Services)
MeetSpace menerapkan pemisahan tugas secara terstruktur (*Clean Architecture*):

```
Room_Booking_System/
├── api/                           # Endpoint API RESTful (Live Polling, Events, Availability)
├── app/
│   ├── controllers/               # Lapisan Controller (HTTP Request & View Mapping)
│   ├── core/                      # Fondasi Inti (Database, Auth, Helpers, Security Guard)
│   ├── models/                    # Model Facade Ringkas (< 250 baris per file)
│   ├── services/                  # Logika Bisnis Terpisah (Schedule, Conflict, Query, dsb.)
│   └── views/                     # Presentasi Tampilan & Komponen Parsial Modular
│       ├── admin/
│       │   └── partials/          # Komponen Parsial Modular (_booking_table, _modals, dll.)
│       ├── booking/
│       ├── calendar/
│       └── layouts/               # Header, Navbar & Footer Bersama
├── config/                        # Konfigurasi Aplikasi & Environment Loader
├── database/                      # Skema Database Tunggal (schema.sql)
├── migrations/                    # Berkas Migrasi Tambahan Terkendali
├── public/                        # Aset Web Publik (JS Terpisah, CSS Minified, Favicon, Gambar)
│   ├── css/
│   └── js/                        # admin-bookings.js, booking-form.js, ui-utils.js
├── scripts/                       # Skrip CLI Terjadwal (apply_migrations, expire_bookings)
└── tests/                         # Suite Pengujian Otomatis (29 Suite Lengkap)
```

---

### 7.2 Skalabilitas Data Besar (Pagination & Debounce)
Ketika volume pemesanan mencapai puluhan ribu baris di masa mendatang, sistem telah dilengkapi arsitektur proteksi:
1. **Server-Side AJAX Pagination**:
   - Endpoint `api/admin_bookings_live.php` menerima parameter `page` dan `limit`.
   - Menghitung total data langsung di level query database SQL (`SELECT COUNT(*)`).
   - Membatasi batas maksimum muatan per request (`$maxCeiling = 500`) untuk mencegah lonjakan memori (*Out-of-Memory*).
2. **Debounce Protection Frontend**:
   - Kotak pencarian di `public/js/admin-bookings.js` dibungkus utilitas `debounce(func, 180)` sehingga pengetikan beruntun tidak membanjiri request server.
3. **Pencarian Kalender In-Memory Instant**:
   - Kalender mengambil seluruh event bulan aktif satu kali ke `cachedCalendarEvents`, lalu menyaring secara instan (0ms) di memori lokal peramban menggunakan `calendarInstance.refetchEvents()`.

---

### 7.3 Pipeline CSS Bersama (Tailwind CSS)
- Seluruh aturan visual dikompilasi dari satu berkas sumber `src/input.css` menuju `public/css/tailwind.min.css`.
- **Aturan Tegas**: Berkas view dilarang memuat blok tag `<style>` mandiri demi konsistensi dan efisiensi caching peramban.
- Perintah kompilasi CSS:
  ```powershell
  npm.cmd run build:css
  ```

---

### 7.4 Standar PHPDoc & JSDoc
Sesuai audit pengujian [run_function_documentation_tests.php](file:///c:/xampp/htdocs/Room_Booking_System/tests/run_function_documentation_tests.php):
- **Setiap fungsi/metode PHP bernama** wajib memiliki blok komentar dokumentasi PHPDoc (`/** ... */`).
- **Setiap fungsi JavaScript bernama** wajib memiliki blok komentar JSDoc (`/** ... */`).

---

## 8. Panduan Pengujian Sistem (Quality Assurance)

Sistem memiliki **29 test suite otomatis** yang menguji seluruh fungsionalitas, keamanan, dan batas arsitektur secara ketat.

### Menjalankan Seluruh Pengujian Sekaligus
Buka terminal PowerShell di folder proyek dan jalankan perintah berikut:

```powershell
Get-ChildItem tests -Filter '*.php' | Where-Object { $_.Name -like 'run_*' -or $_.Name -like 'test_*' } | ForEach-Object { php $_.FullName; if ($LASTEXITCODE -ne 0) { throw "Gagal: $($_.Name)" } }; Write-Host "`n>>> SEMUA 29 TEST SUITE LULUS 100%! <<<" -ForegroundColor Green
```

### Rincian Modul Pengujian:
1. `run_admin_dashboard_tests.php`: Verifikasi dasbor admin, metrik, dan pemesanan hari ini.
2. `run_api_architecture_tests.php`: Verifikasi keamanan method HTTP, proteksi CSRF, dan guard API.
3. `run_booking_document_tests.php` & `_http_tests.php`: Verifikasi upload dokumen, MIME signature, dan keamanan unduh privat.
4. `run_booking_domain_refactor_tests.php`: Verifikasi pemisahan service layer dan isolasi domain.
5. `run_booking_edit_tests.php`: Verifikasi batasan pengeditan booking berdasarkan role dan status.
6. `run_booking_expiration_tests.php`: Verifikasi logika otomatisasi kedaluwarsa jadwal lampau.
7. `run_calendar_ui_tests.php`: Verifikasi format bahasa Indonesia dan tampilan agenda kalender.
8. `run_css_consolidation_tests.php`: Verifikasi eliminasi blok style mandiri dan pipeline CSS terpadu.
9. `run_database_configuration_tests.php`: Verifikasi konfigurasi database, timezone WIB, dan PDO native prepared statements.
10. `run_final_architecture_tests.php`: Verifikasi batas baris facade model (< 250 baris) dan controller (< 75 baris).
11. `run_function_documentation_tests.php`: Verifikasi 100% kepatuhan PHPDoc dan JSDoc pada seluruh fungsi bernama.
12. `run_model_architecture_tests.php`: Verifikasi dependency injection PDO pada seluruh model inti.
13. `run_presentation_architecture_tests.php`: Verifikasi pemisahan partials UI dan utilitas escaping XSS.
14. `run_report_export_tests.php`: Verifikasi sanitasi ekspor CSV dan rendering PDF resmi.
15. `run_responsive_ui_tests.php`: Verifikasi layout responsif seluler (viewport, sentuhan, modal adaptif).
16. `run_role_hierarchy_tests.php`: Verifikasi hirarki hak akses Super Admin, Admin, dan User biasa.
17. `run_room_availability_tests.php`: Verifikasi akurasi algoritma ketersediaan ruang (slot waktu).
18. `run_schedule_status_tests.php`: Verifikasi transisi status waktu jadwal rapat.
19. `run_security_hardening_tests.php`: Verifikasi proteksi session fixation, XSS, dan CSRF token.
20. `run_user_account_tests.php`: Verifikasi kebijakan password kuat dan validasi username unik.
21. `test_booking_cancel_and_relocate.php`: Pengujian alur pembatalan beralasan dan pengalihan ruangan.
22. `test_booking_lifecycle_completed.php`: Pengujian penutupan rapat otomatis berstatus selesai.
23. `test_centralized_status_integration.php`: Pengujian konsistensi badge dan status terpusat lintas halaman.
24. `test_delete_user_permissions.php`: Pengujian izin penghapusan user dan proteksi Super Admin terakhir.
25. `test_department_sync_and_flash.php`: Pengujian standarisasi divisi SPMT dan pesan flash session.
26. `test_past_booking_time_validation.php`: Pengujian pencegahan pemesanan jam lampau pada hari yang sama.
27. `test_server_pagination_and_calendar_search.php`: Pengujian limit/offset pagination, debounce, dan search kalender.
28. `test_user_name_username_sync.php`: Pengujian integritas sinkronisasi nama dan username pengguna.

---

## 9. Pemecahan Masalah (Troubleshooting & FAQ)

### Q: Mengapa muncul pesan "Database connection failed"?
- Pastikan MySQL aktif di XAMPP Control Panel.
- Buka berkas `.env` dan pastikan nama database, user, dan password sesuai dengan konfigurasi phpMyAdmin Anda.
- Pastikan ekstensi `extension=pdo_mysql` tidak dikomentari pada berkas `php.ini`.

### Q: Tombol silang (X) pada searchbar kalender tidak muncul atau berantakan?
- Pastikan Anda menggunakan versi terbaru dari repositori ini. Kolom pencarian kalender menggunakan `<input type="text">` dengan koordinat native Tailwind `top-2.5 right-3` yang terpusat secara presisi, bebas dari duplikasi tombol bawaan peramban Chromium.

### Q: Dokumen pendukung yang diunggah gagal dibuka / 404?
- Periksa path `BOOKING_DOCUMENT_STORAGE` pada berkas `.env`. Pastikan folder fisik di luar web root tersebut benar-benar ada dan memiliki izin baca/tulis (*read/write permissions*).

### Q: Bagaimana jika jadwal bentrok memiliki nilai SAW yang persis sama?
- Sistem akan mengurutkan alternatif berdasarkan waktu pengajuan paling awal (*First-Come, First-Served*) sebagai faktor penentu sekunder, dan Administrator tetap memegang kendali final untuk memilih alternatif terbaik.

---

**MeetSpace SPMT — Professional Room Booking System**  
*Dikembangkan dengan dedikasi untuk keandalan, keamanan, dan efisiensi operasional PT Pelindo Multi Terminal.*

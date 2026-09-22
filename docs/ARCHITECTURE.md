# Arsitektur MeetSpace

MeetSpace menggunakan arsitektur berlapis sederhana untuk PHP native. Struktur
ini sengaja tidak bergantung pada framework agar tetap mudah dijalankan melalui
XAMPP, tetapi batas tanggung jawabnya mengikuti pola aplikasi jangka panjang.

## Alur request

```text
Entry point PHP / endpoint API
        |
        v
Controller atau ApiRequest
        |
        v
Service domain
        |
        v
Model / query service
        |
        v
PDO tunggal -> MariaDB
```

Entry point pada root hanya melakukan bootstrap dan memanggil controller.
Direktori internal `app`, `database`, `docs`, `migrations`, `scripts`, `src`, dan
`tests` tidak dapat diakses melalui Apache.

## Lapisan aplikasi

### Core

- `Environment` membaca `.env` tanpa menimpa environment sistem.
- `Database` menyediakan satu koneksi PDO per proses PHP.
- `BaseModel` memberi constructor injection untuk seluruh model.
- `Controller` menyediakan rendering, redirect, autentikasi, dan CSRF web.
- `ApiRequest` dan `ApiResponse` menyeragamkan kontrak endpoint JSON.

### Controller

Controller mengatur request dan response, bukan menyimpan query atau algoritma.
`AdminController` adalah facade kompatibilitas; pekerjaan sebenarnya dibagi ke
controller ruangan, booking, pengguna, display, riwayat, dan statistik.

### Domain dan service

Domain booking dibagi berdasarkan tanggung jawab:

- `BookingScheduleService`: transaksi pembuatan dan edit jadwal.
- `BookingQueryService`: query dashboard, kalender, dan daftar booking.
- `BookingCommandService`: pembatalan, perubahan status, dan penghapusan.
- `BookingConflictService`: kelompok konflik serta keputusan administrator.
- `BookingHistoryService`: riwayat dan ringkasan laporan.
- `BookingStatisticsService`: KPI dan dataset grafik.
- `BookingLifecycleService`: kedaluwarsa otomatis booking pending.

`BookingModel` mempertahankan facade publik agar migrasi controller dapat
dilakukan bertahap tanpa merusak entry point lama.

### Presentasi

View hanya menerima data yang sudah disiapkan controller. Interaksi global
berada di `public/js/site-shell.js`, inisialisasi tema di `theme-init.js`, dan
escaping/highlight teks di `ui-utils.js`. Data dinamis JavaScript dikirim lewat
partial `_runtime_config.php`, bukan disisipkan ke source JavaScript global.

## Aturan pengembangan

- Tambahkan perubahan skema sebagai migration baru; jangan mengedit migration
  yang sudah diterapkan.
- Gunakan prepared statement untuk seluruh input dinamis.
- Service baru menerima dependency melalui constructor.
- Controller tidak boleh berisi SQL atau manipulasi file langsung.
- Endpoint API wajib memakai `ApiRequest` dan `ApiResponse`.
- Method publik lama hanya dipertahankan bila masih memiliki pemanggil.
- Setiap refactor wajib melewati syntax check dan seluruh test runner.

## Menambahkan fitur

1. Tentukan aturan bisnis pada service domain.
2. Tambahkan query pada service query/model yang relevan.
3. Hubungkan melalui controller atau endpoint API.
4. Render data melalui view dan modul JavaScript yang sesuai.
5. Tambahkan regression test dan perbarui dokumentasi bila kontrak berubah.

Konfigurasi database dijelaskan di [`DATABASE.md`](DATABASE.md), sedangkan
pengamanan server LAN dijelaskan di
[`LOCAL_NETWORK_SECURITY.md`](LOCAL_NETWORK_SECURITY.md).

# Roadmap Refactor Bertahap

Refactor dilakukan dalam commit kecil dan setiap tahap harus mempertahankan
perilaku aplikasi serta melewati seluruh regression test sebelum dilanjutkan.

## Tahap refactor

1. **Fondasi model** — satukan pengelolaan koneksi dan dukung constructor
   injection. Status: selesai.
2. **Domain booking** — pecah `BookingModel` menjadi komponen query, penulisan
   jadwal, penyelesaian konflik, dan laporan tanpa mengubah API controller.
   Status: selesai; `BookingModel` menjadi facade kompatibilitas.
3. **Controller admin** — pisahkan pengelolaan ruangan, booking, pengguna,
   display, dan statistik dari `AdminController`. Status: selesai;
   `AdminController` menjadi facade kompatibilitas.
4. **Presentasi** — pindahkan JavaScript besar dari view ke modul dalam
   `public/js` dan pecah partial UI yang digunakan ulang. Status: selesai;
   shell global, tema, notifikasi, dan utility escaping sudah dimodulkan.
5. **Endpoint API** — seragamkan validasi request, response JSON, status HTTP,
   autentikasi, dan penanganan error. Status: selesai melalui `ApiRequest` dan
   `ApiResponse`.
6. **Finalisasi** — hapus kompatibilitas yang tidak lagi digunakan, lengkapi
   dokumentasi arsitektur, lalu jalankan pemeriksaan regresi dan keamanan.
   Status: selesai.

## Aturan pengerjaan

- Tidak mencampurkan perubahan fitur dengan refactor struktural.
- Satu tahap dapat dibagi lagi menjadi beberapa commit yang dapat dikembalikan.
- Kontrak method publik dipertahankan sampai seluruh pemanggil sudah dimigrasi.
- File pengguna yang tidak berkaitan tidak dimasukkan ke commit refactor.
- Tahap berikutnya hanya dimulai setelah syntax check dan seluruh test lulus.

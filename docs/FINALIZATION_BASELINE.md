# Baseline Finalisasi

Dokumen ini mencatat titik pemulihan sebelum proses finalisasi aplikasi untuk
server lokal XAMPP yang berjalan 24 jam di jaringan LAN.

## Versi acuan

- Tanggal baseline: 22 September 2026
- Commit: `8844418afe5575c9b40a51a4e2e0a6ceb6e5cbd5`
- Tag Git: `finalization-baseline-20260922`
- Status working tree sebelum finalisasi: bersih

Tag tersebut merupakan salinan logis kode sebelum hardening, refactor,
perbaikan responsif, dan konsolidasi CSS dilakukan.

## Hasil pengujian

Sebanyak 10 berkas pengujian dijalankan terhadap database lokal. Seluruh 106
pemeriksaan lulus tanpa kegagalan. Cakupan baseline meliputi:

- status jadwal dan monitor;
- dashboard administrator;
- ketersediaan dan konflik ruangan;
- pengeditan booking;
- dokumen pendukung dan otorisasi akses;
- kalender;
- hierarki role;
- pengelolaan akun;
- kedaluwarsa otomatis pengajuan.

## Lokasi backup

Backup disimpan di luar document root Apache:

```text
C:\xampp\private\Room_Booking_System\backups\baseline-20260922-162758
```

Isi backup:

| Berkas | Isi | SHA-256 |
| --- | --- | --- |
| `database.sql` | Struktur dan data MariaDB | `64B8910BA39408227C308E5D8FA46DA497A4D5E52374CB080114412118413E3A` |
| `source-code.zip` | Source code pada commit baseline | `18BC6DB2E59564550FB01C412C208BEEB8B3B56A198D7EC98C91237F4D38F1AC` |
| `booking-documents.zip` | Dokumen pendukung pada penyimpanan privat | `056496E5912F7661739473A7536590F9CFDBDA502B1B734829EA32D948FE4EB8` |

Dump database telah diperiksa memiliki deklarasi tabel, data, dan penanda
penyelesaian MariaDB. Kedua arsip ZIP juga telah diperiksa dan dapat dibaca.

## Cara kembali ke kode baseline

Gunakan tag hanya setelah pekerjaan yang belum disimpan sudah diamankan:

```powershell
git switch --detach finalization-baseline-20260922
```

Untuk melanjutkan pengembangan dari baseline dalam branch baru:

```powershell
git switch -c recovery/finalization-baseline finalization-baseline-20260922
```

Pemulihan database dan dokumen harus dilakukan melalui prosedur restore yang
akan ditulis pada tahap operasional. Jangan mengimpor backup ke database aktif
tanpa membuat backup terbaru terlebih dahulu.

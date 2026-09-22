<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Menangani response export laporan booking untuk Administrator.
 */
final class BookingExportController extends Controller
{
    private BookingReportService $reports;

    /** Menyiapkan service laporan dari model aplikasi. */
    public function __construct()
    {
        $this->reports = new BookingReportService(
            $this->model('BookingModel'),
            $this->model('RoomModel')
        );
    }

    /** Mengirim laporan booking sebagai berkas CSV. */
    public function csv(): void
    {
        $this->requireAdmin();
        $report = $this->reports->prepare($_GET, (string) ($_SESSION['user_name'] ?? 'Administrator'));

        $filename = 'Laporan_Pemesanan_Ruangan_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');
        if ($output === false) throw new RuntimeException('Stream export CSV tidak tersedia.');

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $this->csvHeaders());
        foreach ($this->reports->csvRows($report) as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    /** Menampilkan laporan booking yang siap dicetak atau disimpan sebagai PDF. */
    public function pdf(): void
    {
        $this->requireAdmin();
        $report = $this->reports->prepare($_GET, (string) ($_SESSION['user_name'] ?? 'Administrator'));
        $this->view('admin/booking_report_pdf', $report);
    }

    /** Mengembalikan header kolom baku untuk export CSV. */
    private function csvHeaders(): array
    {
        return [
            'No',
            'Judul Agenda / Rapat',
            'Nama Pemesan (PIC)',
            'Divisi',
            'Username Pemesan',
            'Ruang Rapat',
            'Kode Ruang',
            'Lokasi',
            'Tanggal',
            'Jam Mulai',
            'Jam Selesai',
            'Durasi (Jam)',
            'Jumlah Peserta',
            'Catatan / Keperluan',
            'Status Rapat',
            'Waktu Pengajuan',
        ];
    }
}

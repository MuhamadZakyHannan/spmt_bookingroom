<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/BookingDocumentService.php';
require_once __DIR__ . '/../app/models/BookingDocumentModel.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

function expectDocument(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$pdo = Database::getInstance()->getConnection();
if (!$pdo) throw new RuntimeException('Koneksi database tidak tersedia.');

$service = new BookingDocumentService(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'meetspace-document-tests');
$formPartial = file_get_contents(__DIR__ . '/../app/views/booking/_form_fields.php');
$createView = file_get_contents(__DIR__ . '/../app/views/booking/create.php');
$editView = file_get_contents(__DIR__ . '/../app/views/booking/edit.php');
$lateUploadView = file_get_contents(__DIR__ . '/../app/views/booking/document_upload.php');
$myBookingsView = file_get_contents(__DIR__ . '/../app/views/booking/my_bookings.php');
$adminBookingsView = file_get_contents(__DIR__ . '/../app/views/admin/bookings.php');
$downloadController = file_get_contents(__DIR__ . '/../app/controllers/BookingDocumentController.php');
$bookingModelSource = file_get_contents(__DIR__ . '/../app/models/BookingModel.php');
$bookingScript = file_get_contents(__DIR__ . '/../public/js/booking-form.js');

expectDocument(
    str_contains($formPartial, 'name="supporting_document"')
        && str_contains($formPartial, 'application/pdf,image/jpeg,image/png'),
    'Form booking menyediakan input dokumen dengan format yang dibatasi.'
);
expectDocument(
    str_contains($createView, 'enctype="multipart/form-data"')
        && str_contains($editView, 'enctype="multipart/form-data"'),
    'Form pembuatan dan pengeditan mendukung upload multipart.'
);
expectDocument(
    str_contains($downloadController, '$this->requireAuth()')
        && str_contains($downloadController, 'booking_user_id')
        && str_contains($downloadController, 'is_admin()'),
    'Unduhan dokumen dilindungi autentikasi dan otorisasi pemilik atau Administrator.'
);
expectDocument(
    str_contains($bookingScript, "file.size > 5 * 1024 * 1024")
        && str_contains($bookingScript, "allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png']"),
    'Browser memberi peringatan sebelum dokumen yang terlalu besar atau salah format dikirim.'
);
expectDocument(
    str_contains($bookingModelSource, 'd.id AS document_id, d.original_name AS document_name')
        && str_contains($adminBookingsView, 'Surat Pendukung'),
    'Dokumen tersedia untuk peninjauan Administrator, termasuk pada analisis konflik jadwal.'
);
expectDocument(
    str_contains($lateUploadView, 'data-supporting-document-form')
        && str_contains($myBookingsView, 'Tambah')
        && str_contains($myBookingsView, 'booking_document_upload.php?booking_id='),
    'Pemilik dapat menyusulkan surat pendukung tanpa mengubah data jadwal booking.'
);

$noFile = $service->validate(null);
expectDocument($noFile['success'] === true && $noFile['provided'] === false, 'Dokumen bersifat opsional.');

$pdfPath = tempnam(sys_get_temp_dir(), 'meetspace_pdf_');
$largePath = tempnam(sys_get_temp_dir(), 'meetspace_large_');
file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
file_put_contents($largePath, str_repeat('A', BookingDocumentService::MAX_SIZE_BYTES + 1));

try {
    $validPdf = $service->validate([
        'name' => 'surat-pendukung.pdf',
        'tmp_name' => $pdfPath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($pdfPath),
    ]);
    expectDocument($validPdf['success'] === true && $validPdf['mime_type'] === 'application/pdf', 'PDF yang valid diterima berdasarkan MIME dan signature.');

    $wrongExtension = $service->validate([
        'name' => 'surat-pendukung.exe',
        'tmp_name' => $pdfPath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($pdfPath),
    ]);
    expectDocument($wrongExtension['success'] === false, 'Ekstensi yang tidak sesuai dengan isi file ditolak.');

    $tooLarge = $service->validate([
        'name' => 'dokumen.pdf',
        'tmp_name' => $largePath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($largePath),
    ]);
    expectDocument($tooLarge['success'] === false, 'Dokumen lebih dari 5 MB ditolak.');
    expectDocument($service->resolvePath('../config.php') === null, 'Nama file traversal tidak dapat diakses dari penyimpanan privat.');

    $notUploaded = $service->store($validPdf);
    expectDocument($notUploaded['success'] === false, 'Penyimpanan hanya menerima file dari mekanisme upload HTTP.');

    $userId = (int) $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
    $roomId = (int) $pdo->query("SELECT id FROM rooms WHERE status != 'maintenance' ORDER BY id LIMIT 1")->fetchColumn();
    if ($userId <= 0 || $roomId <= 0) throw new RuntimeException('Data uji pengguna atau ruangan tidak tersedia.');

    $prefix = 'TEST-DOCUMENT-' . bin2hex(random_bytes(4));
    $insert = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
         VALUES (?, ?, ?, '2099-12-29', '08:00:00', '09:00:00', 'Document test', 'internal_divisi', 1, 'pending', 'Document Test', 'QA')"
    );
    $insert->execute([$userId, $roomId, $prefix]);
    $bookingId = (int) $pdo->lastInsertId();
    $documentModel = new BookingDocumentModel($pdo);

    try {
        $firstName = str_repeat('a', 48) . '.pdf';
        $first = $documentModel->replace($bookingId, $userId, [
            'original_name' => 'surat-pertama.pdf',
            'stored_name' => $firstName,
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'sha256' => hash('sha256', 'first'),
        ]);
        expectDocument($first['success'] === true, 'Metadata dokumen dapat disimpan terpisah dari booking.');

        $stored = $documentModel->getForBooking($bookingId);
        expectDocument($stored && $stored['original_name'] === 'surat-pertama.pdf', 'Dokumen booking dapat dibaca kembali.');

        $secondName = str_repeat('b', 48) . '.pdf';
        $replacement = $documentModel->replace($bookingId, $userId, [
            'original_name' => 'surat-pengganti.pdf',
            'stored_name' => $secondName,
            'mime_type' => 'application/pdf',
            'size_bytes' => 120,
            'sha256' => hash('sha256', 'second'),
        ]);
        expectDocument($replacement['success'] === true && $replacement['old_stored_name'] === $firstName, 'Penggantian dokumen mengembalikan referensi file lama untuk dibersihkan.');

        $booking = (new BookingModel())->getById($bookingId);
        expectDocument((int) ($booking['document_id'] ?? 0) === (int) $replacement['document_id'], 'Data booking menyertakan referensi dokumen terbaru.');
    } finally {
        $delete = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
        $delete->execute([$bookingId]);
    }
} finally {
    @unlink($pdfPath);
    @unlink($largePath);
}

echo PHP_EOL . 'Hasil: 16 lulus, 0 gagal.' . PHP_EOL;

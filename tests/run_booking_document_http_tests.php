<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/UserModel.php';

function expectDocumentHttp(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

function httpRequest(string $url, string $cookieJar, $postFields = null): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($postFields !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postFields);
    }
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);
    if ($body === false) throw new RuntimeException('HTTP request gagal: ' . $error);
    return ['status' => $status, 'body' => $body];
}

function csrfFromHtml(string $html): string
{
    if (!preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('Token CSRF tidak ditemukan pada halaman.');
    }
    return $matches[1];
}

$pdo = Database::getInstance()->getConnection();
if (!$pdo) throw new RuntimeException('Koneksi database tidak tersedia.');

$baseUrl = 'http://localhost/Room_Booking_System';
$cookieJar = tempnam(sys_get_temp_dir(), 'meetspace_cookie_');
$pdfPath = tempnam(sys_get_temp_dir(), 'meetspace_http_pdf_');
$suffix = bin2hex(random_bytes(5));
$username = 'document-http-' . $suffix;
$password = 'Document#123';
$title = 'TEST-DOCUMENT-HTTP-' . $suffix;
$bookingId = 0;
$storedName = '';

file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");

try {
    $created = (new UserModel())->create([
        'name' => 'Document HTTP Test',
        'username' => $username,
        'department' => 'SPMT - Teknik & IT',
        'password' => $password,
        'role' => 'admin',
    ]);
    expectDocumentHttp($created, 'Akun sementara untuk pengujian HTTP berhasil dibuat.');

    $loginPage = httpRequest($baseUrl . '/login.php', $cookieJar);
    $loginToken = csrfFromHtml($loginPage['body']);
    $login = httpRequest($baseUrl . '/login.php', $cookieJar, http_build_query([
        'csrf_token' => $loginToken,
        'username' => $username,
        'password' => $password,
    ]));
    expectDocumentHttp($login['status'] === 302, 'Login HTTP berhasil dan sesi terautentikasi.');

    $bookingPage = httpRequest($baseUrl . '/booking.php', $cookieJar);
    $bookingToken = csrfFromHtml($bookingPage['body']);
    $roomId = (int) $pdo->query("SELECT id FROM rooms WHERE status != 'maintenance' ORDER BY id LIMIT 1")->fetchColumn();
    $booking = httpRequest($baseUrl . '/booking.php', $cookieJar, [
        'csrf_token' => $bookingToken,
        'user_name' => 'Document HTTP Test',
        'user_dept' => 'SPMT - Teknik & IT',
        'room_id' => (string) $roomId,
        'date' => '2099-12-28',
        'start_time' => '08:00',
        'end_time' => '09:00',
        'title' => $title,
        'activity_type' => 'internal_divisi',
        'attendees_count' => '1',
        'purpose' => 'Pengujian upload HTTP',
    ]);
    expectDocumentHttp($booking['status'] === 302, 'Booking dapat dibuat tanpa surat pendukung terlebih dahulu.');

    $findBooking = $pdo->prepare(
        "SELECT b.id AS booking_id
         FROM bookings b
         LEFT JOIN booking_documents d ON d.booking_id = b.id
         WHERE b.title = ? AND d.id IS NULL
         LIMIT 1"
    );
    $findBooking->execute([$title]);
    $bookingId = (int) $findBooking->fetchColumn();
    expectDocumentHttp($bookingId > 0, 'Booking tanpa surat pendukung tersimpan dengan benar.');

    $uploadPage = httpRequest($baseUrl . '/booking_document_upload.php?booking_id=' . $bookingId, $cookieJar);
    $uploadToken = csrfFromHtml($uploadPage['body']);
    expectDocumentHttp($uploadPage['status'] === 200, 'Halaman untuk menyusulkan surat pendukung dapat diakses pemilik booking.');

    $uploaded = httpRequest($baseUrl . '/booking_document_upload.php', $cookieJar, [
        'csrf_token' => $uploadToken,
        'booking_id' => (string) $bookingId,
        'return_to' => 'my_bookings.php',
        'supporting_document' => new CURLFile($pdfPath, 'application/pdf', 'surat-pendukung.pdf'),
    ]);
    expectDocumentHttp($uploaded['status'] === 302, 'Surat pendukung dapat diunggah menyusul tanpa mengedit jadwal booking.');

    $find = $pdo->prepare(
        "SELECT b.id AS booking_id, d.id AS document_id, d.stored_name
         FROM bookings b
         JOIN booking_documents d ON d.booking_id = b.id
         WHERE b.title = ? LIMIT 1"
    );
    $find->execute([$title]);
    $saved = $find->fetch(PDO::FETCH_ASSOC);
    expectDocumentHttp((bool) $saved, 'Metadata dokumen tersimpan dan terhubung dengan booking.');
    $bookingId = (int) $saved['booking_id'];
    $storedName = (string) $saved['stored_name'];

    $document = httpRequest($baseUrl . '/booking_document.php?id=' . (int) $saved['document_id'], $cookieJar);
    expectDocumentHttp($document['status'] === 200 && str_starts_with($document['body'], '%PDF-'), 'Pemilik booking dapat membuka dokumen privat melalui endpoint berizin.');

    $adminPage = httpRequest($baseUrl . '/admin_bookings.php', $cookieJar);
    $adminToken = csrfFromHtml($adminPage['body']);
    $deleted = httpRequest($baseUrl . '/admin_bookings.php', $cookieJar, http_build_query([
        'csrf_token' => $adminToken,
        'action' => 'delete',
        'booking_id' => (string) $bookingId,
    ]));
    $storedPath = rtrim(BOOKING_DOCUMENT_STORAGE, "\\/") . DIRECTORY_SEPARATOR . $storedName;
    expectDocumentHttp($deleted['status'] === 302 && !is_file($storedPath), 'Penghapusan booking ikut membersihkan file dokumen privat.');
    $bookingId = 0;
    $storedName = '';
} finally {
    if ($storedName !== '') {
        $filePath = rtrim(BOOKING_DOCUMENT_STORAGE, "\\/") . DIRECTORY_SEPARATOR . $storedName;
        if (is_file($filePath)) @unlink($filePath);
    }
    if ($bookingId > 0) {
        $deleteBooking = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
        $deleteBooking->execute([$bookingId]);
    }
    $deleteUser = $pdo->prepare('DELETE FROM users WHERE username = ?');
    $deleteUser->execute([$username]);
    @unlink($cookieJar);
    @unlink($pdfPath);
}

echo PHP_EOL . 'Hasil: 9 lulus, 0 gagal.' . PHP_EOL;

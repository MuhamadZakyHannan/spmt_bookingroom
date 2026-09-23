<?php

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/core/Organization.php';
require_once __DIR__ . '/../app/core/helpers.php';

function assertCustom(bool $condition, string $message): void
{
    if (!$condition) {
        echo "[FAIL] {$message}" . PHP_EOL;
        exit(1);
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

echo "Memulai pengujian notifikasi 2 detik, sinkronisasi divisi booking, dan format SPMT..." . PHP_EOL;

// 1. Pengujian auto-dismiss flash notification dalam 2 detik
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Pengguna "spmtkreatif" berhasil dihapus.'
];
ob_start();
display_flash();
$flashHtml = ob_get_clean();

assertCustom(strpos($flashHtml, "id='flashAlert'") !== false, "flashAlert berhasil dirender.");
assertCustom(strpos($flashHtml, "setTimeout(dismissFlashAlert, 2000)") !== false, "flashAlert diatur hilang dalam 2 detik (2000 ms).");
assertCustom(strpos($flashHtml, "dismissFlashAlert") !== false, "Fungsi dismissFlashAlert dengan transisi smooth terpasang.");

// 2. Pengujian normalisasi format divisi
assertCustom(
    Organization::normalizeDepartment('SPMT -  Kreatif') === 'SPMT - Kreatif',
    "Pembersihan spasi ganda 'SPMT -  Kreatif' => 'SPMT - Kreatif'."
);
assertCustom(
    Organization::normalizeDepartment('spmt - kreatif') === 'SPMT - Kreatif',
    "Penyelarasan huruf kecil 'spmt - kreatif' => 'SPMT - Kreatif'."
);
assertCustom(
    Organization::normalizeDepartment('kreatif') === 'SPMT - Kreatif',
    "Penambahan otomatis prefix SPMT 'kreatif' => 'SPMT - Kreatif'."
);
assertCustom(
    Organization::normalizeDepartment('Subreg - Keuangan') === 'Subreg - Keuangan',
    "Format Subreg tetap dipertahankan 'Subreg - Keuangan'."
);
assertCustom(
    Organization::normalizeDepartment('subreg teknik') === 'Subreg - Teknik',
    "Penyelarasan Subreg 'subreg teknik' => 'Subreg - Teknik'."
);
assertCustom(
    Organization::normalizeDepartment('SPMT - Teknik & IT') === 'SPMT - Teknik & IT',
    "Akronim dan simbol 'SPMT - Teknik & IT' dipertahankan."
);

// 3. Pengujian integrasi penambahan pengguna dan bertambahnya divisi di form pemesanan
$userModel = new UserModel();
$db = Database::getInstance()->getConnection();

$testDept = 'SPMT - Kreatif';
$testUsername = 'test_kreatif_' . time();

$db->beginTransaction();
try {
    $created = $userModel->create([
        'name' => 'Staf Kreatif',
        'username' => $testUsername,
        'department' => $testDept,
        'role' => 'user',
        'password' => 'PassKreatif123'
    ]);
    assertCustom($created, "Berhasil membuat user uji dengan divisi '{$testDept}'.");

    // Periksa apakah divisi baru muncul di Organization::getAllDepartments()
    $allDepts = Organization::getAllDepartments();
    assertCustom(in_array($testDept, $allDepts, true), "Divisi baru '{$testDept}' otomatis muncul di Organization::getAllDepartments().");

    // Render form fields pemesanan dan periksa apakah opsi muncul
    ob_start();
    $bookingFormDepartments = null; // Biarkan form mengambil dari Organization::getAllDepartments()
    $bookingFormPrefix = 'testBooking';
    $bookingFormValues = ['user_dept' => ''];
    require __DIR__ . '/../app/views/booking/_form_fields.php';
    $formHtml = ob_get_clean();

    assertCustom(
        strpos($formHtml, '<option value="' . $testDept . '"') !== false,
        "Dropdown divisi di form pemesanan menyertakan divisi '{$testDept}' yang baru ditambahkan."
    );

    // 4. Pengujian bahwa fungsi hapus user tetap berfungsi
    $createdUser = $userModel->getByUsername($testUsername);
    assertCustom($createdUser !== false, "User uji ditemukan.");
    $deleted = $userModel->delete($createdUser['id']);
    assertCustom($deleted, "User uji berhasil dihapus menggunakan UserModel::delete().");
    assertCustom($userModel->getById($createdUser['id']) === false, "User uji sudah tidak ada di database.");

} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

echo PHP_EOL . "Seluruh pengujian sukses 100%!" . PHP_EOL;

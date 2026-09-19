<?php
// MeetSpace Database Diagnostic Script for XAMPP
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Diagnosa Koneksi Database - MeetSpace</title>
    <!-- Tailwind CSS (Local Compiled Standalone) -->
    <link rel="stylesheet" href="public/css/tailwind.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen py-12 px-4 antialiased flex items-center justify-center">
<div class="w-full max-w-2xl">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2 mb-2">
            <i class="fas fa-stethoscope text-brand-600"></i> Diagnosa Koneksi Database XAMPP
        </h1>
        <p class="text-xs text-slate-500 mb-6 pb-4 border-b border-slate-100">
            Halaman ini membantu mengecek status koneksi MySQL dan ketersediaan database <code class="px-1.5 py-0.5 bg-slate-100 rounded text-brand-600 font-mono">meetspace_db</code>.
        </p>

        <div class="space-y-4 text-xs">
            <?php
            $db_host = "localhost";
            $db_user = "root";
            $db_pass = "";
            $db_name = "meetspace_db";

            echo "<div class='p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between'>
                    <span class='font-bold text-slate-700'>1. Cek Ekstensi PDO MySQL:</span>";
            if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
                echo "<span class='px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-lg flex items-center gap-1'><i class='fas fa-check'></i> Aktif (OK)</span></div>";
            } else {
                echo "<span class='px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-lg flex items-center gap-1'><i class='fas fa-times'></i> Tidak Aktif</span></div>";
                echo "<div class='p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs'><strong>Solusi:</strong> Buka <code class='font-mono'>php.ini</code> di XAMPP dan hilangkan titik koma pada <code class='font-mono'>;extension=pdo_mysql</code> lalu restart Apache.</div>";
            }

            echo "<div class='p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between'>
                    <span class='font-bold text-slate-700'>2. Cek Koneksi Server MySQL ($db_host):</span>";
            try {
                $pdo_test = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                echo "<span class='px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-lg flex items-center gap-1'><i class='fas fa-check'></i> Terhubung (OK)</span></div>";

                echo "<div class='p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between'>
                        <span class='font-bold text-slate-700'>3. Cek Database '$db_name':</span>";
                $stmt = $pdo_test->query("SHOW DATABASES LIKE '$db_name'");
                if ($stmt->fetch()) {
                    echo "<span class='px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-lg flex items-center gap-1'><i class='fas fa-check'></i> Ditemukan (OK)</span></div>";

                    // Check Tables
                    $pdo_test->exec("USE `$db_name`");
                    $tables = $pdo_test->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    echo "<div class='p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between'>
                            <span class='font-bold text-slate-700'>4. Tabel dalam Database:</span>";
                    if (count($tables) > 0) {
                        echo "<span class='px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-lg'>" . count($tables) . " Tabel Ditemukan (" . implode(', ', $tables) . ")</span></div>";
                        echo "<div class='p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center gap-3 mt-4'>
                                <i class='fas fa-check-circle text-emerald-600 text-xl'></i>
                                <div><strong>Selamat!</strong> Koneksi database XAMPP Anda berfungsi 100% sempurna. Anda dapat membuka <a href='index.php' class='font-bold underline text-emerald-900'>Aplikasi MeetSpace</a>.</div>
                              </div>";
                    } else {
                        echo "<span class='px-2.5 py-1 bg-amber-100 text-amber-800 font-bold rounded-lg'>Tabel Kosong</span></div>";
                        echo "<div class='p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs flex items-center gap-3 mt-4'>
                                <i class='fas fa-exclamation-triangle text-amber-600 text-xl'></i>
                                <div>Database <code class='font-mono'>meetspace_db</code> sudah ada tetapi belum di-import tabelnya. Silakan import file <code class='font-mono'>database.sql</code> via phpMyAdmin.</div>
                              </div>";
                    }

                } else {
                    echo "<span class='px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-lg flex items-center gap-1'><i class='fas fa-times'></i> Belum Dibuat</span></div>";
                    echo "<div class='p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-3 mt-4'>
                            <i class='fas fa-info-circle text-rose-600 text-xl'></i>
                            <div>Database <code class='font-mono'>$db_name</code> belum ada. Buka <a href='http://localhost/phpmyadmin' target='_blank' class='font-bold underline'>phpMyAdmin</a>, buat database <code class='font-mono'>$db_name</code>, lalu import file <code class='font-mono'>database.sql</code>.</div>
                          </div>";
                }

            } catch (PDOException $e) {
                echo "<span class='px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-lg flex items-center gap-1'><i class='fas fa-times'></i> Gagal Terhubung</span></div>";
                echo "<div class='p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs mt-3'>
                        <div class='font-bold mb-1 flex items-center gap-1.5'><i class='fas fa-exclamation-circle text-rose-600'></i> Detail Error MySQL:</div>
                        <code class='font-mono text-[11px] block bg-white p-2 rounded border border-rose-200 text-rose-900 overflow-x-auto'>" . htmlspecialchars($e->getMessage()) . "</code>
                      </div>";

                echo "<div class='p-4 bg-slate-50 border border-slate-200/80 rounded-xl mt-3 text-xs text-slate-700'>
                        <div class='font-bold mb-2 flex items-center gap-1.5'><i class='fas fa-wrench text-brand-600'></i> Langkah Pemecahan Masalah:</div>
                        <ol class='list-decimal list-inside space-y-1 text-slate-600'>
                            <li>Pastikan service <strong>MySQL</strong> di XAMPP Control Panel dalam kondisi <strong>Running (Hijau)</strong>.</li>
                            <li>Jika MySQL crash/berhenti, cek apakah port 3306 dipakai oleh aplikasi lain (seperti MySQL Installer/MariaDB).</li>
                            <li>Jika Anda menggunakan password MySQL di XAMPP, buka file <code class='font-mono'>config.php</code> dan ganti <code class='font-mono'>\$db_pass = 'password_anda';</code>.</li>
                            <li>Jika Anda mengubah port MySQL di XAMPP menjadi 3307, ubah <code class='font-mono'>\$db_host = '127.0.0.1;port=3307';</code> di <code class='font-mono'>config.php</code>.</li>
                        </ol>
                      </div>";
            }
            ?>
        </div>

        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-center gap-3">
            <a href="test_db.php" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition flex items-center gap-1.5">
                <i class="fas fa-sync-alt"></i> Cek Ulang Koneksi
            </a>
            <a href="index.php" class="py-2 px-5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-md text-xs transition flex items-center gap-1.5">
                <i class="fas fa-home"></i> Kembali ke Aplikasi
            </a>
        </div>
    </div>
</div>
</body>
</html>

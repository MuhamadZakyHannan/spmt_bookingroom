<?php

/**
 * Menyediakan satu koneksi PDO yang konsisten untuk seluruh request aplikasi.
 */
final class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    /**
     * Membuat koneksi dengan native prepared statements dan timezone WIB.
     */
    private function __construct()
    {
        $dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
        $dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
        $dbPass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        $dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'meetspace_db');

        try {
            $this->pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_TIMEOUT => 5,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'",
            ]);
        } catch (PDOException $e) {
            error_log('Koneksi Database singleton gagal: ' . $e->getMessage());
            $this->pdo = null;
        }
    }

    /**
     * Mengambil satu-satunya instance koneksi dalam proses PHP saat ini.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Mengembalikan PDO atau null apabila database tidak dapat dijangkau.
     */
    public function getConnection(): ?PDO
    {
        return $this->pdo;
    }
}

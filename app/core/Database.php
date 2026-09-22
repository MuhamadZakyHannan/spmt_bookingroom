<?php
/**
 * Database Singleton Connection Handler
 */
class Database {
    private static $instance = null;
    private $pdo = null;

    private function __construct() {
        $db_host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
        $db_user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
        $db_pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        $db_name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'meetspace_db');

        try {
            $this->pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Koneksi Database singleton gagal: ' . $e->getMessage());
            $this->pdo = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

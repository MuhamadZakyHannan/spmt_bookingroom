<?php
/**
 * Database Singleton Connection Handler
 */
class Database {
    private static $instance = null;
    private $pdo = null;

    private function __construct() {
        $db_host = "localhost";
        $db_user = "root";
        $db_pass = "";
        $db_name = "meetspace_db";

        try {
            $this->pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
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

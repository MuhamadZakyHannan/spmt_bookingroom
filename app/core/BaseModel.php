<?php

require_once __DIR__ . '/Database.php';

/**
 * Fondasi model dengan koneksi PDO yang dapat disuntikkan saat pengujian.
 *
 * Model produksi tetap memakai koneksi singleton aplikasi. Constructor
 * injection mencegah setiap model mengulang cara memperoleh koneksi dan
 * memudahkan pengujian terisolasi pada tahap refactor berikutnya.
 */
abstract class BaseModel
{
    protected ?PDO $db;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? Database::getInstance()->getConnection();
    }
}

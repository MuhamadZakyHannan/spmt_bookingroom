<?php

final class Organization
{
    public const DEPARTMENTS = [
        'SPMT - Pendukung Operasi',
        'SPMT - Teknik & IT',
        'SPMT - Rendal OPS',
        'SPMT - Integrated PNC',
        'SPMT - Operasional',
        'SPMT - Ruang Rapat dan Branch Manager',
        'SPMT - SPJM',
        'Subreg - Keuangan',
        'Subreg - Teknik',
        'Subreg - Integraterd PNC',
        'Subreg - Komersial',
        'Subreg - Arsip',
    ];

    /** Memeriksa apakah valid department terpenuhi (resmi atau terdaftar di pengguna). */
    public static function isValidDepartment(string $department): bool
    {
        return in_array($department, self::getAllDepartments(), true);
    }

    /** Mengambil semua divisi terdaftar (daftar resmi digabung dengan divisi pengguna di database). */
    public static function getAllDepartments(): array
    {
        $departments = self::DEPARTMENTS;
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            if ($db) {
                $stmt = $db->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != ''");
                $userDepts = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($userDepts as $d) {
                    $d = trim((string)$d);
                    if ($d !== '' && !in_array($d, $departments, true)) {
                        $departments[] = $d;
                    }
                }
            }
        } catch (Throwable $e) {
            // fallback to DEPARTMENTS constant
        }
        return $departments;
    }

    /** Menstandarkan format penulisan divisi ke format standar, contoh: 'SPMT - Kreatif'. */
    public static function normalizeDepartment(string $dept): string
    {
        $dept = trim($dept);
        if ($dept === '') return '';

        if (preg_match('/^([A-Za-z0-9]+)\s*-\s*(.+)$/u', $dept, $m)) {
            $p = strtoupper($m[1]) === 'SUBREG' ? 'Subreg' : strtoupper($m[1]);
            $name = trim($m[2]);
        } elseif (preg_match('/^(spmt|subreg)\s+(.+)$/i', $dept, $m)) {
            $p = strtolower($m[1]) === 'subreg' ? 'Subreg' : 'SPMT';
            $name = trim($m[2]);
        } else {
            $p = 'SPMT';
            $name = $dept;
        }

        $words = preg_split('/\s+/', $name);
        foreach ($words as &$w) {
            if ($w !== '&' && $w !== 'dan' && !preg_match('/^[A-Z0-9&]+$/', $w)) {
                $w = ucfirst(strtolower($w));
            }
        }
        $name = implode(' ', $words);
        return "{$p} - {$name}";
    }
}

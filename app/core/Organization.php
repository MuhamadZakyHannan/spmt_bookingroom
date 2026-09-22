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

    /** Memeriksa apakah valid department terpenuhi. */
    public static function isValidDepartment(string $department): bool
    {
        return in_array($department, self::DEPARTMENTS, true);
    }
}

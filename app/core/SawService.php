<?php
/**
 * SawService.php
 * Sistem Pendukung Keputusan (SPK) Metode Simple Additive Weighting (SAW)
 * Untuk Penentuan Prioritas & Resolusi Konflik Jadwal Pemesanan Ruangan
 */

class SawService {

    // Bobot Kriteria (Total = 1.00 / 100%)
    const WEIGHTS = [
        'k1' => 0.30, // Tingkat Kepentingan Kegiatan (Benefit)
        'k2' => 0.20, // Jumlah Peserta (Benefit)
        'k3' => 0.15, // Durasi Penggunaan (Cost)
        'k4' => 0.20, // Waktu Pengajuan (Benefit)
        'k5' => 0.15  // Frekuensi Penggunaan Ruangan oleh Divisi (Cost)
    ];

    // Tipe Atribut Kriteria ('benefit' atau 'cost')
    const ATTRIBUTES = [
        'k1' => 'benefit',
        'k2' => 'benefit',
        'k3' => 'cost',
        'k4' => 'benefit',
        'k5' => 'cost'
    ];

    // Label & Deskripsi Kriteria
    const CRITERIA_INFO = [
        'k1' => [
            'name' => 'Tingkat Kepentingan Kegiatan',
            'weight_percent' => '30%',
            'attribute' => 'Benefit',
            'desc' => 'Semakin tinggi urgensi/level meeting, semakin besar prioritasnya.'
        ],
        'k2' => [
            'name' => 'Jumlah Peserta',
            'weight_percent' => '20%',
            'attribute' => 'Benefit',
            'desc' => 'Semakin banyak peserta rapat, pemanfaatan ruangan semakin efisien.'
        ],
        'k3' => [
            'name' => 'Durasi Penggunaan',
            'weight_percent' => '15%',
            'attribute' => 'Cost',
            'desc' => 'Durasi yang lebih ringkas lebih disukai agar ruangan dapat dipakai tim lain.'
        ],
        'k4' => [
            'name' => 'Waktu Pengajuan',
            'weight_percent' => '20%',
            'attribute' => 'Benefit',
            'desc' => 'Pengajuan yang direncanakan jauh-jauh hari lebih diprioritaskan dibanding mendadak.'
        ],
        'k5' => [
            'name' => 'Frekuensi Penggunaan oleh Divisi',
            'weight_percent' => '15%',
            'attribute' => 'Cost',
            'desc' => 'Divisi yang jarang menggunakan ruangan pada bulan ini diberi kesempatan lebih.'
        ]
    ];

    // Opsi Sub-Kriteria K1 (Tingkat Kepentingan Kegiatan)
    const ACTIVITY_TYPES = [
        'direksi_eksternal' => [
            'label' => 'Rapat dengan Direksi / Pihak Eksternal',
            'score' => 5,
            'badge_color' => 'rose'
        ],
        'antar_divisi' => [
            'label' => 'Rapat Koordinasi Antar Divisi',
            'score' => 4,
            'badge_color' => 'amber'
        ],
        'internal_divisi' => [
            'label' => 'Rapat Internal Divisi',
            'score' => 3,
            'badge_color' => 'blue'
        ],
        'pelatihan' => [
            'label' => 'Sosialisasi / Pelatihan',
            'score' => 2,
            'badge_color' => 'emerald'
        ],
        'rutin' => [
            'label' => 'Kegiatan Rutin / Non-Prioritas',
            'score' => 1,
            'badge_color' => 'slate'
        ]
    ];

    /**
     * Menghitung nilai mentah (sub-kriteria) untuk sebuah booking
     */
    public static function getRawScores(array $booking, int $deptMonthlyUsage = 0): array {
        // K1: Tingkat Kepentingan Kegiatan (Benefit)
        $actKey = $booking['activity_type'] ?? 'internal_divisi';
        $k1Data = self::ACTIVITY_TYPES[$actKey] ?? self::ACTIVITY_TYPES['internal_divisi'];
        $k1 = $k1Data['score'];
        $k1Label = $k1Data['label'];

        // K2: Jumlah Peserta (Benefit)
        $attendees = (int)($booking['attendees_count'] ?? 1);
        if ($attendees > 20) {
            $k2 = 4;
            $k2Label = "> 20 orang ({$attendees} org)";
        } else if ($attendees >= 11) {
            $k2 = 3;
            $k2Label = "11–20 orang ({$attendees} org)";
        } else if ($attendees >= 5) {
            $k2 = 2;
            $k2Label = "5–10 orang ({$attendees} org)";
        } else {
            $k2 = 1;
            $k2Label = "< 5 orang ({$attendees} org)";
        }

        // K3: Durasi Penggunaan (Cost)
        $startTime = strtotime($booking['start_time']);
        $endTime = strtotime($booking['end_time']);
        $durationHours = max(1, ceil(($endTime - $startTime) / 3600));

        if ($durationHours <= 1) {
            $k3 = 1;
            $k3Label = "1 jam";
        } else if ($durationHours == 2) {
            $k3 = 2;
            $k3Label = "2 jam";
        } else if ($durationHours == 3) {
            $k3 = 3;
            $k3Label = "3 jam";
        } else {
            $k3 = 4;
            $k3Label = "≥ 4 jam ({$durationHours} jam)";
        }

        // K4: Waktu Pengajuan (Benefit)
        $meetingDate = strtotime($booking['date']);
        $bookingCreatedAt = !empty($booking['created_at']) ? strtotime(date('Y-m-d', strtotime($booking['created_at']))) : time();
        $diffDays = floor(($meetingDate - $bookingCreatedAt) / 86400);

        if ($diffDays >= 3) {
            $k4 = 3;
            $k4Label = "Diajukan H-3 atau lebih awal ({$diffDays} hari lalu)";
        } else if ($diffDays >= 1) {
            $k4 = 2;
            $k4Label = "Diajukan H-1 s.d. H-2 ({$diffDays} hari lalu)";
        } else {
            $k4 = 1;
            $k4Label = "Diajukan di hari yang sama (mendadak)";
        }

        // K5: Frekuensi Penggunaan Ruangan oleh Divisi (Cost)
        if ($deptMonthlyUsage <= 2) {
            $k5 = 3;
            $k5Label = "Jarang ({$deptMonthlyUsage}x bulan ini)";
        } else if ($deptMonthlyUsage <= 5) {
            $k5 = 2;
            $k5Label = "Cukup sering ({$deptMonthlyUsage}x bulan ini)";
        } else {
            $k5 = 1;
            $k5Label = "Sering ({$deptMonthlyUsage}x bulan ini)";
        }

        return [
            'scores' => [
                'k1' => $k1,
                'k2' => $k2,
                'k3' => $k3,
                'k4' => $k4,
                'k5' => $k5
            ],
            'labels' => [
                'k1' => $k1Label,
                'k2' => $k2Label,
                'k3' => $k3Label,
                'k4' => $k4Label,
                'k5' => $k5Label
            ]
        ];
    }

    /**
     * Menganalisis sekelompok booking yang bertabrakan menggunakan metode SAW
     * @param array $conflictingBookings Daftar booking yang berkonflik
     * @param callable $usageCallback Fungsi pengambil frekuensi penggunaan divisi
     * @return array Hasil analisis SAW lengkap
     */
    public static function analyzeConflictGroup(array $conflictingBookings, callable $usageCallback): array {
        if (count($conflictingBookings) < 2) {
            return [];
        }

        $alternatives = [];
        $rawMatrix = [];

        // 1. Ekstraksi Nilai Mentah Alternatif (X)
        $index = 1;
        foreach ($conflictingBookings as $b) {
            $altCode = 'A' . $index;
            $deptUsage = $usageCallback($b['user_dept'] ?? '', $b['date'] ?? date('Y-m-d'));
            $raw = self::getRawScores($b, $deptUsage);

            $alternatives[$altCode] = [
                'code' => $altCode,
                'booking_id' => $b['id'],
                'booking' => $b,
                'raw_scores' => $raw['scores'],
                'raw_labels' => $raw['labels']
            ];

            $rawMatrix[$altCode] = $raw['scores'];
            $index++;
        }

        // 2. Cari Nilai Max (Benefit) & Min (Cost) untuk Setiap Kriteria
        $maxMin = [];
        foreach (self::WEIGHTS as $crit => $weight) {
            $columnValues = array_column($rawMatrix, $crit);
            $maxMin[$crit] = [
                'max' => !empty($columnValues) ? max($columnValues) : 1,
                'min' => !empty($columnValues) ? min($columnValues) : 1
            ];
        }

        // 3. Normalisasi Matriks (R)
        $normalizedMatrix = [];
        foreach ($rawMatrix as $altCode => $scores) {
            foreach ($scores as $crit => $val) {
                $attr = self::ATTRIBUTES[$crit];
                if ($attr === 'benefit') {
                    // Benefit: R_ij = X_ij / Max(X_j)
                    $maxVal = $maxMin[$crit]['max'];
                    $r = $maxVal > 0 ? ($val / $maxVal) : 0;
                } else {
                    // Cost: R_ij = Min(X_j) / X_ij
                    $minVal = $maxMin[$crit]['min'];
                    $r = $val > 0 ? ($minVal / $val) : 0;
                }
                $normalizedMatrix[$altCode][$crit] = round($r, 4);
            }
        }

        // 4. Hitung Nilai Akhir Preferensi (V)
        $results = [];
        foreach ($normalizedMatrix as $altCode => $rScores) {
            $v = 0;
            $breakdown = [];
            foreach ($rScores as $crit => $rVal) {
                $w = self::WEIGHTS[$crit];
                $part = $w * $rVal;
                $v += $part;
                $breakdown[$crit] = [
                    'weight' => $w,
                    'normalized' => $rVal,
                    'contribution' => round($part, 4)
                ];
            }

            $finalV = round($v, 4);
            $results[$altCode] = array_merge($alternatives[$altCode], [
                'normalized' => $rScores,
                'breakdown' => $breakdown,
                'preference_score' => $finalV
            ]);
        }

        // 5. Ranking Alternatif Berdasarkan Skor Tertinggi
        uasort($results, function($a, $b) {
            if ($a['preference_score'] == $b['preference_score']) {
                return 0;
            }
            return ($a['preference_score'] > $b['preference_score']) ? -1 : 1;
        });

        $rank = 1;
        $winner = null;
        foreach ($results as $altCode => &$item) {
            $item['rank'] = $rank;
            if ($rank === 1) {
                $winner = $item;
            }
            $rank++;
        }
        unset($item);

        return [
            'criteria' => self::CRITERIA_INFO,
            'weights' => self::WEIGHTS,
            'attributes' => self::ATTRIBUTES,
            'max_min' => $maxMin,
            'results' => $results,
            'winner' => $winner
        ];
    }
}

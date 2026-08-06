<?php

namespace App\Support;

/**
 * Helper murni untuk import format penilaian dari file Excel.
 *
 * Struktur file (satu sheet):
 *   Tipe | Kategori | Sub-Kategori | Kriteria | Label 1 | Skor 1 | Label 2 | Skor 2 | ... | Bobot
 *
 * - Baris Tipe = "Rubrik"      → membangun assessment_categories / sub / criteria.
 * - Baris Tipe = "Pengurangan" → membangun deduction_categories / criteria,
 *   menempel ke kategori rubrik aktif (baris Rubrik terakhir yang sama).
 * - Baris kosong / Tipe tak dikenal → dilewati (dictatat sebagai error baris).
 *
 * Label & skor dipisah menjadi kolom berpasangan. Untuk tiap pasangan:
 *   - Label kosong → skor polos (mis. "5").
 *   - Label terisi → objek {score, label} (mis. "Kurang" + "0-25").
 *   - Satu sel skor boleh memuat beberapa nilai dipisah koma (mis. "23, 30"),
 *     semuanya memakai label pada pasangan yang sama.
 */
class FormatNilaiImport
{
    /** Batas jumlah opsi per kriteria — sinkron dengan Builder::MAX_OPTIONS. */
    public const MAX_OPTIONS = 20;

    /** Banyak pasangan Label/Skor yang didukung per baris. */
    public const MAX_SCORE_PAIRS = 6;

    // Indeks kolom (0-based dari $sheet->toArray()).
    public const COL_TIPE = 0;

    public const COL_KATEGORI = 1;

    public const COL_SUB = 2;

    public const COL_KRITERIA = 3;

    public const COL_LABEL_START = 4; // E = Label 1

    public const COL_BOBOT = 4 + 2 * self::MAX_SCORE_PAIRS; // setelah semua pasangan

    public const TYPE_RUBRIK = 'rubrik';

    public const TYPE_PENGURANGAN = 'pengurangan';

    /**
     * Bangun score_options dari kolom pasangan Label/Skor pada satu baris.
     *
     * @return array<int, int|string|array{score:string,label:string}> kosong bila tak valid.
     */
    public static function parseScoreOptionsFromPairs(array $row): array
    {
        $allScores = [];
        $hasLabels = false;
        $optionCount = 0;

        for ($i = 0; $i < self::MAX_SCORE_PAIRS; $i++) {
            $label = trim((string) ($row[self::COL_LABEL_START + $i * 2] ?? ''));
            $scoreRaw = trim((string) ($row[self::COL_LABEL_START + $i * 2 + 1] ?? ''));

            if ($scoreRaw === '' && $label === '') {
                continue;
            }

            if ($scoreRaw === '') {
                return []; // label tanpa skor → baris tidak valid
            }

            if ($label !== '') {
                $hasLabels = true;
            }

            $parts = array_values(array_filter(array_map('trim', explode(',', $scoreRaw)), fn ($v) => $v !== ''));

            if (empty($parts)) {
                return [];
            }

            foreach ($parts as $part) {
                $optionCount++;
                if ($optionCount > self::MAX_OPTIONS) {
                    return [];
                }

                $allScores[] = $label !== ''
                    ? ['score' => $part, 'label' => $label]
                    : $part;
            }
        }

        if (empty($allScores)) {
            return [];
        }

        // Normalisasi: bila ada label, semua opsi menjadi objek (label kosong bila polos).
        if ($hasLabels) {
            return array_values(array_map(
                fn ($s) => is_array($s) ? $s : ['score' => $s, 'label' => ''],
                $allScores
            ));
        }

        return array_values($allScores);
    }

    /**
     * Bangun deduction_options dari kolom Skor (label diabaikan).
     * Semua nilai harus angka (negatif biasanya); boleh dipisah koma.
     *
     * @return array<int, string> kosong bila tak valid.
     */
    public static function parseDeductionOptionsFromPairs(array $row): array
    {
        $options = [];

        for ($i = 0; $i < self::MAX_SCORE_PAIRS; $i++) {
            $scoreRaw = trim((string) ($row[self::COL_LABEL_START + $i * 2 + 1] ?? ''));

            if ($scoreRaw === '') {
                continue;
            }

            $parts = array_values(array_filter(array_map('trim', explode(',', $scoreRaw)), fn ($v) => $v !== ''));

            foreach ($parts as $part) {
                if (! is_numeric($part)) {
                    return [];
                }

                $options[] = $part;

                if (count($options) > self::MAX_OPTIONS) {
                    return [];
                }
            }
        }

        return $options;
    }

    /**
     * Normalisasi seluruh baris menjadi struktur bertingkat siap-simpan.
     *
     * @param  array<int, array<int, mixed>>  $rows  2D array dari spreadsheet (tanpa header).
     * @return array{
     *   rubrik: array<int, array{name:string,subCategories:array<int,array{name:string,criterias:array<int,array{name:string,score_options:array,weight:float}>}>}>,
     *   pengurangan: array<int, array{rubrik_index:int,name:string,criterias:array<int,array{name:string,deduction_options:array}>}>,
     *   errors: array<int, array{row:int,message:string}>
     * }
     */
    public static function normalizeRows(array $rows): array
    {
        $result = [
            'rubrik' => [],
            'pengurangan' => [],
            'errors' => [],
        ];

        $activeRubrikIndex = null;
        $activeSubIndex = null;

        foreach ($rows as $rowIndex => $row) {
            $cell = fn (int $i) => trim((string) ($row[$i] ?? ''));

            $tipe = strtolower($cell(self::COL_TIPE));
            $kategori = $cell(self::COL_KATEGORI);
            $sub = $cell(self::COL_SUB);
            $kriteria = $cell(self::COL_KRITERIA);
            $bobot = $cell(self::COL_BOBOT);
            $rowNo = $rowIndex + 1; // +1 header, +1 1-based

            if ($tipe === '') {
                continue; // baris kosong
            }

            if (str_contains($tipe, self::TYPE_RUBRIK)) {
                if ($kategori === '' || $kriteria === '') {
                    $result['errors'][] = [
                        'row' => $rowNo,
                        'message' => 'Baris Rubrik wajib berisi Kategori dan Kriteria.',
                    ];

                    continue;
                }

                $scoreOptions = self::parseScoreOptionsFromPairs($row);

                if (empty($scoreOptions)) {
                    $result['errors'][] = [
                        'row' => $rowNo,
                        'message' => 'Skor tidak valid: isi minimal satu pasangan Skor (dengan Label opsional).',
                    ];

                    continue;
                }

                $weight = ($bobot !== '' && is_numeric($bobot)) ? (float) $bobot : 1.0;

                // Kategori baru bila nama berubah.
                if ($activeRubrikIndex === null || $result['rubrik'][$activeRubrikIndex]['name'] !== $kategori) {
                    $activeRubrikIndex = count($result['rubrik']);
                    $result['rubrik'][] = ['name' => $kategori, 'subCategories' => []];
                    $activeSubIndex = null;
                }

                // Sub-kategori baru bila nama berubah.
                $rubrik = &$result['rubrik'][$activeRubrikIndex];
                if ($activeSubIndex === null || $rubrik['subCategories'][$activeSubIndex]['name'] !== $sub) {
                    $activeSubIndex = count($rubrik['subCategories']);
                    $rubrik['subCategories'][] = ['name' => $sub, 'criterias' => []];
                }

                $rubrik['subCategories'][$activeSubIndex]['criterias'][] = [
                    'name' => $kriteria,
                    'score_options' => $scoreOptions,
                    'weight' => $weight,
                ];
                unset($rubrik);
            } elseif (str_contains($tipe, self::TYPE_PENGURANGAN)) {
                if ($activeRubrikIndex === null) {
                    $result['errors'][] = [
                        'row' => $rowNo,
                        'message' => 'Baris Pengurangan harus mengikuti baris Rubrik.',
                    ];

                    continue;
                }

                if ($kategori === '' || $kriteria === '') {
                    $result['errors'][] = [
                        'row' => $rowNo,
                        'message' => 'Baris Pengurangan wajib berisi Kategori dan Kriteria.',
                    ];

                    continue;
                }

                $options = self::parseDeductionOptionsFromPairs($row);

                if (empty($options)) {
                    $result['errors'][] = [
                        'row' => $rowNo,
                        'message' => 'Skor pengurangan harus berupa angka (negatif), isi di kolom Skor.',
                    ];

                    continue;
                }

                // Kelompok pengurangan menempel ke kategori rubrik aktif.
                $rubrikIndex = $activeRubrikIndex;
                $foundGroup = false;
                foreach ($result['pengurangan'] as $i => $group) {
                    if ($group['rubrik_index'] === $rubrikIndex && $group['name'] === $kategori) {
                        $result['pengurangan'][$i]['criterias'][] = [
                            'name' => $kriteria,
                            'deduction_options' => $options,
                        ];
                        $foundGroup = true;
                        break;
                    }
                }

                if (! $foundGroup) {
                    $result['pengurangan'][] = [
                        'rubrik_index' => $rubrikIndex,
                        'name' => $kategori,
                        'criterias' => [
                            ['name' => $kriteria, 'deduction_options' => $options],
                        ],
                    ];
                }
            } else {
                $original = trim((string) ($row[self::COL_TIPE] ?? ''));
                $result['errors'][] = [
                    'row' => $rowNo,
                    'message' => "Tipe '{$original}' tidak dikenal. Gunakan 'Rubrik' atau 'Pengurangan'.",
                ];
            }
        }

        return $result;
    }

    /**
     * Ubah struktur ternormalisasi menjadi baris-baris datar untuk preview UI.
     *
     * @return array<int, array{tipe:string,kategori:string,sub:string,kriteria:string,skor:string,bobot:string}>
     */
    public static function previewRows(array $normalized): array
    {
        $rows = [];

        foreach ($normalized['rubrik'] as $cat) {
            foreach ($cat['subCategories'] as $sub) {
                foreach ($sub['criterias'] as $crit) {
                    $rows[] = [
                        'tipe' => 'Rubrik',
                        'kategori' => $cat['name'],
                        'sub' => $sub['name'],
                        'kriteria' => $crit['name'],
                        'skor' => self::formatScoreOptions($crit['score_options']),
                        'bobot' => $crit['weight'] != 1 ? (string) $crit['weight'] : '1',
                    ];
                }
            }
        }

        foreach ($normalized['pengurangan'] as $group) {
            $attachedTo = $normalized['rubrik'][$group['rubrik_index']]['name'] ?? '';
            foreach ($group['criterias'] as $crit) {
                $rows[] = [
                    'tipe' => 'Pengurangan',
                    'kategori' => $group['name'],
                    'sub' => $attachedTo !== '' ? "→ {$attachedTo}" : '',
                    'kriteria' => $crit['name'],
                    'skor' => implode(', ', $crit['deduction_options']),
                    'bobot' => '',
                ];
            }
        }

        return $rows;
    }

    /**
     * Render score_options menjadi teks preview (mis. "Kurang: 0-25, Cukup: 26-50").
     */
    public static function formatScoreOptions(array $options): string
    {
        return implode(', ', array_map(function ($opt) {
            if (is_array($opt)) {
                $label = $opt['label'] ?? '';
                $score = $opt['score'] ?? '';

                return $label !== '' ? "{$label}: {$score}" : (string) $score;
            }

            return (string) $opt;
        }, $options));
    }

    /**
     * Deteksi apakah baris pertama spreadsheet adalah baris header ("Tipe ...").
     */
    public static function isHeaderRow(array $row): bool
    {
        $first = strtolower(trim((string) ($row[self::COL_TIPE] ?? '')));

        return str_contains($first, 'tipe') || str_contains($first, 'type');
    }
}

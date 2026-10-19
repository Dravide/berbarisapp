<?php

namespace App\Support;

use App\Models\AssessmentCategory;
use App\Models\DeductionCategory;
use App\Models\Eventner;

/**
 * Membaca rubrik penilaian milik sebuah event menjadi struktur siap-salin.
 *
 * Keluarannya SENGAJA berbentuk sama dengan FormatNilaiImport::normalizeRows()
 * — `rubrik` / `pengurangan` / `errors` — supaya tabel pratinjau dan lingkar
 * tulis di Import bisa dipakai ulang apa adanya. Bedanya cuma satu: di sini
 * `rubrik_index` menunjuk indeks kategori yang benar-benar ada (bukan hasil
 * baris Excel), sehingga kelompok pengurangan tetap menempel ke kategori
 * asalnya, bukan ke kategori pertama.
 *
 * Tambahan `pengurangan_tingkat` tidak ada di jalur Excel: kelompok
 * ber-scope 'global' tidak menempel pada kategori mana pun, jadi ia tidak bisa
 * dinyatakan sebagai `rubrik_index`. Tanpanya, "Pengurangan Nilai Tingkat" milik
 * event sumber hilang diam-diam saat disalin — kegagalan yang tampak seperti
 * rubrik sumbernya memang tidak punya sanksi tingkat.
 *
 * Yang TIDAK ikut tersalin, dan memang tidak boleh: grup, seri, dan babak.
 * Ketiganya baris milik event sumber; menyalin id-nya akan menempelkan rubrik
 * baru ke babak/seri event lain — persis kegagalan yang dijaga
 * AssessmentCategory::scopeForLevel().
 */
class FormatNilaiSalin
{
    /**
     * @param  ?int  $levelId  Tingkat lomba MILIK EVENT SUMBER. null = seluruh
     *                         tingkat event itu (tanpa saringan).
     * @return array{
     *   rubrik: array<int, array{name:string,subCategories:array}>,
     *   pengurangan: array<int, array{rubrik_index:int,name:string,criterias:array}>,
     *   pengurangan_tingkat: array<int, array{name:string,criterias:array}>,
     *   errors: array<int, array{row:int,message:string}>
     * }
     */
    public static function baca(Eventner $sumber, ?int $levelId = null): array
    {
        $result = [
            'rubrik' => [],
            'pengurangan' => [],
            'pengurangan_tingkat' => [],
            'errors' => [],
        ];

        $kategori = AssessmentCategory::with([
            'subCategories.criterias',
            'deductionCategories.criterias',
        ])
            ->where('eventner_id', $sumber->id)
            ->orderBy('sort_order');

        // forLevel() — bukan where() — supaya rubrik "Semua Tingkat" milik event
        // sumber ikut terbaca saat satu tingkat dipilih. Memakai where() polos
        // membuang rubrik ber-level NULL dari salinan, dan salinannya jadi
        // diam-diam tidak lengkap dibanding yang terlihat di Builder asalnya.
        if ($levelId !== null) {
            $kategori->forLevel($levelId);
        }

        foreach ($kategori->get() as $cat) {
            $index = count($result['rubrik']);

            $result['rubrik'][] = [
                'name' => (string) $cat->name,
                'subCategories' => $cat->subCategories->map(fn ($sub) => [
                    'name' => (string) $sub->name,
                    'criterias' => $sub->criterias->map(fn ($crit) => [
                        'name' => (string) $crit->name,
                        'score_options' => $crit->score_options ?? [],
                        'weight' => (float) $crit->weight,
                    ])->values()->all(),
                ])->values()->all(),
            ];

            foreach ($cat->deductionCategories as $ded) {
                // Hanya kelompok yang memang menempel pada kategori ini. Baris
                // ber-scope 'global' dibaca di bawah, dari tingkatnya sendiri.
                if ($ded->scope !== DeductionCategory::SCOPE_CATEGORY) {
                    continue;
                }

                $result['pengurangan'][] = [
                    'rubrik_index' => $index,
                    'name' => (string) $ded->name,
                    'criterias' => $ded->criterias->map(fn ($crit) => [
                        'name' => (string) $crit->name,
                        'deduction_options' => $crit->deduction_options ?? [],
                    ])->values()->all(),
                ];
            }
        }

        // Pengurangan tingkat dibaca lintas tingkat event sumber (bukan disaring
        // diam-diam): tiap kelompok ditandai tingkat asalnya, jadi saat semuanya
        // dituang ke satu tingkat tujuan, panitia melihat dari mana asalnya
        // sebelum menekan Simpan — bukan menemukannya setelah tersimpan.
        $tingkat = DeductionCategory::with(['criterias', 'competitionCategory'])
            ->where('eventner_id', $sumber->id)
            ->global()
            ->orderBy('sort_order');

        if ($levelId !== null) {
            $tingkat->forLevel($levelId);
        }

        foreach ($tingkat->get() as $ded) {
            $result['pengurangan_tingkat'][] = [
                'name' => (string) $ded->name,
                'tingkat' => $ded->competitionCategory?->full_name,
                'criterias' => $ded->criterias->map(fn ($crit) => [
                    'name' => (string) $crit->name,
                    'deduction_options' => $crit->deduction_options ?? [],
                ])->values()->all(),
            ];
        }

        return $result;
    }

    /**
     * Ringkasan angka untuk badge pratinjau + daftar baris tabel.
     *
     * Barisnya memakai FormatNilaiImport::previewRows() — satu tabel pratinjau
     * untuk kedua jalur import, jadi operator tidak perlu belajar dua bentuk
     * tabel untuk pekerjaan yang sama.
     */
    public static function ringkas(array $normalized): array
    {
        $baris = FormatNilaiImport::previewRows($normalized);

        foreach ($normalized['pengurangan_tingkat'] ?? [] as $group) {
            foreach ($group['criterias'] as $crit) {
                $baris[] = [
                    'tipe' => 'Pengurangan',
                    'kategori' => $group['name'],
                    'sub' => $group['tingkat'] ? "→ semua kategori di tingkat {$group['tingkat']} (asal)" : '→ semua kategori di tingkat ini',
                    'kriteria' => $crit['name'],
                    'skor' => implode(', ', $crit['deduction_options']),
                    'bobot' => '',
                ];
            }
        }

        return [
            'rubrikCount' => count($normalized['rubrik']),
            'criteriaCount' => collect($normalized['rubrik'])
                ->sum(fn ($c) => collect($c['subCategories'])->sum(fn ($s) => count($s['criterias']))),
            'penguranganCount' => count($normalized['pengurangan']) + count($normalized['pengurangan_tingkat'] ?? []),
            'deductionCriteriaCount' => collect($normalized['pengurangan'])
                ->sum(fn ($g) => count($g['criterias']))
                + collect($normalized['pengurangan_tingkat'] ?? [])->sum(fn ($g) => count($g['criterias'])),
            'rows' => $baris,
        ];
    }

    /**
     * Nama kategori yang sudah ada di tingkat tujuan — dipakai pratinjau untuk
     * menandai "nama sudah ada di tingkat ini".
     *
     * Nama kembar TIDAK ditolak: panitia yang tahu mana yang harus dihapus,
     * bukan kode. Tetapi ia harus melihatnya sebelum menekan Simpan, bukan
     * sesudah.
     *
     * @return array<int, string> nama (apa adanya) yang sudah dipakai
     */
    public static function namaSudahAda(int $eventnerId, ?int $targetLevelId): array
    {
        return AssessmentCategory::where('eventner_id', $eventnerId)
            ->when($targetLevelId === null,
                fn ($q) => $q->whereNull('competition_category_id'),
                fn ($q) => $q->where('competition_category_id', $targetLevelId))
            ->pluck('name')
            ->map(fn ($n) => (string) $n)
            ->all();
    }
}

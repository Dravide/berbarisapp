<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi penugasan baru dari keadaan yang sekarang, supaya tak ada satu pun
 * perilaku penilaian yang berubah saat rilis.
 *
 * Sumbernya assessment_category_judge (juri <-> rubrik), dipetakan lewat
 * penanda yang menempel di rubriknya:
 *
 *   rubrik bergrup        -> baris `group` di grup itu
 *   rubrik berbabak final -> baris `final`
 *   rubrik tanpa keduanya -> baris `level` (tingkat tanpa grup)
 *
 * Hasilnya di LOBB: Grup A dan Grup B masing-masing tercentang keempat juri,
 * persis seperti tampilan hari ini. Panitia tinggal mencopot centang berlebih
 * di modal Kelola Grup — bukan menebak dari nol.
 *
 * Baris `ungrouped` sengaja TIDAK diisi. Mengisinya dengan seluruh juri
 * tingkat hanya menyalin kebocoran yang sama ke jalur baru; barisnya dibiarkan
 * kosong dan muncul sebagai peringatan di layar.
 *
 * Idempoten: tiap baris disaring dulu, jadi dijalankan dua kali tidak
 * menggandakan baris. MySQL dan SQLite sama-sama menganggap NULL tidak sama
 * dengan NULL di dalam unique index, jadi jangan andalkan indeksnya di sini —
 * yang menjamin adalah penyaringan eksplisit di bawah.
 *
 * Memakai query builder, bukan model: migrasi data tidak boleh bergantung pada
 * bentuk model yang akan berubah setelahnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->isi('group', fn ($q) => $q->whereNotNull('ac.competition_group_id'),
            fn ($b) => $b->competition_group_id);

        $this->isi('final',
            fn ($q) => $q->where('cr.type', 'final'),
            fn () => null, 'cr');

        $this->isi('level',
            fn ($q) => $q->whereNull('ac.competition_group_id')->whereNull('ac.competition_round_id'),
            fn () => null);
    }

    /**
     * Satu scope: tarik pasangan (tingkat, grup, juri) dari rubrik, lalu
     * simpan hanya yang belum ada.
     */
    private function isi(string $scope, callable $saring, callable $grupDari, ?string $joinBabak = null): void
    {
        $q = DB::table('assessment_category_judge as acj')
            ->join('assessment_categories as ac', 'ac.id', '=', 'acj.assessment_category_id');

        if ($joinBabak) {
            $q->join('competition_rounds as cr', 'cr.id', '=', 'ac.competition_round_id');
        }

        $baris = $saring($q)
            ->distinct()
            ->get(['ac.competition_category_id', 'ac.competition_group_id', 'acj.judge_id']);

        $now = now();

        foreach ($baris as $b) {
            $grupId = $grupDari($b);

            $ada = DB::table('competition_group_judge')
                ->where('judge_id', $b->judge_id)
                ->where('competition_category_id', $b->competition_category_id)
                ->where('scope', $scope)
                ->when($grupId === null, fn ($x) => $x->whereNull('competition_group_id'),
                    fn ($x) => $x->where('competition_group_id', $grupId))
                ->exists();

            if ($ada) {
                continue;
            }

            DB::table('competition_group_judge')->insert([
                'competition_category_id' => $b->competition_category_id,
                'competition_group_id' => $grupId,
                'judge_id' => $b->judge_id,
                'scope' => $scope,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Kosongkan penugasan baru. assessment_category_judge tidak disentuh, jadi
     * keadaan lama kembali utuh dan panel juri kembali membaca rubrik.
     */
    public function down(): void
    {
        DB::table('competition_group_judge')->delete();
    }
};

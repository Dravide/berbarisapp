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
 * menggandakan baris. Ini bukan kehati-hatian teoretis — migrasi ini pernah
 * gagal di tengah pada basis data MySQL (yang tidak punya transaksi DDL),
 * sesudah baris `group` dan `final` sempat masuk, lalu dijalankan ulang.
 * MySQL dan SQLite sama-sama menganggap NULL tidak sama dengan NULL di dalam
 * unique index, jadi jangan andalkan indeksnya di sini — yang menjamin adalah
 * penyaringan eksplisit di bawah.
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

        $this->levelUntukRubrikGlobal();
    }

    /**
     * Satu scope: tarik pasangan (tingkat, grup, juri) dari rubrik, lalu
     * simpan hanya yang belum ada.
     *
     * Rubrik global (competition_category_id NULL) disaring keluar di sini —
     * ia tak menyebut tingkat mana pun, jadi tak bisa dipetakan langsung.
     * Penanganannya di levelUntukRubrikGlobal().
     */
    private function isi(string $scope, callable $saring, callable $grupDari, ?string $joinBabak = null): void
    {
        $q = DB::table('assessment_category_judge as acj')
            ->join('assessment_categories as ac', 'ac.id', '=', 'acj.assessment_category_id')
            ->whereNotNull('ac.competition_category_id');

        if ($joinBabak) {
            $q->join('competition_rounds as cr', 'cr.id', '=', 'ac.competition_round_id');
        }

        $baris = $saring($q)
            ->distinct()
            ->get(['ac.competition_category_id', 'ac.competition_group_id', 'acj.judge_id']);

        foreach ($baris as $b) {
            $this->simpan($scope, (int) $b->competition_category_id, $grupDari($b), (int) $b->judge_id);
        }
    }

    /**
     * Baris `level` untuk rubrik GLOBAL — dan hanya untuk tingkat TANPA GRUP.
     *
     * Rubrik global tak menyebut tingkat mana pun: satu barisnya berlaku di
     * seluruh tingkat milik eventner-nya. Menyalinnya apa adanya tak mungkin,
     * karena competition_category_id di pivot ini wajib berisi — itulah yang
     * membuat migrasi ini gagal pada percobaan pertama. Jadi pasangan
     * (eventner, juri) disilangkan dengan tingkat yang memang tidak punya grup.
     *
     * Hanya tingkat tanpa grup, karena di sanalah rubrik global adalah
     * satu-satunya pengikat juri: tanpa baris `level`, panel jurinya kosong
     * begitu layar centang rubrik dihapus. Tingkat yang punya grup dilewati
     * dengan sengaja — baris `level` di sana berada di urutan terakhir
     * penugasanUntukPeserta(), jadi ia tak akan pernah dibaca, dan
     * menambahkannya hanya membuat modal Kelola Grup menampilkan centang yang
     * tak berpengaruh.
     *
     * Sisi lain dari pilihan itu, yang harus diakui: juri rubrik global di
     * tingkat YANG PUNYA GRUP tak mendapat penugasan apa pun. Memetakannya ke
     * grup mana pun adalah tebakan, dan sentuhan panitia lebih murah daripada
     * juri yang diam-diam menilai kelompok yang salah.
     */
    private function levelUntukRubrikGlobal(): void
    {
        $global = DB::table('assessment_category_judge as acj')
            ->join('assessment_categories as ac', 'ac.id', '=', 'acj.assessment_category_id')
            ->whereNull('ac.competition_category_id')
            ->distinct()
            ->get(['ac.eventner_id', 'acj.judge_id']);

        foreach ($global->groupBy('eventner_id') as $eventnerId => $juri) {
            $tingkat = DB::table('competition_categories as cc')
                ->where('cc.eventner_id', $eventnerId)
                // Sama dengan daftar tingkat di layar: anak, atau induk yang
                // memang tak beranak (bentuk tiga belas event lama).
                ->where(function ($q) {
                    $q->whereNotNull('cc.parent_id')
                        ->orWhere(function ($sq) {
                            $sq->whereNull('cc.parent_id')
                                ->whereNotExists(fn ($x) => $x->select(DB::raw(1))
                                    ->from('competition_categories as anak')
                                    ->whereColumn('anak.parent_id', 'cc.id'));
                        });
                })
                ->whereNotExists(fn ($x) => $x->select(DB::raw(1))
                    ->from('competition_groups as g')
                    ->whereColumn('g.competition_category_id', 'cc.id'))
                ->pluck('cc.id');

            foreach ($tingkat as $levelId) {
                foreach ($juri->pluck('judge_id') as $judgeId) {
                    $this->simpan('level', (int) $levelId, null, (int) $judgeId);
                }
            }
        }
    }

    /** Tulis satu baris kalau belum ada — inti sifat idempoten migrasi ini. */
    private function simpan(string $scope, int $levelId, ?int $groupId, int $judgeId): void
    {
        $ada = DB::table('competition_group_judge')
            ->where('judge_id', $judgeId)
            ->where('competition_category_id', $levelId)
            ->where('scope', $scope)
            ->when($groupId === null, fn ($x) => $x->whereNull('competition_group_id'),
                fn ($x) => $x->where('competition_group_id', $groupId))
            ->exists();

        if ($ada) {
            return;
        }

        $now = now();

        DB::table('competition_group_judge')->insert([
            'competition_category_id' => $levelId,
            'competition_group_id' => $groupId,
            'judge_id' => $judgeId,
            'scope' => $scope,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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

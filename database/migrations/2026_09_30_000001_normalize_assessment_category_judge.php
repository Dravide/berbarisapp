<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `assessment_category_judge` hidup lagi — kali ini sebagai PEMBATASAN.
 *
 * Sejarah tabel ini penting untuk memahami kenapa barisnya justru DIHAPUS:
 *
 *  - Sampai 2026-09-29 ia satu-satunya pengikat juri, dan artinya longgar:
 *    "juri ini boleh menilai rubrik ini". Tak ada satu pun layar panitia yang
 *    menulisnya — hanya seeder dan beberapa berkas tes. Pembaca yang tersisa
 *    (kartu akses, unduh PDF) memakainya untuk MENYARING, dan itulah sebabnya
 *    `JudgeScoring\Index::loadCriteria()` sengaja tidak menyaring juri sama
 *    sekali: daftar rubrik datang dari seri peserta, bukan dari pivot ini.
 *  - Mulai sekarang artinya mengikat: "rubrik ini HANYA diisi juri ini".
 *    Rubrik tanpa baris tetap terbuka untuk semua juri, jadi keadaan lama
 *    ("semua juri boleh semuanya") diwakili oleh tabel yang kosong.
 *
 * Karena itu baris lama dibuang, bukan diwariskan. Baris lama tak pernah
 * dibuat panitia, dan di bawah arti barunya ia berubah jadi pembatasan yang
 * tak seorang pun minta: acara yang sudah berjalan akan kehilangan rubrik bagi
 * sebagian jurinya tanpa satu pun pesan. Dikosongkan, tiap rubrik jatuh ke
 * cabang "belum dicentang" dan hasilnya persis sama dengan sebelum perubahan.
 *
 * Urutannya mengikat: hapus kembar dulu (pivot ini tak pernah punya unique
 * index), baru pasang indexnya. Kalau tidak, indexnya ditolak oleh data kembar.
 *
 * `down()` TIDAK memulihkan baris lama — datanya memang sengaja dibuang, dan
 * tak ada cara menebaknya kembali. Yang dibatalkan hanya indexnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->hapusKembar();

        DB::table('assessment_category_judge')->delete();

        Schema::table('assessment_category_judge', function (Blueprint $table) {
            // Aman dipasang di sini justru karena kedua kolomnya NOT NULL:
            // derita "NULL != NULL" yang memaksa CompetitionGroup::syncJudges()
            // menghapus-lalu-menulis tak berlaku pada tabel ini.
            $table->unique(['judge_id', 'assessment_category_id'], 'assessment_category_judge_unik');
        });
    }

    /**
     * Sisakan satu baris per pasangan (juri, rubrik).
     *
     * Ditulis lewat subquery MIN(id), bukan GROUP BY + DELETE JOIN, supaya
     * sama jalan di MySQL maupun SQLite (suite tes memakai yang kedua).
     */
    private function hapusKembar(): void
    {
        $simpan = DB::table('assessment_category_judge')
            ->groupBy('judge_id', 'assessment_category_id')
            ->selectRaw('MIN(id) as id')
            ->pluck('id');

        if ($simpan->isEmpty()) {
            return;
        }

        DB::table('assessment_category_judge')->whereNotIn('id', $simpan)->delete();
    }

    public function down(): void
    {
        Schema::table('assessment_category_judge', function (Blueprint $table) {
            $table->dropUnique('assessment_category_judge_unik');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengurangan ber-scope 'global' ternyata TIDAK berlaku untuk semua tingkat
     * lomba, melainkan berlaku untuk semua KATEGORI PENILAIAN di dalam SATU
     * tingkat lomba. Migrasi `add_scope_to_deduction_categories_table` menulis
     * "berlaku semua tingkat lomba" — itu keliru dan membuat sanksi satu tingkat
     * ikut memotong nilai tingkat lain.
     *
     * Kolom ini mengikat tiap kelompok global ke satu tingkat lomba. Nilainya
     * juga dipakai sebagai penyaring di semua pembaca nilai: kelompok global
     * hanya memotong peserta yang mendaftar di tingkat itu.
     */
    public function up(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->foreignId('competition_category_id')
                ->nullable()
                ->after('assessment_category_id')
                ->constrained('competition_categories')
                ->nullOnDelete();
        });

        // Backfill data lama: kelompok global yang sudah ada belum punya tingkat.
        // Hanya diisi bila tingkatnya tidak ambigu — eventner dengan tepat satu
        // tingkat. Bila lebih dari satu, dibiarkan NULL: menebak tingkat pemilik
        // sanksi berarti memotong nilai peserta yang salah, dan itu lebih buruk
        // daripada kelompok yang tidak tampil sampai panitia membuatnya ulang.
        $eventnerIds = DB::table('deduction_categories')
            ->where('scope', 'global')
            ->whereNull('competition_category_id')
            ->distinct()
            ->pluck('eventner_id');

        foreach ($eventnerIds as $eventnerId) {
            $levels = DB::table('competition_categories')
                ->where('eventner_id', $eventnerId)
                ->where(function ($q) {
                    $q->whereNotNull('parent_id')
                        ->orWhere(function ($sq) {
                            $sq->whereNull('parent_id')
                                ->whereNotExists(function ($n) {
                                    $n->selectRaw('1')
                                        ->from('competition_categories as anak')
                                        ->whereColumn('anak.parent_id', 'competition_categories.id');
                                });
                        });
                })
                ->pluck('id');

            if ($levels->count() !== 1) {
                continue;
            }

            DB::table('deduction_categories')
                ->where('eventner_id', $eventnerId)
                ->where('scope', 'global')
                ->whereNull('competition_category_id')
                ->update(['competition_category_id' => $levels->first()]);
        }
    }

    public function down(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->dropForeign(['competition_category_id']);
            $table->dropColumn('competition_category_id');
        });
    }
};

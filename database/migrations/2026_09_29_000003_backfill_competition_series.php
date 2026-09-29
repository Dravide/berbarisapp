<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi kolom seri dari grup yang sudah ada.
 *
 * Setiap grup melahirkan tepat satu seri senama, lalu rubrik dan peserta grup
 * itu ditandai dengan seri tersebut. Pasukan yang belum bergrup dan rubrik
 * yang belum bergrup dibiarkan NULL — dan NULL berarti "berlaku di mana saja",
 * jadi tidak ada satu pun nilai yang berubah artinya.
 *
 * Idempoten: dijalankan dua kali tidak menggandakan seri (unique index
 * competition_category_id + name dijaga updateOrInsert) maupun tidak menimpa
 * penanda seri yang sudah diisi panitia.
 *
 * Memakai query builder, bukan model: migrasi data tidak boleh bergantung pada
 * bentuk model yang akan berubah setelahnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Lahirkan satu seri per grup yang belum punya kembaran senama.
        DB::table('competition_groups')
            ->orderBy('id')
            ->chunkById(200, function ($groups) {
                $now = now();

                foreach ($groups as $group) {
                    DB::table('competition_series')->updateOrInsert(
                        [
                            'competition_category_id' => $group->competition_category_id,
                            'name' => $group->name,
                        ],
                        [
                            'eventner_id' => $group->eventner_id,
                            'sort_order' => $group->sort_order,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            });

        // 2. Peta grup -> seri, dari nama yang barusan disamakan.
        $seriesByGroup = DB::table('competition_groups as g')
            ->join('competition_series as s', function ($join) {
                $join->on('s.competition_category_id', '=', 'g.competition_category_id')
                    ->on('s.name', '=', 'g.name');
            })
            ->pluck('s.id', 'g.id');

        // 3. Tandai rubrik dan peserta. Hanya yang masih NULL, supaya penanda
        //    seri yang sudah diatur panitia tidak tertimpa.
        foreach ($seriesByGroup as $groupId => $seriesId) {
            DB::table('assessment_categories')
                ->where('competition_group_id', $groupId)
                ->whereNull('competition_series_id')
                ->update(['competition_series_id' => $seriesId]);

            DB::table('registrations')
                ->where('competition_group_id', $groupId)
                ->whereNull('competition_series_id')
                ->update(['competition_series_id' => $seriesId]);
        }
    }

    /**
     * Kosongkan penanda seri lalu hapus serinya. Grup tidak disentuh, jadi
     * perilaku lama (rubrik dinilai per grup) kembali utuh.
     */
    public function down(): void
    {
        DB::table('registrations')->whereNotNull('competition_series_id')
            ->update(['competition_series_id' => null]);

        DB::table('assessment_categories')->whereNotNull('competition_series_id')
            ->update(['competition_series_id' => null]);

        DB::table('competition_series')->delete();
    }
};

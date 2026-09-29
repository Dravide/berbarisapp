<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionSeries;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migrasi backfill seri wajib idempoten.
 *
 * Backfill inilah yang menjamin Fase 2 tidak mengubah arti satu pun nilai:
 * setiap rubrik & peserta berisi seri dari grupnya, yang bergrup NULL tetap
 * NULL. Kalau ia tidak idempoten, menjalankannya dua kali (mis. setelah
 * rollback sebagian, atau di dua mesin pengembang) akan menggandakan seri —
 * dan penggandaan itu memecah lembar nilai jadi dua yang tampak identik.
 *
 * Migrasi dijalankan ulang lewat berkasnya sendiri, bukan lewat artisan:
 * RefreshDatabase sudah menjalankannya sekali di DB kosong, dan yang diuji di
 * sini adalah perilakunya pada DATA yang sudah ada.
 */
class CompetitionSeriesBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function jalankanBackfill(): void
    {
        $migration = require database_path('migrations/2026_09_29_000003_backfill_competition_series.php');
        $migration->up();
    }

    private function siapkanData(): array
    {
        $user = \App\Models\User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        $groupA = CompetitionGroup::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);
        $groupB = CompetitionGroup::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);

        $rubrik = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'competition_group_id' => $groupA->id,
            'name' => 'PBB Grup A',
            'sort_order' => 1,
        ]);

        $peserta = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $level->id,
            'competition_group_id' => $groupA->id,
            'nama_sekolah' => 'SMPN 1',
        ]);

        return [$eventner, $level, $groupA, $groupB, $rubrik, $peserta];
    }

    public function test_backfill_melahirkan_satu_seri_per_grup()
    {
        [, $level, , , , ] = $this->siapkanData();

        $this->jalankanBackfill();

        $this->assertSame(
            ['Grup A', 'Grup B'],
            CompetitionSeries::where('competition_category_id', $level->id)
                ->orderBy('sort_order')->pluck('name')->all()
        );
        $this->assertSame(
            2,
            CompetitionSeries::where('competition_category_id', $level->id)->count(),
            'Backfill melahirkan seri lebih dari satu per grup.'
        );
    }

    public function test_backfill_menandai_rubrik_dan_peserta_dengan_seri_grupnya()
    {
        [, $level, $groupA, , $rubrik, $peserta] = $this->siapkanData();

        $this->jalankanBackfill();

        $seriA = CompetitionSeries::where('competition_category_id', $level->id)
            ->where('name', 'Grup A')->firstOrFail();

        $this->assertSame($seriA->id, $rubrik->fresh()->competition_series_id);
        $this->assertSame($seriA->id, $peserta->fresh()->competition_series_id);
        $this->assertSame($groupA->id, $peserta->fresh()->competition_group_id, 'Kolom grup ikut berubah.');
    }

    /** Peserta & rubrik tanpa grup tetap NULL — NULL berarti "berlaku di mana saja". */
    public function test_backfill_membiarkan_yang_tanpa_grup_tetap_null()
    {
        [, $level, , , , , ] = $this->siapkanData();

        $tanpaGrup = Registration::factory()->for(Eventner::first(), 'eventner')->create([
            'competition_category_id' => $level->id,
            'competition_group_id' => null,
            'nama_sekolah' => 'SMPN Tanpa Grup',
        ]);

        $this->jalankanBackfill();

        $this->assertNull($tanpaGrup->fresh()->competition_series_id);
    }

    /**
     * Inti tes ini: menjalankan backfill dua kali tidak menggandakan seri dan
     * tidak menimpa penanda seri yang sudah diatur panitia.
     */
    public function test_backfill_idempoten_saat_dijalankan_dua_kali()
    {
        [, $level, , , $rubrik, $peserta] = $this->siapkanData();

        $this->jalankanBackfill();
        $setelahSekali = CompetitionSeries::where('competition_category_id', $level->id)
            ->orderBy('id')->pluck('id', 'name')->all();

        $this->jalankanBackfill();
        $setelahDuaKali = CompetitionSeries::where('competition_category_id', $level->id)
            ->orderBy('id')->pluck('id', 'name')->all();

        $this->assertSame($setelahSekali, $setelahDuaKali, 'Backfill kedua menggandakan seri.');
        $this->assertSame($setelahSekali['Grup A'], $rubrik->fresh()->competition_series_id);
        $this->assertSame($setelahSekali['Grup A'], $peserta->fresh()->competition_series_id);
    }

    /** Seri yang sudah dipindah panitia tidak ditarik kembali ke seri grupnya. */
    public function test_backfill_tidak_menimpa_seri_yang_sudah_diatur()
    {
        [, $level, , , $rubrik, $peserta] = $this->siapkanData();

        $this->jalankanBackfill();

        $seriB = CompetitionSeries::where('competition_category_id', $level->id)
            ->where('name', 'Grup B')->firstOrFail();
        $rubrik->update(['competition_series_id' => $seriB->id]);
        $peserta->update(['competition_series_id' => $seriB->id]);

        $this->jalankanBackfill();

        $this->assertSame($seriB->id, $rubrik->fresh()->competition_series_id);
        $this->assertSame($seriB->id, $peserta->fresh()->competition_series_id);
    }

    /** down() mengembalikan perilaku lama: penanda seri kosong, grup utuh. */
    public function test_rollback_mengosongkan_seri_tanpa_menyentuh_grup()
    {
        [, , $groupA, , $rubrik, $peserta] = $this->siapkanData();

        $this->jalankanBackfill();
        $this->assertNotNull($peserta->fresh()->competition_series_id);

        $migration = require database_path('migrations/2026_09_29_000003_backfill_competition_series.php');
        $migration->down();

        $this->assertNull($rubrik->fresh()->competition_series_id);
        $this->assertNull($peserta->fresh()->competition_series_id);
        $this->assertSame(0, CompetitionSeries::count());
        $this->assertSame($groupA->id, $peserta->fresh()->competition_group_id);
        $this->assertSame(2, DB::table('competition_groups')->count());
    }
}

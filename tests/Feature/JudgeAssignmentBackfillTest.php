<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Backfill penugasan juri dari keadaan lama (assessment_category_judge).
 *
 * Migrasinya sendiri sudah dijalankan saat RefreshDatabase menyiapkan skema,
 * jadi yang diuji di sini adalah potongannya — dijalankan ulang di atas data
 * yang disusun tes, lewat kelas anonim dari berkas migrasinya.
 *
 * Dua hal yang dijaga, keduanya dari kegagalan nyata:
 *
 *  1. Rubrik GLOBAL (competition_category_id NULL) tak menyebut tingkat mana
 *     pun, jadi menyalinnya apa adanya menyisipkan NULL ke kolom
 *     competition_category_id yang NOT NULL. Migrasi pertama gagal persis di
 *     situ pada basis data MySQL produksi, dan karena MySQL tak punya
 *     transaksi DDL, baris `group` dan `final` sempat masuk sebelum gagal —
 *     artinya percobaan kedua harus tahan dijalankan di atas separuh hasil.
 *  2. Menjalankannya dua kali tak boleh menggandakan baris, karena unique index
 *     tak bisa mencegah kembar untuk baris ber-group_id NULL.
 */
class JudgeAssignmentBackfillTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $parent;

    private CompetitionCategory $level;

    private CompetitionCategory $levelTanpaGrup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create();

        $this->parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'Tingkat Kelas 9',
        ]);
        $this->levelTanpaGrup = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'Tingkat Kelas 8',
        ]);
    }

    /** Jalankan migrasi backfill sekali lagi, seperti `php artisan migrate` ulang. */
    private function jalankanBackfill(): void
    {
        (require database_path('migrations/2026_09_29_000006_backfill_competition_group_judge.php'))->up();
    }

    private function penugasan(): array
    {
        return DB::table('competition_group_judge')
            ->orderBy('scope')->orderBy('competition_category_id')
            ->orderBy('competition_group_id')->orderBy('judge_id')
            ->get()
            ->map(fn ($b) => [$b->scope, $b->competition_category_id, $b->competition_group_id, $b->judge_id])
            ->all();
    }

    /** Pasang juri ke satu rubrik lewat pivot lama — sumber backfill. */
    private function rubrikLama(array $atribut, array $judgeIds): AssessmentCategory
    {
        $rubrik = AssessmentCategory::create(['eventner_id' => $this->eventner->id, 'name' => 'PBB'] + $atribut);
        $rubrik->judges()->attach($judgeIds);

        return $rubrik;
    }

    public function test_rubrik_global_tidak_menggagalkan_backfill(): void
    {
        // Persis bentuk yang menjatuhkan migrasi di produksi: rubrik global
        // milik eventner yang tak punya penugasan apa pun.
        $this->rubrikLama(['competition_category_id' => null], []);

        $this->jalankanBackfill();

        $this->assertSame([], $this->penugasan());
    }

    public function test_rubrik_global_yang_ada_jurinya_jadi_baris_level_di_tingkat_tanpa_grup(): void
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Dery']);

        // Tingkat Kelas 9 punya grup, Tingkat Kelas 8 tidak. Hanya yang tidak
        // punya grup yang boleh mendapat baris `level`.
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);

        $this->rubrikLama(['competition_category_id' => null], [$juri->id]);

        $this->jalankanBackfill();

        $this->assertSame([
            ['level', $this->levelTanpaGrup->id, null, $juri->id],
        ], $this->penugasan());
    }

    public function test_rubrik_global_tidak_dilempar_ke_tingkat_yang_punya_grup(): void
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Dery']);

        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);

        $this->rubrikLama(['competition_category_id' => null], [$juri->id]);

        $this->jalankanBackfill();

        // Tingkat Kelas 8 tetap dapat barisnya; Tingkat Kelas 9 tidak — baris
        // `level` di sana tak akan pernah dibaca penugasanUntukPeserta().
        $this->assertSame(
            [$this->levelTanpaGrup->id],
            array_column($this->penugasan(), 1)
        );
    }

    public function test_rubrik_bergrup_jadi_baris_group(): void
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Dery']);
        $grup = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);

        $this->rubrikLama([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $grup->id,
        ], [$juri->id]);

        $this->jalankanBackfill();

        $this->assertSame([
            ['group', $this->level->id, $grup->id, $juri->id],
        ], $this->penugasan());
    }

    public function test_rubrik_berbabak_final_jadi_baris_final(): void
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Dery']);
        $babak = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
        ]);

        $this->rubrikLama([
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $babak->id,
        ], [$juri->id]);

        $this->jalankanBackfill();

        $this->assertSame([
            ['final', $this->level->id, null, $juri->id],
        ], $this->penugasan());
    }

    public function test_dijalankan_dua_kali_tidak_menggandakan_baris(): void
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Dery']);
        $grup = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);

        $this->rubrikLama([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $grup->id,
        ], [$juri->id]);
        $this->rubrikLama(['competition_category_id' => null], [$juri->id]);

        // Percobaan pertama berhenti di tengah, seperti di MySQL tanpa DDL
        // transaksional: baris `group` sudah masuk, baris `level` belum.
        $this->jalankanBackfill();
        $this->jalankanBackfill();

        $this->assertSame([
            ['group', $this->level->id, $grup->id, $juri->id],
            ['level', $this->levelTanpaGrup->id, null, $juri->id],
        ], $this->penugasan());
    }
}

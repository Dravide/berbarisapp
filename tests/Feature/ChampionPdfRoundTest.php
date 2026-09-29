<?php

namespace Tests\Feature;

use App\Http\Controllers\Eventner\ChampionCategoryController;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Rekap juara (PDF) pada tingkat berbabak.
 *
 * Dua kebocoran yang dijaga:
 *
 *  1. Kategori juara yang mencakup rubrik Penyisihan DAN Final menjumlahkan
 *     keduanya bila babak tidak disaring — padahal juara final ditentukan nilai
 *     final saja, dan nilai penyisihan cuma penentu siapa yang lolos.
 *  2. Babak final menilai finalis saja. Tanpa batasan itu sekolah yang tak
 *     pernah dinilai final ikut terdaftar bernilai 0, terbaca sebagai juara
 *     bernilai nol.
 *
 * PDF-nya tidak dirender di sini — yang diuji keputusan yang diserahkan ke
 * view, karena di situlah letak bug-nya (dompdf menutup viewData).
 */
class ChampionPdfRoundTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    private ChampionCategory $juara;

    private Registration $lolos;

    private Registration $gugur;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'logo_event' => null,
        ]);
        $this->actingAs($user);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Inti',
        ]);

        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        $this->final = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        // Satu kategori juara mencakup rubrik KEDUA babak — bentuk yang bikin
        // nilai penyisihan dan final tercampur kalau babak tidak disaring.
        $subPenyisihan = $this->rubrik('Rubrik Penyisihan', $this->penyisihan);
        $subFinal = $this->rubrik('Rubrik Final', $this->final);

        $this->juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $this->juara->assessmentSubCategories()->sync([$subPenyisihan->id, $subFinal->id]);

        $this->lolos = $this->peserta('SMPN 1', penyisihan: 10, final: 20);
        $this->gugur = $this->peserta('SMPN 2', penyisihan: 30, final: null);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->lolos->id,
        ]);
    }

    /** Rubrik + sub + kriteria berbobot 1, ditandai satu babak. */
    private function rubrik(string $name, ?CompetitionRound $round): AssessmentSubCategory
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $round?->id,
            'name' => $name,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
            'sort_order' => 1,
        ]);

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20], ['score' => 30]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return $sub;
    }

    private function peserta(string $sekolah, ?int $penyisihan, ?int $final): Registration
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'nama_sekolah' => $sekolah,
        ]);

        $nilai = [
            'Rubrik Penyisihan' => $penyisihan,
            'Rubrik Final' => $final,
        ];

        foreach ($nilai as $nama => $skor) {
            if ($skor === null) {
                continue;
            }

            $crit = AssessmentCriteria::whereHas('subCategory', fn ($q) => $q->where('name', 'Sub ' . $nama))
                ->firstOrFail();

            AssessmentScore::create([
                'eventner_id' => $this->eventner->id,
                'registration_id' => $reg->id,
                'assessment_criteria_id' => $crit->id,
                'score' => $skor,
            ]);
        }

        return $reg;
    }

    /** @return array<string, mixed> */
    private function pdfData(?int $roundId = null): array
    {
        $request = Request::create('/eventner/champion-categories/pdf');
        $request->query->set('competition_category_id', $this->level->id);
        if ($roundId) {
            $request->query->set('competition_round_id', $roundId);
        }

        return app(ChampionCategoryController::class)->pdfData($request);
    }

    /** Peta [nama_sekolah => total] untuk kategori juara utama. */
    private function totals(?int $roundId = null): array
    {
        $data = $this->pdfData($roundId);

        return collect($data['rankings'][$this->juara->id])
            ->mapWithKeys(fn ($row) => [$row['participant']->nama_sekolah => (int) $row['total']])
            ->all();
    }

    /** Tanpa babak: perilaku lama — seluruh kriteria dijumlahkan. */
    public function test_tanpa_babak_nilai_kedua_babak_dijumlahkan()
    {
        // Dua peserta bernilai sama, jadi urutannya tidak tetap — yang diuji jumlahnya.
        $this->assertEquals(['SMPN 1' => 30, 'SMPN 2' => 30], $this->totals());
    }

    /** Babak final: nilai final saja, dan hanya finalis yang diikutkan. */
    public function test_babak_final_hanya_nilai_final_dan_hanya_finalis()
    {
        $this->assertSame(['SMPN 1' => 20], $this->totals($this->final->id));
    }

    /** Peserta gugur bernilai penyisihan lebih tinggi tetap tidak masuk juara final. */
    public function test_peserta_bukan_finalis_tidak_muncul_di_juara_final()
    {
        $this->assertArrayNotHasKey('SMPN 2', $this->totals($this->final->id));
    }

    /** Babak penyisihan: seluruh peserta, nilai penyisihan saja. */
    public function test_babak_penyisihan_hanya_nilai_penyisihan()
    {
        $this->assertSame(['SMPN 2' => 30, 'SMPN 1' => 10], $this->totals($this->penyisihan->id));
    }

    /** Kategori juara yang rubriknya khusus babak lain tidak ikut tampil. */
    public function test_kategori_juara_babak_lain_tidak_tampil()
    {
        $khususFinal = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Final',
            'quantity' => 3,
        ]);
        $khususFinal->assessmentSubCategories()->sync(
            AssessmentSubCategory::whereHas('category', fn ($q) => $q->where('name', 'Rubrik Final'))->pluck('id')
        );

        $diPenyisihan = $this->pdfData($this->penyisihan->id);
        $this->assertSame(['Juara Umum'], $diPenyisihan['championCategories']->pluck('name')->all());

        $diFinal = $this->pdfData($this->final->id);
        $this->assertEqualsCanonicalizing(
            ['Juara Umum', 'Juara Final'],
            $diFinal['championCategories']->pluck('name')->all()
        );
    }

    /** Babak tingkat lain ditolak — rekap tidak boleh dicetak dengan rubrik tingkat lain. */
    public function test_babak_tingkat_lain_ditolak()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
        ]);
        $babakLain = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Final Tingkat Lain',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 1,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->pdfData($babakLain->id);
    }

    /** Rutenya sendiri tetap menghasilkan PDF, dengan dan tanpa babak. */
    public function test_route_tetap_menghasilkan_pdf()
    {
        $this->get(route('eventner.champion-categories.pdf', [
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $this->final->id,
        ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}

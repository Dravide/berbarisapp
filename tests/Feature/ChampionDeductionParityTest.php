<?php

namespace Tests\Feature;

use App\Http\Controllers\Eventner\ChampionCategoryController;
use App\Livewire\Eventner\ChampionCategory\Index as ChampionIndex;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Angka juara harus sama di layar admin dan di PDF rekap.
 *
 * Dulu halaman /eventner/champion-categories menampilkan nilai BRUTO,
 * sedangkan PDF rekap (dan Rekap Nilai, serta halaman juara publik)
 * menampilkan nilai BERSIH setelah pengurangan. Peserta yang kena sanksi
 * terbaca berbeda nilainya antara dua layar — dan karena pengurut pertama
 * memakai kolom yang sama, urutannya pun ikut berbeda.
 */
class ChampionDeductionParityTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private ChampionCategory $juara;

    private AssessmentCriteria $kriteria;

    private DeductionCriteria $sanksi;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $this->actingAs($user);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);

        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penilaian Umum',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub Umum',
            'sort_order' => 1,
        ]);
        $this->kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Ketepatan',
            'score_options' => [['score' => 100]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $this->juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $this->juara->assessmentSubCategories()->attach($sub->id);

        // Sanksi tingkat: tidak menempel ke rubrik, terikat tingkat lomba.
        $deductionCat = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => null,
            'competition_category_id' => $this->level->id,
            'scope' => DeductionCategory::SCOPE_GLOBAL,
            'name' => 'Sanksi Lapangan',
            'sort_order' => 1,
        ]);
        $this->sanksi = DeductionCriteria::create([
            'deduction_category_id' => $deductionCat->id,
            'name' => 'Terlambat masuk lapangan',
            'deduction_options' => [-15, -40],
            'sort_order' => 1,
        ]);
    }

    private function peserta(string $sekolah, int $skor): Registration
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'nama_sekolah' => $sekolah,
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => $skor,
        ]);

        return $reg;
    }

    private function sanksi(Registration $reg, int $amount): void
    {
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'deduction_criteria_id' => $this->sanksi->id,
            'amount' => $amount,
        ]);
    }

    /** Peta [nama_sekolah => total] dari layar admin. */
    private function totalLayar(): array
    {
        $component = Livewire::test(ChampionIndex::class)
            ->call('selectCompetitionCategory', $this->level->id);

        $rankings = $component->viewData('rankings');

        return collect($rankings->get($this->juara->id, collect()))
            ->mapWithKeys(fn ($ps) => [$ps['participant']->nama_sekolah => (int) $ps['total']])
            ->all();
    }

    /** Peta [nama_sekolah => total] dari PDF rekap — sumber yang sama, jalur lain. */
    private function totalPdf(): array
    {
        $request = Request::create('/eventner/champion-categories/pdf');
        $request->query->set('competition_category_id', $this->level->id);

        $data = app(ChampionCategoryController::class)->pdfData($request);

        return collect($data['rankings'][$this->juara->id])
            ->mapWithKeys(fn ($row) => [$row['participant']->nama_sekolah => (int) $row['total']])
            ->all();
    }

    public function test_layar_admin_mengurangi_sanksi_dari_total()
    {
        $reg = $this->peserta('SMPN Sanksi', 100);
        $this->sanksi($reg, -40);

        $this->assertSame(['SMPN Sanksi' => 60], $this->totalLayar());
    }

    public function test_layar_admin_sama_dengan_pdf_rekap()
    {
        $a = $this->peserta('SMPN Sanksi', 100);
        $this->peserta('SMPN Bersih', 90);
        $this->sanksi($a, -40);

        $this->assertSame($this->totalPdf(), $this->totalLayar());
    }

    public function test_tanda_amount_tidak_dipercaya_saat_mengurangi()
    {
        // Operator bisa menyimpan -15 maupun 15; keduanya harus mengurangi.
        $reg = $this->peserta('SMPN Sanksi', 100);
        $this->sanksi($reg, 15);

        $this->assertSame(['SMPN Sanksi' => 85], $this->totalLayar());
    }

    /**
     * Urutan ikut berubah karena pengurut pertama memakai nilai bersih:
     * peserta bernilai bruto lebih tinggi tapi kena sanksi besar harus turun.
     */
    public function test_peringkat_mengikuti_nilai_bersih()
    {
        $kenaSanksi = $this->peserta('SMPN Sanksi', 100);
        $this->peserta('SMPN Bersih', 90);
        $this->sanksi($kenaSanksi, -40);

        $component = Livewire::test(ChampionIndex::class)
            ->call('selectCompetitionCategory', $this->level->id);

        $rankings = collect($component->viewData('rankings')->get($this->juara->id, collect()));

        $this->assertSame('SMPN Bersih', $rankings[0]['participant']->nama_sekolah);
        $this->assertSame('SMPN Sanksi', $rankings[1]['participant']->nama_sekolah);
    }

    /** Tanpa pengurangan, angka tetap seperti semula. */
    public function test_tanpa_sanksi_total_tetap_bruto()
    {
        $this->peserta('SMPN Bersih', 100);

        $this->assertSame(['SMPN Bersih' => 100], $this->totalLayar());
    }
}

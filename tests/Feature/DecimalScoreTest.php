<?php

namespace Tests\Feature;

use App\Livewire\Eventner\ScoreRecap\Index as ScoreRecapIndex;
use App\Livewire\Eventner\Scoring\Index as ScoringIndex;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Services\ChampionCalculator;
use App\Support\ScoreOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Opsi nilai berpecahan ("8.2") tidak boleh dibulatkan jadi 8.
 *
 * `assessment_scores.score` bertipe varchar dan opsi nilai bebas diketik
 * panitia, jadi "8.2" tersimpan apa adanya. Setiap pembaca yang meng-cast
 * (int) membuang pecahannya diam-diam: juri mengklik tombol bernilai 8.2,
 * tetapi subtotal, rekap, peringkat juara, dan halaman hasil menampilkan
 * serta menjumlahkannya sebagai 8. Test di sini menjaga agar pembacaan nilai
 * lewat ScoreOptions::value() dan tampilannya lewat ScoreOptions::format()
 * tidak pernah kembali membulatkan.
 */
class DecimalScoreTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $lomba;

    private AssessmentCategory $formatNilai;

    private AssessmentCriteria $kriteria;

    private Judge $juri;

    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-DESIMAL',
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->lomba = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create(['name' => 'PBB Beregu']);

        $this->formatNilai = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Penilaian Umum',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $this->formatNilai->id,
            'name' => 'Sub Umum',
            'sort_order' => 1,
        ]);
        // Opsi berpecahan — inti laporan: tombol 8.2 dan 9.2.
        $this->kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Ketepatan',
            'score_options' => [['score' => '8.2'], ['score' => '9.2']],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $this->juri = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Satu',
        ]);
        $this->juri->assessmentCategories()->attach($this->formatNilai->id);

        // Panel membaca juri dari baris PENUGASAN, bukan dari rubrik yang
        // dipegangnya — tanpa baris `level` ini, judgeTotals selalu kosong.
        CompetitionGroup::syncJudges(
            $this->lomba->id,
            CompetitionGroup::SCOPE_LEVEL,
            null,
            [$this->juri->id]
        );

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Desimal',
        ]);

        $this->actingAs($this->eventner->user);
    }

    /** Juri mengklik dua opsi berpecahan pada dua kriteria. */
    private function isiDuaKriteria(): AssessmentCriteria
    {
        $kedua = AssessmentCriteria::create([
            'assessment_sub_category_id' => $this->kriteria->assessment_sub_category_id,
            'name' => 'Kerapian',
            'score_options' => [['score' => '9.2']],
            'weight' => 1,
            'sort_order' => 2,
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'judge_id' => $this->juri->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => '8.2',
        ]);
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'judge_id' => $this->juri->id,
            'assessment_criteria_id' => $kedua->id,
            'score' => '9.2',
        ]);

        return $kedua;
    }

    /** Pintu tunggal pembacaan nilai memang menerima pecahan. */
    public function test_pembaca_nilai_mempertahankan_pecahan()
    {
        $this->assertSame(8.2, ScoreOptions::value('8.2'));
        $this->assertSame(9.2, ScoreOptions::value('9.2'));
        $this->assertSame(17.4, ScoreOptions::value('8.2') + ScoreOptions::value('9.2'));
        // Angka bulat dan teks tak bernilai tetap aman.
        $this->assertSame(8.0, ScoreOptions::value(8));
        $this->assertSame(0.0, ScoreOptions::value('-'));
        $this->assertSame(0.0, ScoreOptions::value(null));
    }

    /** Tampilan nilai tidak membulatkan: 8.2 tetap "8.2", 8.0 tetap "8". */
    public function test_tampilan_nilai_tidak_membulatkan_pecahan()
    {
        $this->assertSame('8.2', ScoreOptions::format(8.2));
        $this->assertSame('17.4', ScoreOptions::format(17.4));
        $this->assertSame('8', ScoreOptions::format(8.0));
        $this->assertSame('0', ScoreOptions::format(0.0));
    }

    /**
     * Laporan aslinya: di panel Input Nilai, baris nilai juri menjumlahkan
     * 8.2 + 9.2 = 17.4 — dulu 8 + 9 = 17.
     */
    public function test_panel_input_nilai_menjumlahkan_pecahan()
    {
        $kedua = $this->isiDuaKriteria();

        Livewire::test(ScoringIndex::class, ['selectedCategoryId' => $this->lomba->id])
            ->call('selectParticipant', $this->reg->id)
            ->assertViewHas('judgeTotals', function ($judgeTotals) {
                $total = collect($judgeTotals)->firstWhere('judge.id', $this->juri->id)['total'];
                // 8.2 + 9.2 = 17.4 (dulu 17 karena tiap nilai di-cast int)
                return (float) $total === 17.4;
            });

        // Kriteria kedua ikut terbaca panel — penjaga agar tak seorang pun
        // mempersempit pengujian ke satu kriteria saja.
        $this->assertTrue($kedua->exists);
    }

    /** Rekap panitia juga menjumlahkan pecahan, bukan membulatkannya. */
    public function test_rekap_panitia_menjumlahkan_pecahan()
    {
        $this->isiDuaKriteria();

        Livewire::test(ScoreRecapIndex::class)
            ->set('selectedCategoryId', $this->lomba->id)
            ->assertViewHas('scoringData', function ($scoringData) {
                return (float) $scoringData->first()['finalScore'] === 17.4;
            });
    }

    /**
     * Halaman juara mengambil angka yang sama; kalau di sini dibulatkan,
     * peringkat juara bisa berbeda dari rekap panitia.
     */
    public function test_juara_memakai_nilai_berpecahan()
    {
        $this->isiDuaKriteria();

        $juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 1,
            'sort_order' => 1,
        ]);
        $juara->assessmentSubCategories()->attach($this->formatNilai->subCategories->first()->id);

        [, , $winners] = app(ChampionCalculator::class)->winners($juara, $this->lomba->id);

        $this->assertSame(
            17.4,
            (float) $winners[0]['total'],
            'Juara dihitung dari 8.2 + 9.2 = 17.4, bukan 8 + 9 = 17.'
        );
    }
}

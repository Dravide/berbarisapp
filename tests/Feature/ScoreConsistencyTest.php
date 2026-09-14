<?php

namespace Tests\Feature;

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
use App\Services\ChampionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Klaster "peringkat & total tidak konsisten" (temuan audit #10–#17).
 *
 * Semua halaman menentukan juara dari angka yang sama. Test di sini menjaga
 * agar tiap halaman memakai bobot kriteria, menormalkan tanda pengurangan,
 * membuang peserta bernilai nol, dan menyepakati aturan peringkat seri.
 */
class ScoreConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $lomba;
    private AssessmentCategory $formatNilai;
    private AssessmentCriteria $kriteria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-KONSISTEN',
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
        // Bobot 2 — inti temuan #12: bobot nyata bukan default.
        $this->kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Ketepatan',
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 2,
            'sort_order' => 1,
        ]);
    }

    private function skor(Registration $reg, int $nilai): AssessmentScore
    {
        return AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => $nilai,
        ]);
    }

    /** Pengurangan dengan tanda apa pun harus punya magnitude positif. */
    public function test_magnitude_pengurangan_selalu_positif()
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
        ]);
        $kriteriaPotong = DeductionCriteria::create([
            'deduction_category_id' => DeductionCategory::create([
                'eventner_id' => $this->eventner->id,
                'name' => 'Pelanggaran',
                'sort_order' => 1,
            ])->id,
            'name' => 'Terlambat',
            'deduction_options' => [['amount' => 5, 'label' => 'Terlambat']],
            'sort_order' => 1,
        ]);

        $positif = ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'deduction_criteria_id' => $kriteriaPotong->id,
            'amount' => 5,
        ]);
        $negatif = ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'deduction_criteria_id' => DeductionCriteria::create([
                'deduction_category_id' => $kriteriaPotong->deduction_category_id,
                'name' => 'Lain',
                'deduction_options' => [['amount' => 7, 'label' => 'Lain']],
                'sort_order' => 2,
            ])->id,
            'amount' => -7,
        ]);

        $this->assertSame(5.0, $positif->magnitude);
        $this->assertSame(7.0, $negatif->magnitude);
    }

    /**
     * #12 — rekap panitia mengalikan bobot. Kalau bobot diabaikan, nilai
     * tampil separuh dari yang dipakai menentukan juara.
     */
    public function test_rekap_panitia_mengalikan_bobot_kriteria()
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Bobot',
        ]);
        $this->skor($reg, 20);

        $user = $this->eventner->user;
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->set('selectedCategoryId', $this->lomba->id)
            ->assertViewHas('scoringData', function ($scoringData) {
                // 20 × bobot 2 = 40
                return (int) $scoringData->first()['finalScore'] === 40;
            });
    }

    /**
     * #13 — papan skor publik ikut mengurangi potongan, seperti rekap panitia.
     * Dulu papan ini hanya menjumlah nilai, jadi peringkatnya bisa berbeda.
     */
    public function test_papan_skor_publik_mengurangi_potongan()
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Potong',
        ]);
        $this->skor($reg, 20);

        $kategoriPotong = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => $this->formatNilai->id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'deduction_criteria_id' => DeductionCriteria::create([
                'deduction_category_id' => $kategoriPotong->id,
                'name' => 'Terlambat',
                'deduction_options' => [['amount' => 15, 'label' => 'Terlambat']],
                'sort_order' => 1,
            ])->id,
            'amount' => 15,
        ]);

        Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-KONSISTEN',
        ])->assertViewHas('rankings', function ($rankings) {
            // 20 × 2 = 40, dikurangi 15 = 25
            return (int) collect($rankings)->first()['total'] === 25;
        });
    }

    /**
     * #16 — aturan peringkat seri sama di semua halaman: nilai sama berarti
     * peringkat sama, dan peringkat berikutnya melompat.
     */
    public function test_rekap_panitia_memberi_peringkat_sama_untuk_nilai_seri()
    {
        $seriA = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Seri A',
        ]);
        $seriB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Seri B',
        ]);
        $bawah = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Bawah',
        ]);

        $this->skor($seriA, 20);
        $this->skor($seriB, 20);
        $this->skor($bawah, 10);

        $this->actingAs($this->eventner->user);

        Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->set('selectedCategoryId', $this->lomba->id)
            ->assertViewHas('scoringData', function ($scoringData) {
                $peringkat = $scoringData->pluck('rank', 'participant.nama_sekolah');

                // Dua teratas seri di peringkat 1, yang ketiga melompat ke 3.
                return $peringkat['SMP Seri A'] === 1
                    && $peringkat['SMP Seri B'] === 1
                    && $peringkat['SMP Bawah'] === 3;
            });
    }

    /**
     * #10 & #14 — peserta tanpa nilai bukan juara. ChampionCalculator dan
     * halaman sertifikat harus sepakat soal ini.
     */
    public function test_peserta_tanpa_nilai_bukan_juara()
    {
        $bernilai = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Bernilai',
        ]);
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Kosong',
        ]);
        $this->skor($bernilai, 20);

        $juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Umum',
            'quantity' => 3,
            'sort_order' => 1,
        ]);
        // Rubrik juara menempel pada sub kategori penilaian — tanpa ini
        // tidak ada kriteria yang dihitung dan semua total nol.
        $juara->assessmentSubCategories()->attach($this->kriteria->assessment_sub_category_id);

        [, , $winners] = app(ChampionCalculator::class)->winners($juara, $this->lomba->id);

        $namaJuara = collect($winners)->pluck('registration.nama_sekolah');

        $this->assertContains('SMP Bernilai', $namaJuara);
        $this->assertNotContains('SMP Kosong', $namaJuara);
    }

    /**
     * #11 — tanda pengurangan tidak dipercaya.
     *
     * Pengurangan dipakai sebagai pemecah seri, dan di situ tanda yang
     * tersimpan menentukan urutan: nilai mentah "-5" lebih kecil dari "0"
     * sehingga peserta yang dikurangi justru naik. Yang benar, besar
     * pengurangan yang dibandingkan — bukan tandanya.
     */
    public function test_tanda_potongan_tidak_membalik_urutan_juara()
    {
        $tanpa = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Tanpa Potongan',
        ]);
        $positif = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Plus',
        ]);
        $negatif = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Minus',
        ]);

        // Nilai sama semua — yang membedakan hanya pengurangannya.
        $this->skor($tanpa, 20);
        $this->skor($positif, 20);
        $this->skor($negatif, 20);

        $kategoriPotong = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        $kriteria = DeductionCriteria::create([
            'deduction_category_id' => $kategoriPotong->id,
            'name' => 'Terlambat',
            'deduction_options' => [['amount' => 5, 'label' => 'Terlambat']],
            'sort_order' => 1,
        ]);

        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $positif->id,
            'deduction_criteria_id' => $kriteria->id,
            'amount' => 5, // operator mengetik "5"
        ]);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $negatif->id,
            'deduction_criteria_id' => $kriteria->id,
            'amount' => -5, // operator mengetik "-5"
        ]);

        $juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Umum',
            'quantity' => 3,
            'sort_order' => 1,
        ]);
        $juara->assessmentSubCategories()->attach($this->kriteria->assessment_sub_category_id);

        [, , $winners] = app(ChampionCalculator::class)->winners($juara, $this->lomba->id);

        $nama = collect($winners)->pluck('registration.nama_sekolah')->all();

        // Yang tidak dikurangi di puncak; dua sisanya seri di bawahnya.
        $this->assertSame('SMP Tanpa Potongan', $nama[0]);
        $this->assertContains('SMP Plus', $nama);
        $this->assertContains('SMP Minus', $nama);
        $this->assertSame(1, $winners[0]['rank']);
        $this->assertSame(2, $winners[1]['rank']);
        $this->assertSame(2, $winners[2]['rank']);
    }
}

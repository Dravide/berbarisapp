<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use App\Services\ScoreRecapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rekap keseluruhan (PDF) satu tingkat lomba.
 *
 * Dua hal yang dijaga di sini:
 *
 *  1. Isinya sama dengan tabel di layar Rekap Nilai — keduanya membaca
 *     ScoreRecapBuilder, dan itulah gunanya dipindahkan dari komponen Livewire:
 *     salinan kedua dari rumus peringkat/nilai akhir pasti berbeda begitu salah
 *     satunya diperbaiki.
 *  2. Peringkat dihitung DI DALAM bagiannya. Peserta Grup A bukan pembanding
 *     peserta Grup B, dan baris penyisihan bukan pembanding baris final —
 *     inilah cacat yang dulu ada di tombol "Download CSV" yang digantikan
 *     berkas ini: nilai penyisihan dan final dijumlahkan jadi satu peringkat.
 */
class ScoreRecapPdfTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    private Registration $regA;

    private Registration $regB;

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
            'name' => 'PBB Beregu',
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);
        $this->groupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
        ]);

        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penyisihan',
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

        $this->rubrik('Rubrik Penyisihan', $this->penyisihan);
        $this->rubrik('Rubrik Final', $this->final);

        $this->regA = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'nama_sekolah' => 'SMPN 1',
        ]);
        $this->regB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupB->id,
            'nama_sekolah' => 'SMPN 2',
        ]);
    }

    /** Rubrik satu kriteria untuk satu babak, tanpa seri. */
    private function rubrik(string $name, CompetitionRound $round): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $round->id,
            'name' => $name,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
        ]);

        return AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function nilai(Registration $reg, AssessmentCriteria $crit, string $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'judge_id' => null,
            'assessment_criteria_id' => $crit->id,
            'score' => $score,
        ]);
    }

    /** Baris satu bagian, dicari lewat nama kontingennya. */
    private function baris(array $section, string $nama): array
    {
        foreach ($section['data'] as $row) {
            if ($row['participant']->display_name === $nama) {
                return $row;
            }
        }

        $this->fail("Baris {$nama} tidak ada di bagian {$section['label']}.");
    }

    /** Semua kriteria satu babak, diambil dari rubriknya. */
    private function kriteriaBabak(CompetitionRound $round): array
    {
        return AssessmentCriteria::whereHas('subCategory.category', fn ($q) => $q
                ->where('eventner_id', $this->eventner->id)
                ->where('competition_round_id', $round->id))
            ->get()
            ->all();
    }

    private function rekap(): array
    {
        return (new ScoreRecapBuilder)->build($this->eventner, (int) $this->level->id);
    }

    /**
     * Peringkat dihitung per bagian, bukan sekali untuk semua grup.
     *
     * SMPN 2 yang nilainya lebih rendah tampil peringkat 1 di Grup B — kalau
     * peringkatnya dihitung lintas grup, barisnya akan tertulis peringkat 2
     * seumur hidup padahal ia juara bagiannya.
     */
    public function test_peringkat_dihitung_per_grup()
    {
        [$penyisihan] = $this->kriteriaBabak($this->penyisihan);

        $this->nilai($this->regA, $penyisihan, '20');
        $this->nilai($this->regB, $penyisihan, '10');

        $sections = collect($this->rekap()['sections'])
            ->flatMap(fn ($s) => $s['groups'] ?? [$s])
            ->keyBy('label');

        $this->assertSame(1, $this->baris($sections['Grup A'], 'SMPN 1')['rank']);
        $this->assertSame(1, $this->baris($sections['Grup B'], 'SMPN 2')['rank']);
        $this->assertSame(20.0, (float) $this->baris($sections['Grup A'], 'SMPN 1')['finalScore']);
        $this->assertSame(10.0, (float) $this->baris($sections['Grup B'], 'SMPN 2')['finalScore']);
    }

    /**
     * Nilai penyisihan dan final tidak pernah terjumlah jadi satu baris.
     *
     * Inilah cacat tombol lama: satu peringkat gabungan membuat peserta yang
     * dinilai baik di penyisihan lalu biasa saja di final tetap di puncak, dan
     * angka peringkatnya tak pernah dinilai siapa pun.
     */
    public function test_nilai_penyisihan_dan_final_tidak_terjumlah()
    {
        [$penyisihan] = $this->kriteriaBabak($this->penyisihan);
        [$final] = $this->kriteriaBabak($this->final);

        $this->nilai($this->regA, $penyisihan, '20');
        $this->nilai($this->regA, $final, '10');

        // Loloskan SMPN 1 ke final.
        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->regA->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $sections = $this->rekap()['sections'];
        $this->assertCount(2, $sections, 'Satu bagian per babak.');

        $penyisihanSection = collect($sections[0]['groups'])->firstWhere('label', 'Grup A');
        $finalSection = collect($sections[1]['groups'])->firstWhere('label', 'Grup A');

        $this->assertSame(20.0, (float) $this->baris($penyisihanSection, 'SMPN 1')['finalScore']);
        $this->assertSame(10.0, (float) $this->baris($finalSection, 'SMPN 1')['finalScore']);
    }

    /** Babak final hanya memuat finalisnya. */
    public function test_babak_final_hanya_memuat_finalis()
    {
        [$penyisihan] = $this->kriteriaBabak($this->penyisihan);
        [$final] = $this->kriteriaBabak($this->final);

        $this->nilai($this->regA, $penyisihan, '20');
        $this->nilai($this->regB, $penyisihan, '10');
        $this->nilai($this->regA, $final, '20');

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->regA->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $finalSection = collect($this->rekap()['sections'][1]['groups'])
            ->firstWhere('label', 'Grup A');

        $nama = collect($finalSection['data'])->map(fn ($r) => $r['participant']->display_name)->all();
        $this->assertSame(['SMPN 1'], $nama);
    }

    /** Rute mengembalikan berkas PDF, bukan halaman HTML. */
    public function test_rute_menghasilkan_pdf()
    {
        [$penyisihan] = $this->kriteriaBabak($this->penyisihan);
        $this->nilai($this->regA, $penyisihan, '20');

        $res = $this->get(route('eventner.score-recap.pdf', ['category_id' => $this->level->id]));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    /** Tingkat milik tenant lain tidak bisa direkap. */
    public function test_tingkat_tenant_lain_ditolak()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => Eventner::factory()->create(['status' => 'approved'])->id,
            'parent_id' => null,
        ]);

        $this->get(route('eventner.score-recap.pdf', ['category_id' => $lain->id]))
            ->assertNotFound();
    }

    /** Kategori wajib disebut — tanpa itu tak ada yang bisa direkap. */
    public function test_kategori_wajib_disebut()
    {
        $this->get(route('eventner.score-recap.pdf'))->assertStatus(400);
    }
}

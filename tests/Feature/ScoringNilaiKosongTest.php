<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Scoring\Index as ScoringIndex;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lencana "N nilai masih kosong" di samping tombol PDF.
 *
 * Angka di lencana itu dipakai panitia memutuskan "sudah boleh difinalisasi
 * atau belum", jadi ia wajib memakai aturan yang sama dengan yang menuntut
 * kelengkapan saat finalisasi — bukan aturan lain yang kebetulan mirip.
 *
 * Yang paling mudah salah di sini adalah milik siapa kriteria kosong itu.
 * Sejak rubrik bisa dibagi antar juri, kolom juri lain tercetak oranye "-" di
 * lembar peserta padahal kriteria itu memang bukan tugasnya; menghitung kolom
 * seperti itu sebagai kosong membuat lencana menuduh juri atas kriteria yang
 * tak pernah boleh ia isi.
 */
class ScoringNilaiKosongTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $group;

    private CompetitionRound $penyisihan;

    private Judge $juriA;

    private Judge $juriB;

    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);

        $this->group = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);

        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        $this->juriA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri A']);
        $this->juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri B']);

        // Keduanya dari baris penugasan yang sama; pemisahannya murni dari
        // centangan rubrik, persis seperti RubricJudgeSplitTest.
        CompetitionGroup::syncJudges(
            $this->level->id,
            CompetitionGroup::SCOPE_GROUP,
            $this->group->id,
            [$this->juriA->id, $this->juriB->id],
        );

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->group->id,
            'nama_sekolah' => 'SMPN 1',
        ]);

        $this->actingAs($this->eventner->user);
    }

    /** Rubrik satu kriteria untuk satu babak, dicentang ke $pengisi. */
    private function makeRubrik(string $nama, Judge $pengisi, ?CompetitionRound $round = null): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => ($round ?? $this->penyisihan)->id,
            'name' => $nama,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $nama,
        ]);

        $criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $nama,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
        ]);

        $category->syncRubricJudges([$pengisi->id]);

        return $criteria;
    }

    /** Baris nilai milik satu juri dengan isi apa adanya (kosong pun boleh). */
    private function isiNilai(AssessmentCriteria $criteria, Judge $juri, ?string $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'judge_id' => $juri->id,
            'assessment_criteria_id' => $criteria->id,
            'score' => $score,
        ]);
    }

    /** Panel Input Nilai dengan peserta & babak penyisihan sudah terbuka. */
    private function panel(?CompetitionRound $round = null)
    {
        return Livewire::test(ScoringIndex::class, ['selectedCategoryId' => $this->level->id])
            ->set('selectedRoundId', ($round ?? $this->penyisihan)->id)
            ->call('selectParticipant', $this->reg->id);
    }

    /** Kriteria kosong dihitung per juri, bukan disatukan seluruh babak. */
    public function test_kriteria_kosong_dihitung_per_juri()
    {
        $kriteriaA = $this->makeRubrik('A', $this->juriA);
        $this->makeRubrik('B', $this->juriB);

        $this->isiNilai($kriteriaA, $this->juriA, '20');
        // Juri B belum mengisi apa pun.

        $this->panel()->assertViewHas('nilaiKosong', function ($nilaiKosong) {
            $juri = collect($nilaiKosong['judges']);

            return $nilaiKosong['total'] === 1
                && $juri->pluck('judge.id')->all() === [$this->juriB->id]
                && $juri['0']['criteria'] === ['Kriteria B'];
        });
    }

    /** Lencana dan modalnya benar-benar tercetak di samping tombol PDF. */
    public function test_lencana_dan_modal_tercetak_di_samping_tombol_pdf()
    {
        $this->makeRubrik('A', $this->juriA);
        $this->makeRubrik('B', $this->juriB);

        $this->panel()
            ->assertSee('nilai masih kosong')
            ->assertSee('data-bs-target="#nilaiKosongModal"', false)
            ->assertSee('id="nilaiKosongModal"', false)
            ->assertSee('Kriteria B');
    }

    /** Tanpa nilai kosong, tak ada lencana maupun modal yang menganggur. */
    public function test_tak_ada_lencana_dan_modal_saat_semua_terisi()
    {
        $kriteriaA = $this->makeRubrik('A', $this->juriA);
        $kriteriaB = $this->makeRubrik('B', $this->juriB);
        $this->isiNilai($kriteriaA, $this->juriA, '20');
        $this->isiNilai($kriteriaB, $this->juriB, '10');

        $this->panel()
            ->assertDontSee('nilai masih kosong')
            ->assertDontSee('id="nilaiKosongModal"', false);
    }

    /**
     * Penjaga regresi: rubrik milik juri lain TIDAK ikut dihitung kosong.
     *
     * Nilai juri A lengkap seluruhnya, sedangkan rubrik juri B belum disentuh
     * siapa pun. Tanpa saringan "boleh dinilai juri ini", kriteria juri B yang
     * memang bukan tugas juri A akan masuk daftar kosongnya.
     */
    public function test_kriteria_milik_juri_lain_tidak_dihitung_kosong()
    {
        $kriteriaA = $this->makeRubrik('A', $this->juriA);
        $kriteriaA2 = $this->makeRubrik('A2', $this->juriA);
        $this->makeRubrik('B', $this->juriB);

        $this->isiNilai($kriteriaA, $this->juriA, '20');
        $this->isiNilai($kriteriaA2, $this->juriA, '10');

        $this->panel()->assertViewHas('nilaiKosong', function ($nilaiKosong) {
            $ids = collect($nilaiKosong['judges'])->pluck('judge.id')->all();

            // Juri A tak muncul sama sekali; yang tersisa hanya juri B.
            return $nilaiKosong['total'] === 1
                && $ids === [$this->juriB->id];
        });
    }

    /** Semua terisi = tak ada lencana. */
    public function test_tak_ada_lencana_saat_semua_kriteria_terisi()
    {
        $kriteriaA = $this->makeRubrik('A', $this->juriA);
        $kriteriaB = $this->makeRubrik('B', $this->juriB);
        $this->isiNilai($kriteriaA, $this->juriA, '20');
        $this->isiNilai($kriteriaB, $this->juriB, '10');

        $this->panel()->assertViewHas('nilaiKosong', fn ($nilaiKosong) => $nilaiKosong['total'] === 0
            && $nilaiKosong['judges'] === []);
    }

    /**
     * Baris nilai yang ada tapi isinya kosong tetap terhitung belum dinilai.
     *
     * Aturan finalize() sama: baris berscore '' tak bisa difinalisasi, jadi
     * melaporkannya sebagai "sudah diisi" cuma menunda kegagalan ke tombol kunci.
     */
    public function test_baris_nilai_kosong_tetap_dihitung_kosong()
    {
        // Dua kriteria sama-sama milik juri A: satu berbaris kosong, satu belum
        // berbaris sama sekali.
        $kriteriaA = $this->makeRubrik('A', $this->juriA);
        $this->makeRubrik('B', $this->juriA);

        $this->isiNilai($kriteriaA, $this->juriA, '');

        $this->panel()->assertViewHas('nilaiKosong', function ($nilaiKosong) {
            $juriA = collect($nilaiKosong['judges'])->firstWhere('judge.id', $this->juriA->id);

            return $nilaiKosong['total'] === 2
                && $juriA !== null
                && $juriA['criteria'] === ['Kriteria A', 'Kriteria B'];
        });
    }

    /**
     * Hitungannya mengikuti babak yang sedang dibuka.
     *
     * Nilai final yang sudah lengkap tidak boleh membuat lencana padam saat
     * operator membuka babak penyisihan — dan sebaliknya.
     */
    public function test_hitungan_mengikuti_babak_terpilih()
    {
        $final = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        $kriteriaPenyisihan = $this->makeRubrik('Penyisihan', $this->juriA);
        $kriteriaFinal = $this->makeRubrik('Final', $this->juriA, $final);

        // Babak penyisihan belum diisi; babak final sudah.
        $this->isiNilai($kriteriaPenyisihan, $this->juriA, '10');
        $this->isiNilai($kriteriaFinal, $this->juriA, '20');

        // Penyisihan: semua terisi.
        $this->panel()->assertViewHas('nilaiKosong', fn ($n) => $n['total'] === 0);

        // Babak final juga terisi — yang menentukan bukan babak terakhir diisi,
        // melainkan babak yang sedang dibuka.
        $this->panel($final)->assertViewHas('nilaiKosong', fn ($n) => $n['total'] === 0);

        // Kosongkan satu baris final: lencana menyala HANYA di babak final.
        AssessmentScore::where('assessment_criteria_id', $kriteriaFinal->id)
            ->where('judge_id', $this->juriA->id)
            ->delete();

        $this->panel()->assertViewHas('nilaiKosong', fn ($n) => $n['total'] === 0);
        $this->panel($final)->assertViewHas('nilaiKosong', fn ($n) => $n['total'] === 1);
    }
}

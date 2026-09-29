<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Buka kunci nilai oleh panitia + jejak auditnya.
 *
 * Sebelum ini tak ada jalan sah melepas `is_finalized`: panitia yang salah
 * menekan "Finalisasi Semua" hanya bisa memperbaikinya lewat UPDATE langsung
 * ke database, dan justru di situ tak ada satu pun catatan siapa/kapan/kenapa.
 *
 * Yang paling mudah salah di fitur ini bukan tombolnya, melainkan daftar
 * kriteria yang dibuka. finalize() mengunci memakai AssessmentCategory::
 * forEntry(), yang lewat scopeForLevel() IKUT menyertakan rubrik tanpa babak;
 * sedangkan roundCriteriaIds() milik panel hanya menyaring babak secara tepat.
 * Membuka kunci dengan daftar yang lebih sempit meninggalkan baris terkunci,
 * sehingga tombolnya tampak tidak bekerja tanpa pesan apa pun. Tes
 * `buka_kunci_ikut_membuka_rubrik_tanpa_babak` yang menjaga itu.
 */
class ScoreUnlockTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $lomba;

    private Judge $juri;

    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create(['status' => 'approved']);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->lomba = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create(['name' => 'PBB Beregu']);

        $this->juri = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Satu',
        ]);

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Buka Kunci',
        ]);

        $this->actingAs($this->eventner->user);
    }

    /**
     * Rubrik + satu kriteria, terpasang ke juri. Mengembalikan kriterianya.
     *
     * Pemasangan juri kini menempel di GRUP, bukan di rubriknya — pivot
     * assessment_category_judge tinggal jejak audit. Panel menentukan juri mana
     * yang boleh dibuka dari baris penugasan itu, jadi tanpa syncJudges() di
     * bawah, daftar jurinya kosong dan tombol Buka Kunci tak pernah muncul.
     *
     * Peserta di berkas ini tak punya grup, jadi barisnya `ungrouped`; $grup
     * dipakai kalau kelak ada peserta bergrup.
     *
     * @param  CompetitionRound|null  $babak  null = rubrik berlaku semua babak.
     */
    private function rubrik(string $name, ?CompetitionRound $babak = null, ?CompetitionGroup $grup = null): AssessmentCriteria
    {
        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'competition_round_id' => $babak?->id,
            'competition_group_id' => $grup?->id,
            'name' => $name,
            'sort_order' => 1,
        ]);
        $this->juri->assessmentCategories()->attach($cat->id);

        CompetitionGroup::syncJudges(
            $this->lomba->id,
            $grup ? CompetitionGroup::SCOPE_GROUP : CompetitionGroup::SCOPE_UNGROUPED,
            $grup?->id,
            [$this->juri->id],
        );

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Sub ' . $name,
            'sort_order' => 1,
        ]);

        return AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function babak(string $name, string $type = CompetitionRound::TYPE_PRELIMINARY): CompetitionRound
    {
        return CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => $name,
            'type' => $type,
            'sort_order' => 1,
        ]);
    }

    /** Kunci nilai juri ini untuk daftar kriteria tertentu. */
    private function kunci(array $criteriaIds, ?int $judgeId = null): void
    {
        foreach ($criteriaIds as $criteriaId) {
            AssessmentScore::create([
                'eventner_id' => $this->eventner->id,
                'judge_id' => $judgeId ?? $this->juri->id,
                'registration_id' => $this->reg->id,
                'assessment_criteria_id' => $criteriaId,
                'score' => 10,
                'is_finalized' => true,
            ]);
        }
    }

    /** Berapa baris nilai juri ini yang masih terkunci. */
    private function terkunci(?int $judgeId = null): int
    {
        return AssessmentScore::where('registration_id', $this->reg->id)
            ->where('judge_id', $judgeId ?? $this->juri->id)
            ->where('is_finalized', true)
            ->count();
    }

    private function panel()
    {
        return Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id);
    }

    public function test_buka_kunci_menolak_tanpa_alasan()
    {
        $kriteria = $this->rubrik('Penilaian Umum');
        $this->kunci([$kriteria->id]);

        $this->panel()
            ->call('openUnlockModal')
            ->assertSet('showUnlockModal', true)
            ->call('unlockScores')
            ->assertHasErrors('unlockReason');

        // Modal tetap terbuka supaya panitia bisa memperbaiki, bukan menutup
        // dan membuang apa yang barusan diketik.
        $this->assertSame(1, $this->terkunci());
    }

    public function test_buka_kunci_membuka_nilai_juri_itu_saja()
    {
        $kriteria = $this->rubrik('Penilaian Umum');
        $juriLain = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Dua',
        ]);
        // Juri lain hanya boleh muncul sebagai pilihan kalau ia memang ditugaskan
        // ke baris penugasan peserta ini — kalau grup ini, tak ada pemilihnya.
        $grupLain = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Grup Lain',
        ]);
        $juriLain->assessmentCategories()->attach($kriteria->subCategory->assessment_category_id);
        CompetitionGroup::syncJudges($this->lomba->id, CompetitionGroup::SCOPE_GROUP, $grupLain->id, [$juriLain->id]);

        $this->kunci([$kriteria->id]);
        $this->kunci([$kriteria->id], $juriLain->id);

        $this->panel()
            ->call('openUnlockModal')
            ->set('unlockReason', 'Salah juri saat finalisasi')
            ->call('unlockScores')
            ->assertSet('showUnlockModal', false);

        $this->assertSame(0, $this->terkunci(), 'Nilai juri terpilih belum dibuka.');
        $this->assertSame(1, $this->terkunci($juriLain->id), 'Nilai juri lain ikut terbuka.');
    }
    /**
     * Rubrik tanpa babak ikut terkunci finalize(), jadi wajib ikut terbuka.
     *
     * Tingkat ini punya satu rubrik ber-babak dan satu rubrik tanpa babak.
     * Kalau pembukaan memakai daftar kriteria milik panel (roundCriteriaIds()
     * menyaring competition_round_id = X secara tepat), baris rubrik tanpa
     * babak tetap terkunci — tombol Buka Kunci tampak tidak bekerja.
     */
    public function test_buka_kunci_ikut_membuka_rubrik_tanpa_babak()
    {
        $penyisihan = $this->babak('Penyisihan');
        $kriteriaBabak = $this->rubrik('Rubrik Penyisihan', $penyisihan);
        $kriteriaUmum = $this->rubrik('Rubrik Umum');

        $this->kunci([$kriteriaBabak->id, $kriteriaUmum->id]);

        $this->panel()
            ->call('selectScope', 'preliminary')
            ->call('openUnlockModal')
            ->set('unlockReason', 'Nilai tertukar dengan regu lain')
            ->call('unlockScores');

        $this->assertSame(0, $this->terkunci(), 'Rubrik tanpa babak tertinggal terkunci.');
        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $kriteriaUmum->id,
            'is_finalized' => false,
        ]);
    }

    public function test_buka_kunci_tidak_menyentuh_babak_lain()
    {
        $penyisihan = $this->babak('Penyisihan');
        $final = $this->babak('Final', CompetitionRound::TYPE_FINAL);

        $kriteriaPenyisihan = $this->rubrik('Rubrik Penyisihan', $penyisihan);
        $kriteriaFinal = $this->rubrik('Rubrik Final', $final);

        $this->kunci([$kriteriaPenyisihan->id]);
        $this->kunci([$kriteriaFinal->id]);

        $this->panel()
            ->call('selectScope', 'preliminary')
            ->call('openUnlockModal')
            ->set('unlockReason', 'Perbaikan nilai penyisihan')
            ->call('unlockScores');

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $kriteriaPenyisihan->id,
            'is_finalized' => false,
        ]);
        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $kriteriaFinal->id,
            'is_finalized' => true,
        ]);
    }

    public function test_buka_kunci_tercatat_di_activity_log()
    {
        $kriteria = $this->rubrik('Penilaian Umum');
        $this->kunci([$kriteria->id]);

        $this->panel()
            ->call('openUnlockModal')
            ->set('unlockReason', 'Salah juri saat finalisasi')
            ->call('unlockScores');

        $log = Activity::where('log_name', 'penilaian')->get();

        $this->assertCount(1, $log, 'Jejak audit tidak tercatat tepat sekali.');

        $baris = $log->first();
        // Subject wajib Registration: halaman Activity Log menyaring
        // subject_type ke tujuh model, dan AssessmentScore bukan salah satunya.
        $this->assertSame(Registration::class, $baris->subject_type);
        $this->assertSame($this->reg->id, $baris->subject_id);
        $this->assertSame($this->eventner->user_id, $baris->causer_id);
        $this->assertSame('Salah juri saat finalisasi', $baris->properties['alasan']);
        $this->assertSame(1, $baris->properties['jumlah_kriteria']);
        $this->assertSame($this->juri->id, $baris->properties['judge_id']);
    }

    public function test_buka_kunci_tanpa_nilai_terkunci_tidak_menulis_log()
    {
        $this->rubrik('Penilaian Umum');

        // Nilai ada, tapi belum dikunci.
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'judge_id' => $this->juri->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->rubrik('Penilaian Lain')->id,
            'score' => 10,
            'is_finalized' => false,
        ]);

        $this->panel()
            ->call('openUnlockModal')
            ->assertSet('showUnlockModal', false)
            ->assertDispatched('toast');

        // Fixture membuat model ber-LogsActivity, jadi tabel activity tidak
        // kosong sejak awal. Yang dijaga di sini: aksi tanpa hasil tak menulis
        // satu pun baris di log penilaian.
        $this->assertSame(0, Activity::where('log_name', 'penilaian')->count(), 'Aksi tanpa hasil menulis jejak palsu.');
    }

    public function test_buka_kunci_registrasi_tenant_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $regLain = Registration::factory()->for($lain, 'eventner')->create();

        $kriteriaLain = $this->rubrik('Rubrik Tenant Lain');
        // Nilai terkunci milik tenant lain, pada registrasi tenant lain.
        AssessmentScore::create([
            'eventner_id' => $lain->id,
            'judge_id' => $this->juri->id,
            'registration_id' => $regLain->id,
            'assessment_criteria_id' => $kriteriaLain->id,
            'score' => 10,
            'is_finalized' => true,
        ]);

        // selectParticipant() mencari dengan where('eventner_id', ...), jadi id
        // registrasi tenant lain ditolak sebelum panel sempat dibuka.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
                ->call('selectCategory', $this->lomba->id)
                ->call('selectParticipant', $regLain->id);
        } finally {
            // Kunci tenant lain tak tersentuh, dan tak ada atribusi lintas tenant.
            $this->assertSame(1, AssessmentScore::where('eventner_id', $lain->id)
                ->where('is_finalized', true)->count());
            $this->assertSame(0, Activity::where('log_name', 'penilaian')->count());
        }
    }

    public function test_service_menolak_registrasi_tenant_lain()
    {
        // Guard komponen (selectParticipant) menutup jalur UI, tapi bukan itu
        // yang menjaga lintas tenant di dalam service: unfinalize() mencari
        // registrasi dengan where('eventner_id', ...). Kalau saringan itu
        // hilang, id registrasi tenant lain akan membuka nilainya.
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $regLain = Registration::factory()->for($lain, 'eventner')->create();

        $kriteriaLain = $this->rubrik('Rubrik Tenant Lain');
        AssessmentScore::create([
            'eventner_id' => $lain->id,
            'judge_id' => $this->juri->id,
            'registration_id' => $regLain->id,
            'assessment_criteria_id' => $kriteriaLain->id,
            'score' => 10,
            'is_finalized' => true,
        ]);

        $dibuka = app(\App\Services\ScoreFinalizationService::class)
            ->unfinalize($this->eventner->id, $regLain->id, $this->juri->id);

        $this->assertSame(0, $dibuka);
        $this->assertSame(1, AssessmentScore::where('eventner_id', $lain->id)
            ->where('is_finalized', true)->count(), 'Nilai tenant lain ikut terbuka.');
    }

    public function test_setelah_buka_kunci_nilai_bisa_diubah_lagi()
    {
        $kriteria = $this->rubrik('Penilaian Umum');
        $this->kunci([$kriteria->id]);

        $panel = $this->panel();

        // Sebelum dibuka: panel membaca DB dan melihatnya terkunci.
        $this->assertTrue($panel->get('isFinalized'));

        $panel->call('openUnlockModal')
            ->set('unlockReason', 'Perbaikan nilai setelah sengketa')
            ->call('unlockScores');

        $this->assertFalse($panel->get('isFinalized'), 'Panel masih menganggap nilainya terkunci.');

        // Panel menyegarkan statusnya dari DB, bukan dari properti lama.
        // Lewat set(), bukan call(): Livewire menolak pemanggilan hook langsung.
        $panel->set('selectedJudgeId', $this->juri->id);
        $this->assertFalse($panel->get('isFinalized'));

        $panel->set('scores.' . $kriteria->id, '20')
            ->call('saveScores');

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $kriteria->id,
            'judge_id' => $this->juri->id,
            'score' => 20,
        ]);
    }
}

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
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rekap nilai pada tingkat bergrup dan berbabak.
 *
 * Dua keluhan yang dijaga di sini:
 *
 *  1. "nilai PBB ada 3, semua ada 3" — tabel Grup A memuat kolom rubrik Grup B
 *     yang tak pernah dinilai untuk pesertanya. Nama kolomnya sama, jadi
 *     terbaca sebagai tiga kolom PBB.
 *  2. "masih umum semua" — satu tabel untuk seluruh tingkat, padahal nilai
 *     penyisihan dan nilai final tinggal di baris kriteria berbeda dan tidak
 *     pernah dijumlahkan.
 */
class ScoreRecapGroupsTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    private Judge $juri;

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

        $this->juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Umum']);

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
            'name' => 'Final Stage',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);
    }

    /** Rubrik + kriteria; nilai kriteria pertama dikembalikan untuk diisi. */
    private function rubrik(string $name, ?CompetitionGroup $group, ?CompetitionRound $round): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'competition_round_id' => $round?->id,
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
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function peserta(string $nama, ?CompetitionGroup $group): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'nama_sekolah' => $nama,
        ]);
    }

    private function nilai(Registration $reg, AssessmentCriteria $criteria, int $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'judge_id' => $this->juri->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'score' => $score,
        ]);
    }

    /** Nama kolom rubrik yang muncul, dalam urutan tampil. */
    private function kolomRekap(string $html): array
    {
        preg_match_all('/<th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">([^<]+)<\/h6><\/th>/', $html, $m);

        // Buang kolom angka yang bukan rubrik.
        return array_values(array_filter($m[1], fn ($t) => ! in_array(trim($t), ['Total', 'Pengurangan', 'Nilai Akhir', 'PDF'], true)));
    }

    /**
     * INI keluhan "nilai PBB ada 3": rubrik bergrup tidak boleh jadi kolom di
     * tabel grup lain, dan rubrik tanpa grup tetap muncul di kedua tabel.
     */
    public function test_tabel_grup_hanya_memuat_kolom_rubrik_grupnya()
    {
        $this->rubrik('PBB Grup A', $this->groupA, $this->penyisihan);
        $this->rubrik('PBB Grup B', $this->groupB, $this->penyisihan);
        $this->rubrik('PBB Umum', null, $this->penyisihan);

        $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 2', $this->groupB);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        // Dua tabel (satu per grup), masing-masing 2 kolom: rubrik grup itu +
        // rubrik tanpa grup. Bukan 3 kolom di satu tabel.
        $kolom = $this->kolomRekap($html);

        $this->assertSame(['PBB Grup A', 'PBB Umum', 'PBB Grup B', 'PBB Umum'], $kolom);

        // Judul per grup memang tampil, dan tetap muncul sekali tiap grup —
        // tidak tergandakan oleh kolom rubrik.
        $this->assertSame(1, substr_count($html, 'ti-users-group me-1"></i>Grup A'));
        $this->assertSame(1, substr_count($html, 'ti-users-group me-1"></i>Grup B'));
    }

    /**
     * Babak dipecah jadi judul sendiri; nilai final tidak dicampur penyisihan.
     *
     * Babak final tanpa finalis memang belum punya tabel — yang diperiksa di
     * situ judulnya tetap tampil plus keterangannya, bukan tabel hampa.
     */
    public function test_babak_memecah_rekap_jadi_tabel_terpisah()
    {
        $this->rubrik('PBB Penyisihan', $this->groupA, $this->penyisihan);
        $this->rubrik('PBB Final', null, $this->final);

        $this->peserta('SMPN 1', $this->groupA);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        $this->assertStringContainsString('Fase Grup', $html);
        $this->assertStringContainsString('Final Stage', $html);
        $this->assertStringContainsString('Belum ada peserta yang lolos ke babak ini', $html);

        // Rubrik final tidak ikut jadi kolom di tabel penyisihan.
        $this->assertSame(['PBB Penyisihan'], $this->kolomRekap($html));
    }

    /** Begitu ada finalis, tabel final muncul dengan rubrik babak final. */
    public function test_tabel_final_muncul_setelah_ada_finalis()
    {
        $this->rubrik('PBB Penyisihan', $this->groupA, $this->penyisihan);
        $this->rubrik('PBB Final', null, $this->final);

        $lolos = $this->peserta('SMPN Lolos', $this->groupA);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $lolos->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        $this->assertSame(['PBB Penyisihan', 'PBB Final'], $this->kolomRekap($html));
    }

    /**
     * Tabel final hanya memuat finalis. Sekolah yang tak pernah dinilai final
     * tidak boleh muncul bernilai nol di peringkat final.
     */
    public function test_tabel_final_hanya_memuat_peserta_yang_lolos()
    {
        $this->rubrik('PBB Penyisihan', $this->groupA, $this->penyisihan);
        $this->rubrik('PBB Final', null, $this->final);

        $lolos = $this->peserta('SMPN Lolos', $this->groupA);
        $this->peserta('SMPN Gugur', $this->groupA);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $lolos->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        // Nama peserta gugur tetap ada di tabel penyisihan, jadi yang diperiksa
        // jumlah kemunculannya: ia tidak muncul lagi di bagian final.
        $this->assertSame(1, substr_count($html, 'SMPN Gugur'));
        $this->assertSame(2, substr_count($html, 'SMPN Lolos'));
    }

    /**
     * Peringkat dihitung di dalam bagiannya sendiri: peserta Grup B bisa juara
     * walau nilainya lebih rendah dari peserta Grup A.
     */
    public function test_peringkat_dihitung_per_bagian()
    {
        $kriteriaA = $this->rubrik('PBB Grup A', $this->groupA, $this->penyisihan);
        $kriteriaB = $this->rubrik('PBB Grup B', $this->groupB, $this->penyisihan);

        $kuat = $this->peserta('SMPN Kuat', $this->groupA);
        $lemah = $this->peserta('SMPN Lemah', $this->groupB);

        $this->nilai($kuat, $kriteriaA, 90);
        $this->nilai($lemah, $kriteriaB, 50);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        // Dua medali emas, satu di tiap tabel grup.
        $this->assertSame(2, substr_count($html, '🥇'));
    }

    /** Tingkat tanpa babak tetap satu daftar tabel per grup — perilaku lama. */
    public function test_tingkat_tanpa_babak_tidak_dipecah_per_babak()    {
        AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Rubrik Polos',
            'sort_order' => 1,
        ]);

        $this->final->delete();
        $this->penyisihan->delete();
        $this->groupA->delete();
        $this->groupB->delete();

        $this->peserta('SMPN 1', null);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        $this->assertStringNotContainsString('Final Stage', $html);
        // Peserta belum bergrup tidak diberi judul "Belum Bergrup" di tingkat
        // yang memang tak punya grup.
        $this->assertStringNotContainsString('Belum Bergrup', $html);
        $this->assertStringContainsString('SMPN 1', $html);
    }

    /**
     * Finalis dari grup berbeda tampil di tabel per grup, bukan satu tabel.
     *
     * Rubrik final tidak mengenal grup (satu set untuk semua finalis), jadi
     * kolomnya memang sama di kedua tabel — tapi barisnya tetap dipisah grup,
     * karena itulah bentuk yang diminta panitia, dan grup tetap konteks yang
     * dipakai lembar PDF maupun halaman Peserta.
     */
    public function test_finalis_grup_berbeda_dipisah_per_grup()
    {
        $this->rubrik('PBB Penyisihan A', $this->groupA, $this->penyisihan);
        $this->rubrik('PBB Penyisihan B', $this->groupB, $this->penyisihan);
        $kriteriaFinal = $this->rubrik('PBB Final', null, $this->final);

        $dariA = $this->peserta('SMPN Grup A', $this->groupA);
        $dariB = $this->peserta('SMPN Grup B', $this->groupB);

        foreach ([$dariA, $dariB] as $reg) {
            CompetitionRoundRegistration::create([
                'eventner_id' => $this->eventner->id,
                'competition_round_id' => $this->final->id,
                'registration_id' => $reg->id,
                'competition_group_id' => $reg->competition_group_id,
            ]);
        }

        $this->nilai($dariA, $kriteriaFinal, 80);
        $this->nilai($dariB, $kriteriaFinal, 95);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        $blokFinal = substr($html, strpos($html, 'Final Stage'));

        // Dua tabel finalis: satu per grup, masing-masing dengan judul grupnya.
        $this->assertSame(1, substr_count($blokFinal, 'ti-users-group me-1"></i>Grup A'));
        $this->assertSame(1, substr_count($blokFinal, 'ti-users-group me-1"></i>Grup B'));
        $this->assertSame(1, substr_count($blokFinal, 'SMPN Grup A'));
        $this->assertSame(1, substr_count($blokFinal, 'SMPN Grup B'));

        // Peringkat dihitung di dalam tabel grup masing-masing, jadi tiap grup
        // punya juaranya sendiri.
        $this->assertSame(2, substr_count($blokFinal, '🥇'));

        // Label tabel gabungan tak dipakai lagi.
        $this->assertStringNotContainsString('Seluruh Finalis', $html);
    }

    /** Tanpa finalis, bagian final tidak memunculkan tabel hampa. */
    public function test_final_tanpa_finalis_tidak_membuat_tabel_hampa()
    {
        $this->rubrik('PBB Penyisihan', $this->groupA, $this->penyisihan);
        $this->rubrik('PBB Final', null, $this->final);

        $this->peserta('SMPN 1', $this->groupA);

        $html = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $this->level->id,
        ])->html();

        $this->assertStringContainsString('Final Stage', $html);
        $this->assertStringContainsString('Belum ada peserta yang lolos ke babak ini', $html);
        $this->assertStringNotContainsString('Seluruh Finalis', $html);
    }
}

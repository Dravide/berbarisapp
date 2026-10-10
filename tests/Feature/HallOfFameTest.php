<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Hall of Fame — rekap juara lintas event satu penyelenggara.
 *
 * Kelompoknya dari diselenggarakan_oleh (dinormalisasi trim + case-fold),
 * bukan user pemilik. Yang dijaga: dua event satu penyelenggara tampil
 * berdampingan, event penyelenggara lain tak bocor, hanya kategori juara
 * is_public, kuota mengikuti quantity, dan juara tanpa nilai tidak ikut.
 */
class HallOfFameTest extends TestCase
{
    use RefreshDatabase;

    private string $penyelenggara = 'Dinas Pendidikan Kota Tua';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeEvent(array $attrs = []): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'diselenggarakan_oleh' => $this->penyelenggara,
            ...$attrs,
        ]);
    }

    /**
     * Tingkat + rubrik (sub-kategori + kriteria) siap dipakai satu event.
     *
     * @return array{0: CompetitionCategory, 1: AssessmentSubCategory, 2: AssessmentCriteria}
     */
    private function makeRubrik(Eventner $event): array
    {
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $event->id,
            'parent_id' => null,
        ]);
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $event->id,
            'parent_id' => $parent->id,
        ]);

        $category = AssessmentCategory::create([
            'eventner_id' => $event->id,
            'competition_category_id' => $level->id,
            'name' => 'PBB ' . $event->id,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $event->id,
            'sort_order' => 1,
        ]);
        $criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Keterampilan ' . $event->id,
            'score_options' => [['score' => 5], ['score' => 10], ['score' => 20], ['score' => 30], ['score' => 100]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return [$level, $sub, $criteria];
    }

    private function makeChampion(Eventner $event, AssessmentSubCategory $sub, array $attrs = []): ChampionCategory
    {
        $champion = ChampionCategory::create([
            'eventner_id' => $event->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
            'is_public' => true,
            ...$attrs,
        ]);
        $champion->assessmentSubCategories()->sync([$sub->id]);

        return $champion;
    }

    private function makePeserta(Eventner $event, CompetitionCategory $level, AssessmentCriteria $criteria, string $sekolah, int $skor): Registration
    {
        $reg = Registration::factory()->for($event, 'eventner')->create([
            'competition_category_id' => $level->id,
            'nama_sekolah' => $sekolah,
        ]);

        AssessmentScore::create([
            'eventner_id' => $event->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'judge_id' => Judge::create([
                'eventner_id' => $event->id,
                'name' => 'Juri ' . $sekolah,
            ])->id,
            'score' => $skor,
            'is_finalized' => true,
        ]);

        return $reg;
    }

    /** Satu juara sah siap tampil: tingkat + rubrik + kategori publik + peserta berskor. */
    private function makeJuara(Eventner $event, string $sekolah, int $skor, array $championAttrs = []): Registration
    {
        [$level, $sub, $criteria] = $this->makeRubrik($event);
        $this->makeChampion($event, $sub, $championAttrs);

        return $this->makePeserta($event, $level, $criteria, $sekolah, $skor);
    }

    public function test_dua_event_penyelenggara_sama_menampilkan_juara_keduanya()
    {
        $lama = $this->makeEvent(['nama_event' => 'Kompak Cup I', 'tanggal' => '2026-01-10']);
        $baru = $this->makeEvent(['nama_event' => 'Kompak Cup II', 'tanggal' => '2026-05-20']);

        $this->makeJuara($lama, 'SMPN 1', 100);
        $this->makeJuara($baru, 'SMPN 2', 90);

        $this->get(route('public.hall-of-fame', Str::slug($this->penyelenggara)))
            ->assertOk()
            ->assertSee($this->penyelenggara)
            ->assertSee('Kompak Cup I')
            ->assertSee('Kompak Cup II')
            ->assertSee('SMPN 1')
            ->assertSee('SMPN 2')
            // Event terbaru lebih dulu.
            ->assertSeeInOrder(['Kompak Cup II', 'Kompak Cup I']);
    }

    public function test_penyelenggara_lain_tidak_bocor()
    {
        $saya = $this->makeEvent(['nama_event' => 'Event Milik Saya']);
        $this->makeJuara($saya, 'SMPN Saya', 100);

        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
            'diselenggarakan_oleh' => 'Dinas Lain Sekota',
            'nama_event' => 'Event Milik Lain',
        ]);
        $this->makeJuara($eventLain, 'SMPN Lain', 100);

        $this->get(route('public.hall-of-fame', Str::slug($this->penyelenggara)))
            ->assertOk()
            ->assertSee('SMPN Saya')
            ->assertDontSee('SMPN Lain')
            ->assertDontSee('Event Milik Lain');
    }

    public function test_kategori_non_publik_disembunyikan()
    {
        $event = $this->makeEvent();
        [$level, $sub, $criteria] = $this->makeRubrik($event);

        $rahasia = $this->makeChampion($event, $sub, [
            'name' => 'Kategori Rahasia Belum Diumumkan',
            'is_public' => false,
        ]);

        $this->makePeserta($event, $level, $criteria, 'SMPN Rahasia', 100);

        $this->get(route('public.hall-of-fame', Str::slug($this->penyelenggara)))
            ->assertOk()
            ->assertDontSee('Kategori Rahasia Belum Diumumkan')
            ->assertDontSee('SMPN Rahasia');
    }

    public function test_kuota_juara_menghormati_quantity()
    {
        $event = $this->makeEvent();
        [$level, $sub, $criteria] = $this->makeRubrik($event);
        $this->makeChampion($event, $sub, ['quantity' => 2]);

        // 4 peserta berskor, kuota 2 → hanya 2 teratas tampil.
        $this->makePeserta($event, $level, $criteria, 'SMPN Perunggu', 10);
        $this->makePeserta($event, $level, $criteria, 'SMPN Perak', 20);
        $this->makePeserta($event, $level, $criteria, 'SMPN Emas', 30);
        $this->makePeserta($event, $level, $criteria, 'SMPN Sisa', 5);

        $this->get(route('public.hall-of-fame', Str::slug($this->penyelenggara)))
            ->assertOk()
            ->assertSee('SMPN Emas')
            ->assertSee('SMPN Perak')
            ->assertDontSee('SMPN Perunggu')
            ->assertDontSee('SMPN Sisa');
    }

    public function test_event_belum_approved_tidak_tampil()
    {
        $approved = $this->makeEvent(['nama_event' => 'Event Disetujui']);
        $this->makeJuara($approved, 'SMPN Disetujui', 100);

        $pending = $this->makeEvent([
            'nama_event' => 'Event Menunggu Persetujuan',
            'status' => 'pending',
        ]);
        $this->makeJuara($pending, 'SMPN Pending', 100);

        $this->get(route('public.hall-of-fame', Str::slug($this->penyelenggara)))
            ->assertOk()
            ->assertSee('Event Disetujui')
            ->assertDontSee('Event Menunggu Persetujuan')
            ->assertDontSee('SMPN Pending');
    }

    public function test_penyelenggara_tak_kenal_404()
    {
        $this->makeEvent();

        $this->get(route('public.hall-of-fame', 'penyelenggara-tidak-ada'))->assertNotFound();
    }

    public function test_juara_tanpa_nilai_tidak_tampil()
    {
        $event = $this->makeEvent(['nama_event' => 'Event Kosong Nilai']);
        [$level, $sub, $criteria] = $this->makeRubrik($event);
        $this->makeChampion($event, $sub, ['name' => 'Juara Tanpa Pemegang']);

        // Peserta ada, tapi belum ada satupun skor.
        Registration::factory()->for($event, 'eventner')->create([
            'competition_category_id' => $level->id,
            'nama_sekolah' => 'SMPN Tanpa Nilai',
        ]);

        $this->get(route('public.hall-of-fame', Str::slug($this->penyelenggara)))
            ->assertOk()
            ->assertDontSee('Juara Tanpa Pemegang')
            ->assertSee('Belum ada juara yang diumumkan.');
    }
}

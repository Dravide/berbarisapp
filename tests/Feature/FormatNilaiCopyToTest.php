<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Salin rubrik ke tingkat lain.
 *
 * Bug yang dijaga di sini bukan di backend — salinannya memang selalu berhasil.
 * Yang salah bentuk paramnya: `dispatch('copy:done', ['success' => true])`
 * mengirim param POSISIONAL, dan Livewire menaruh param itu apa adanya di
 * `event.detail`. Jadi detail-nya sampai di browser sebagai array `[ {...} ]`,
 * bukan objek. `d.success` pada array selalu undefined, cabangnya jatuh ke
 * SweetAlert "Gagal" — padahal rubriknya sudah tersalin. Halaman menampilkan
 * kegagalan untuk pekerjaan yang sukses.
 *
 * Karena itu tes ini mengunci BENTUK param (harus objek dengan key `success`),
 * bukan cuma nilainya. Kembali ke bentuk posisional akan menggagalkannya.
 */
class FormatNilaiCopyToTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $sumber;

    private CompetitionCategory $tujuan;

    private AssessmentCategory $rubrik;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $this->actingAs($user);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->sumber = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
            'name' => 'Sumber',
        ]);
        $this->tujuan = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
            'name' => 'Tujuan',
        ]);

        $this->rubrik = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->sumber->id,
            'name' => 'PBB',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $this->rubrik->id,
            'name' => 'Gerakan',
            'sort_order' => 1,
        ]);
        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Sikap',
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    /** Param sebuah dispatch, apa adanya dari efek Livewire. */
    private function paramsOf(string $event, array $dispatches): array
    {
        foreach ($dispatches as $d) {
            if (($d['name'] ?? null) === $event) {
                return $d['params'] ?? [];
            }
        }

        $this->fail("Event {$event} tidak dikirim.");
    }

    private function builder(): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(Builder::class)
            ->set('activeTab', (string) $this->sumber->id)
            ->call('openCopyToModal', $this->rubrik->id)
            ->set('copyToTargetCompetitionCategoryId', $this->tujuan->id);
    }

    public function test_salinan_berhasil_dan_paramnya_objek_bukan_array()
    {
        $komponen = $this->builder()->call('confirmCopyTo')->call('executeCopyTo');

        $params = $this->paramsOf('copy:done', $komponen->effects['dispatches']);

        // Inti bug: param bernama sampai sebagai objek, jadi JS bisa membaca
        // d.success. Kalau dispatch-nya kembali posisional, key ini hilang.
        $this->assertArrayHasKey('success', $params, 'copy:done dikirim dengan param posisional — JS akan membaca d.success = undefined dan menampilkan "Gagal".');
        $this->assertTrue($params['success'], 'Salinan yang berhasil dilaporkan gagal.');
        $this->assertStringContainsString('berhasil disalin', $params['message']);

        // Dan salinannya memang benar-benar ada.
        $this->assertSame(1, AssessmentCategory::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->tujuan->id)
            ->where('name', 'PBB')
            ->count());
    }

    public function test_konfirmasi_juga_memakai_param_objek()
    {
        $params = $this->paramsOf('copy:confirm', $this->builder()->call('confirmCopyTo')->effects['dispatches']);

        $this->assertArrayHasKey('source_name', $params);
        $this->assertArrayHasKey('target_name', $params);
        $this->assertSame('PBB', $params['source_name']);
    }

    /**
     * Jalur gagal wajib ikut melapor lewat copy:done — sebelumnya ia hanya
     * menulis session('error') lalu diam, sehingga tombolnya tampak tak
     * bereaksi sama sekali.
     */
    public function test_gagal_melapor_lewat_copy_done()
    {
        $komponen = $this->builder()
            ->set('copyToTargetCompetitionCategoryId', null)
            ->call('confirmCopyTo');

        $params = $this->paramsOf('copy:done', $komponen->effects['dispatches']);

        $this->assertArrayHasKey('success', $params);
        $this->assertFalse($params['success']);
    }

    /** Tingkat tujuan milik event lain ditolak, dan pelaporannya lewat copy:done. */
    public function test_tujuan_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $indukLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id, 'parent_id' => null]);
        $tujuanLain = CompetitionCategory::factory()->create([
            'eventner_id' => $lain->id,
            'parent_id' => $indukLain->id,
        ]);

        $params = $this->paramsOf('copy:done', $this->builder()
            ->set('copyToTargetCompetitionCategoryId', $tujuanLain->id)
            ->call('executeCopyTo')
            ->effects['dispatches']);

        $this->assertFalse($params['success']);
        $this->assertSame(0, AssessmentCategory::where('competition_category_id', $tujuanLain->id)->count());
    }
}

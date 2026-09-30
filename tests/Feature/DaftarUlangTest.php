<?php

namespace Tests\Feature;

use App\Livewire\Eventner\DaftarUlang\Index;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionSeries;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MemeriksaToast;
use Tests\TestCase;

/**
 * Meja daftar ulang — tempat seri sebuah pasukan akhirnya ditetapkan.
 *
 * Inilah satu-satunya UI yang menulis `registrations.competition_series_id`.
 * Sebelum layar ini ada, seri hanya datang dari migrasi backfill, sehingga
 * keputusan "pasukan ini ikut seri mana" tak bisa diambil panitia di hari H.
 *
 * Dua hal yang paling mudah salah dan dijaga di sini:
 *
 *  1. Memindah seri setelah nilai masuk. Seri menentukan rubrik, jadi nilai
 *     lama akan menempel di kriteria yang tak lagi dihuni pasukan itu — dan
 *     lenyap diam-diam dari rekap. Diputuskan: BLOKIR.
 *  2. Id dari DOM. Tingkat yang sedang dibuka adalah satu-satunya yang sah
 *     untuk grup maupun seri; id tingkat lain tidak boleh lolos.
 */
class DaftarUlangTest extends TestCase
{
    use RefreshDatabase;
    use MemeriksaToast;

    private User $user;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Kelas 9',
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);
        $this->groupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);

        $this->seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);
        $this->seriB = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri B',
            'sort_order' => 2,
        ]);
    }

    private function peserta(string $nama, ?CompetitionGroup $group = null, ?CompetitionSeries $series = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'competition_series_id' => $series?->id,
            'nama_sekolah' => $nama,
        ]);
    }

    /** Rubrik + kriteria milik satu seri; kriteria dipakai untuk menaruh nilai. */
    private function rubrik(string $name, CompetitionSeries $series): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $series->id,
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

    private function nilai(Registration $reg, AssessmentCriteria $criteria, int $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'judge_id' => Judge::create([
                'eventner_id' => $this->eventner->id,
                'name' => 'Juri ' . $reg->id,
            ])->id,
            'score' => $score,
        ]);
    }

    protected function panel()
    {
        return Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('activeTab', (string) $this->level->id);
    }

    // ── Bagian seri ────────────────────────────────────────────────────────

    public function test_seri_ditetapkan_dari_meja_daftar_ulang()
    {
        $peserta = $this->peserta('SMPN 1');

        $this->panel()->call('setSeries', $peserta->id, $this->seriB->id);

        $this->assertSame($this->seriB->id, $peserta->fresh()->competition_series_id);
    }

    /**
     * Inti permintaan aslinya: dua pasukan SATU GRUP boleh berbeda seri.
     */
    public function test_dua_pasukan_satu_grup_boleh_beda_seri()
    {
        $satu = $this->peserta('SMPN 1', $this->groupA);
        $dua = $this->peserta('SMPN 2', $this->groupA);

        $this->panel()
            ->call('setSeries', $satu->id, $this->seriA->id)
            ->call('setSeries', $dua->id, $this->seriB->id);

        $this->assertSame($this->seriA->id, $satu->fresh()->competition_series_id);
        $this->assertSame($this->seriB->id, $dua->fresh()->competition_series_id);
        $this->assertSame($this->groupA->id, $satu->fresh()->competition_group_id);
        $this->assertSame($this->groupA->id, $dua->fresh()->competition_group_id);
    }

    /** Memindah seri setelah ada nilai juri diblokir — keputusan panitia. */
    public function test_seri_tidak_bisa_dipindah_setelah_ada_nilai()
    {
        $kriteria = $this->rubrik('PBB Seri A', $this->seriA);
        $peserta = $this->peserta('SMPN 1', $this->groupA, $this->seriA);
        $this->nilai($peserta, $kriteria, 80);

        $panel = $this->panel()
            ->call('setSeries', $peserta->id, $this->seriB->id);

        $this->assertAdaToast($panel, 'tidak bisa dipindah');

        $this->assertSame(
            $this->seriA->id,
            $peserta->fresh()->competition_series_id,
            'Seri berpindah walau nilai juri sudah masuk.'
        );
    }

    /** Seri yang sama bukan pemindahan — tak ada yang perlu diblokir. */
    public function test_menyetel_seri_yang_sama_tidak_diblokir()
    {
        $kriteria = $this->rubrik('PBB Seri A', $this->seriA);
        $peserta = $this->peserta('SMPN 1', $this->groupA, $this->seriA);
        $this->nilai($peserta, $kriteria, 80);

        $this->panel()
            ->call('setSeries', $peserta->id, $this->seriA->id)
            ->assertHasNoErrors();

        $this->assertSame($this->seriA->id, $peserta->fresh()->competition_series_id);
    }

    /** Mengosongkan seri juga diblokir bila nilainya sudah masuk. */
    public function test_mengosongkan_seri_setelah_ada_nilai_juga_diblokir()
    {
        $kriteria = $this->rubrik('PBB Seri A', $this->seriA);
        $peserta = $this->peserta('SMPN 1', $this->groupA, $this->seriA);
        $this->nilai($peserta, $kriteria, 80);

        $panel = $this->panel()
            ->call('setSeries', $peserta->id, '');

        $this->assertAdaToast($panel, 'tidak bisa dipindah');

        $this->assertSame($this->seriA->id, $peserta->fresh()->competition_series_id);
    }

    /** Seri milik tingkat lain ditolak, tidak ditulis. */
    public function test_seri_tingkat_lain_ditolak()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Kelas 7',
        ]);
        $seriLain = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Seri Kelas 7',
        ]);

        $peserta = $this->peserta('SMPN 1', $this->groupA);

        $panel = $this->panel()
            ->call('setSeries', $peserta->id, $seriLain->id);

        $this->assertAdaToast($panel, 'Seri tidak ditemukan');

        $this->assertNull($peserta->fresh()->competition_series_id);
    }

    /**
     * Yang benar-benar dijanjikan: menetapkan seri mengubah rubrik yang dilihat
     * pasukan itu.
     *
     * scopeForEntry() adalah satu-satunya pintu "rubrik mana yang boleh
     * dinilai", jadi tes ini yang membuktikan layar ini bukan sekadar menulis
     * kolom.
     */
    public function test_menetapkan_seri_mengubah_rubrik_pasukan()
    {
        $this->rubrik('PBB Seri A', $this->seriA);
        $this->rubrik('PBB Seri B', $this->seriB);

        $peserta = $this->peserta('SMPN 1', $this->groupA);

        $sebelum = AssessmentCategory::forEntry($this->level->id, $peserta->competition_series_id)
            ->pluck('name')->all();

        $this->panel()->call('setSeries', $peserta->id, $this->seriB->id);

        $sesudah = AssessmentCategory::forEntry($this->level->id, $peserta->fresh()->competition_series_id)
            ->pluck('name')->all();

        $this->assertSame([], $sebelum, 'Tanpa seri, rubrik berseri tidak boleh berlaku.');
        $this->assertSame(['PBB Seri B'], $sesudah);
    }

    // ── Bagian grup & undian ──────────────────────────────────────────────

    public function test_grup_ditetapkan_dan_nomor_undian_dikosongkan()
    {
        $peserta = $this->peserta('SMPN 1', $this->groupA);
        $peserta->update(['urutan_tampil' => 3]);

        $this->panel()->call('setGroup', $peserta->id, $this->groupB->id);

        $this->assertSame($this->groupB->id, $peserta->fresh()->competition_group_id);
        $this->assertNull(
            $peserta->fresh()->urutan_tampil,
            'Nomor undian ikut pindah grup — undian disusun per grup.'
        );
    }

    /** Menyetel grup yang sama tidak mengosongkan undian. */
    public function test_grup_yang_sama_tidak_menghapus_nomor_undian()
    {
        $peserta = $this->peserta('SMPN 1', $this->groupA);
        $peserta->update(['urutan_tampil' => 4]);

        $this->panel()->call('setGroup', $peserta->id, $this->groupA->id);

        $this->assertSame(4, $peserta->fresh()->urutan_tampil);
    }

    public function test_grup_tingkat_lain_ditolak()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Kelas 7',
        ]);
        $grupLain = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Grup Kelas 7',
        ]);

        $peserta = $this->peserta('SMPN 1');

        $panel = $this->panel()
            ->call('setGroup', $peserta->id, $grupLain->id);

        $this->assertAdaToast($panel, 'Grup tidak ditemukan');

        $this->assertNull($peserta->fresh()->competition_group_id);
    }

    public function test_nomor_undian_ditetapkan_dari_meja()
    {
        $peserta = $this->peserta('SMPN 1', $this->groupA);

        $this->panel()->call('setUndian', $peserta->id, 5);

        $this->assertSame(5, $peserta->fresh()->urutan_tampil);
    }

    /**
     * Nomor undian unik per GRUP, bukan per tingkat — nomor sama di grup lain
     * bukan bentrok.
     */
    public function test_nomor_undian_sama_di_grup_berbeda_bukan_bentrok()
    {
        $satu = $this->peserta('SMPN 1', $this->groupA);
        $dua = $this->peserta('SMPN 2', $this->groupB);

        $this->panel()
            ->call('setUndian', $satu->id, 1)
            ->call('setUndian', $dua->id, 1)
            ->assertHasNoErrors('undian');

        $this->assertSame(1, $satu->fresh()->urutan_tampil);
        $this->assertSame(1, $dua->fresh()->urutan_tampil);
    }

    public function test_nomor_undian_bentrok_di_grup_yang_sama_ditolak()
    {
        $satu = $this->peserta('SMPN 1', $this->groupA);
        $dua = $this->peserta('SMPN 2', $this->groupA);
        $satu->update(['urutan_tampil' => 2]);

        $panel = $this->panel()
            ->call('setUndian', $dua->id, 2);

        $this->assertAdaToast($panel, 'Nomor undian');

        $this->assertNull($dua->fresh()->urutan_tampil);
    }

    public function test_nomor_undian_di_bawah_satu_ditolak()
    {
        $peserta = $this->peserta('SMPN 1', $this->groupA);

        $panel = $this->panel()
            ->call('setUndian', $peserta->id, 0);

        $this->assertAdaToast($panel, 'Nomor undian');

        $this->assertNull($peserta->fresh()->urutan_tampil);
    }

    // ── Bagian kehadiran ──────────────────────────────────────────────────

    public function test_tandai_hadir_mengisi_jam_kedatangan()
    {
        $peserta = $this->peserta('SMPN 1');

        $this->panel()->call('toggleHadir', $peserta->id);

        $this->assertNotNull($peserta->fresh()->daftar_ulang_at);
    }

    /** Tombolnya dua arah: salah centang harus bisa dibatalkan. */
    public function test_tandai_hadir_dua_kali_membatalkan_tandanya()
    {
        $peserta = $this->peserta('SMPN 1');

        $this->panel()
            ->call('toggleHadir', $peserta->id)
            ->call('toggleHadir', $peserta->id);

        $this->assertNull($peserta->fresh()->daftar_ulang_at);
    }

    // ── Saringan & angka antrean ──────────────────────────────────────────

    public function test_hitung_belum_hadir_hanya_tingkat_ini()
    {
        $this->peserta('SMPN 1');
        $this->peserta('SMPN 2');
        $this->peserta('SMPN 3')->update(['daftar_ulang_at' => now()]);

        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Kelas 7',
        ]);
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $lain->id,
            'nama_sekolah' => 'SMPN Tingkat Lain',
        ]);

        $this->assertSame(2, $this->panel()->instance()->belumHadir);
    }

    /**
     * Angka "belum hadir" dihitung dari SELURUH tingkat, bukan dari hasil
     * pencarian — panitia memakainya untuk memutuskan kapan meja ditutup.
     */
    public function test_kotak_cari_tidak_mengubah_angka_belum_hadir()
    {
        $this->peserta('SMPN Satu');
        $this->peserta('SMPN Dua');

        $panel = $this->panel()->set('search', 'Satu');

        $this->assertSame(1, $panel->instance()->participants->count());
        $this->assertSame(2, $panel->instance()->belumHadir);
    }

    public function test_kotak_cari_menyaring_nama_sekolah()
    {
        $this->peserta('SMPN Satu');
        $this->peserta('SMPN Dua');

        $this->assertSame(
            ['SMPN Dua'],
            $this->panel()->set('search', 'Dua')->instance()->participants->pluck('nama_sekolah')->all()
        );
    }

    /** Id registrasi tingkat lain tidak boleh tersentuh dari layar ini. */
    public function test_peserta_tingkat_lain_tidak_bisa_diubah()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Kelas 7',
        ]);
        $pesertaLain = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $lain->id,
            'nama_sekolah' => 'SMPN Tingkat Lain',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            $this->panel()->call('setSeries', $pesertaLain->id, $this->seriA->id);
        } finally {
            $this->assertNull($pesertaLain->fresh()->competition_series_id);
        }
    }

    public function test_halaman_tampil_dengan_peserta_dan_serinya()
    {
        $this->peserta('SMPN Satu', $this->groupA, $this->seriA);

        $this->panel()
            ->assertSee('SMPN Satu')
            ->assertSee('Seri A')
            ->assertSee('Grup A')
            ->assertSee('Belum hadir');
    }
}

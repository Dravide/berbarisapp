<?php

namespace Tests\Feature;

use App\Livewire\Admin\School\Show;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Perbaikan data pendaftaran yang nyangkut dari halaman admin Data Sekolah.
 *
 * Kasus nyata: satu pendaftaran menyimpan competition_category_id milik EVENT
 * LAIN (event pendaftarannya kosong tanpa kategori). Panitia tidak bisa
 * membetulkannya — halaman kategori & peserta mereka di-scope ke event sendiri.
 */
class AdminSchoolRegistrationFixTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    private function school(string $npsn = '50766375'): School
    {
        return School::create([
            'npsn' => $npsn,
            'nama_sekolah' => 'SMA Negeri Contoh',
            'no_hp' => '08123456789',
            'school_email' => 'sekolah@example.test',
        ]);
    }

    /**
     * Skenario data rusak: kategori milik event lain.
     *
     * @return array{0: Eventner, 1: Registration, 2: CompetitionCategory}
     */
    private function registrasiLintasEvent(School $school): array
    {
        $eventPendaftaran = Eventner::factory()->create();
        $eventKategori = Eventner::factory()->create();

        $kategoriAsing = CompetitionCategory::factory()->create([
            'eventner_id' => $eventKategori->id,
            'parent_id' => null,
            'name' => 'reiciendis Campuran',
        ]);

        $reg = Registration::factory()->create([
            'eventner_id' => $eventPendaftaran->id,
            'competition_category_id' => $kategoriAsing->id,
            'npsn' => $school->npsn,
            'nama_sekolah' => $school->nama_sekolah,
            'payment_status' => 'free',
        ]);

        return [$eventPendaftaran, $reg, $kategoriAsing];
    }

    /**
     * Satu nilai juri untuk sebuah pendaftaran.
     *
     * assessment_scores.assessment_criteria_id NOT NULL dan rubriknya dimiliki
     * event lewat rantai kategori → sub kategori, jadi jalur lengkap ini yang
     * dipakai — bukan factory yang tidak ada.
     */
    private function beriNilaiJuri(Eventner $eventner, Registration $reg): AssessmentScore
    {
        $kategori = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Penilaian',
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $kategori->id,
            'name' => 'Sub A',
        ]);
        $kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria A',
            'score_options' => [['label' => 'Baik', 'value' => 100]],
            'weight' => 1,
        ]);

        return AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'judge_id' => Judge::create(['eventner_id' => $eventner->id, 'name' => 'Juri 1'])->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $kriteria->id,
            'score' => 90,
        ]);
    }

    // ────────────────────────────────────────────────
    // Ubah kategori
    // ────────────────────────────────────────────────

    public function test_admin_memindahkan_pendaftaran_ke_kategori_event_yang_benar()
    {
        $school = $this->school();
        [$eventPendaftaran, $reg, $kategoriAsing] = $this->registrasiLintasEvent($school);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $eventPendaftaran->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $kategoriBenar = CompetitionCategory::factory()->create([
            'eventner_id' => $eventPendaftaran->id,
            'parent_id' => $induk->id,
            'name' => 'U13 - SD / MI',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('openCategoryModal', $reg->id)
            ->assertSet('showCategoryModal', true)
            ->set('newCategoryId', (string) $kategoriBenar->id)
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertSame($kategoriBenar->id, $reg->fresh()->competition_category_id);
        $this->assertNotSame($kategoriAsing->id, $reg->fresh()->competition_category_id);
    }

    /** Nomor undian menempel pada kategori — pindah kategori harus mengosongkannya. */
    public function test_pindah_kategori_mereset_nomor_undian()
    {
        $school = $this->school();
        [$eventPendaftaran, $reg] = $this->registrasiLintasEvent($school);

        $reg->update(['urutan_tampil' => 7]);

        $tujuan = CompetitionCategory::factory()->create(['eventner_id' => $eventPendaftaran->id]);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('openCategoryModal', $reg->id)
            ->set('newCategoryId', (string) $tujuan->id)
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertNull($reg->fresh()->urutan_tampil);
    }

    /**
     * Dropdown hanya boleh menawarkan kategori milik event pendaftaran.
     * Tanpa ini, admin bisa memindahkan baris ke event ketiga — mengulang
     * persis masalah yang sedang diperbaiki.
     */
    public function test_dropdown_hanya_berisi_kategori_event_pendaftaran()
    {
        $school = $this->school();
        [$eventPendaftaran, $reg] = $this->registrasiLintasEvent($school);

        $milikEventLain = CompetitionCategory::factory()->create(['eventner_id' => Eventner::factory()]);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('openCategoryModal', $reg->id)
            ->assertViewHas('categoryOptions', fn ($opsi) => $opsi->pluck('id')->doesntContain($milikEventLain->id));
    }

    /** Kategori event lain ditolak walau id-nya dikirim langsung. */
    public function test_kategori_event_lain_ditolak()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        $kategoriEventLain = CompetitionCategory::factory()->create(['eventner_id' => Eventner::factory()]);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('openCategoryModal', $reg->id)
            ->set('newCategoryId', (string) $kategoriEventLain->id)
            ->call('saveCategory')
            ->assertHasErrors('newCategoryId');

        $this->assertNotSame($kategoriEventLain->id, $reg->fresh()->competition_category_id);
    }

    /** Nilai juri menempel pada pendaftaran+kategori — pindah setelah dinilai diblokir. */
    public function test_pindah_kategori_diblokir_bila_sudah_ada_nilai_juri()
    {
        $school = $this->school();
        [$eventPendaftaran, $reg] = $this->registrasiLintasEvent($school);

        $this->beriNilaiJuri($eventPendaftaran, $reg);

        $tujuan = CompetitionCategory::factory()->create(['eventner_id' => $eventPendaftaran->id]);
        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('openCategoryModal', $reg->id)
            ->set('newCategoryId', (string) $tujuan->id)
            ->call('saveCategory')
            ->assertHasErrors('newCategoryId');

        $this->assertNotSame($tujuan->id, $reg->fresh()->competition_category_id);
    }

    /** Pendaftaran sekolah lain tidak boleh disentuh lewat halaman sekolah ini. */
    public function test_pendaftaran_sekolah_lain_tidak_bisa_diubah()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        $sekolahLain = $this->school('11111111');

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $sekolahLain->npsn])
            ->call('openCategoryModal', $reg->id)
            ->assertSet('showCategoryModal', false);
    }

    // ────────────────────────────────────────────────
    // Hapus pendaftaran
    // ────────────────────────────────────────────────

    public function test_admin_menghapus_pendaftaran_tanpa_pembayaran()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('deleteRegistration', $reg->id);

        $this->assertDatabaseMissing('registrations', ['id' => $reg->id]);
    }

    /** Uang yang sudah masuk tidak boleh hilang tanpa keputusan sadar. */
    public function test_hapus_pendaftaran_yang_sudah_dibayar_ditolak()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        $reg->update(['payment_status' => 'paid']);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('deleteRegistration', $reg->id);

        $this->assertDatabaseHas('registrations', ['id' => $reg->id]);
    }

    public function test_hapus_pendaftaran_dengan_vote_berbayar_ditolak()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        VoteTransaction::create([
            'eventner_id' => $reg->eventner_id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'TRX-1',
            'qr_url' => 'https://example.test/qr/1',
            'amount' => 5000,
            'votes_earned' => 1,
            'status' => 'PAID',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('deleteRegistration', $reg->id);

        $this->assertDatabaseHas('registrations', ['id' => $reg->id]);
    }

    public function test_hapus_pendaftaran_dengan_nilai_juri_ditolak()
    {
        $school = $this->school();
        [$eventPendaftaran, $reg] = $this->registrasiLintasEvent($school);

        $this->beriNilaiJuri($eventPendaftaran, $reg);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('deleteRegistration', $reg->id);

        $this->assertDatabaseHas('registrations', ['id' => $reg->id]);
    }

    public function test_pendaftaran_sekolah_lain_tidak_bisa_dihapus()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        $sekolahLain = $this->school('11111111');

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $sekolahLain->npsn])
            ->call('deleteRegistration', $reg->id);

        $this->assertDatabaseHas('registrations', ['id' => $reg->id]);
    }

    // ────────────────────────────────────────────────
    // Hapus sekolah
    // ────────────────────────────────────────────────

    public function test_sekolah_tanpa_pendaftaran_bisa_dihapus()
    {
        $school = $this->school();

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('deleteSchool');

        $this->assertDatabaseMissing('schools', ['npsn' => $school->npsn]);
    }

    /** Riwayat, pembayaran, dan peserta hidup di baris registrations — jangan ikut terbuang. */
    public function test_sekolah_yang_masih_punya_pendaftaran_tidak_bisa_dihapus()
    {
        $school = $this->school();
        $this->registrasiLintasEvent($school);

        Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn])
            ->call('deleteSchool');

        $this->assertDatabaseHas('schools', ['npsn' => $school->npsn]);
    }

    /** Setelah pendaftaran terakhir dihapus, baris sekolah baru bisa dibuang. */
    public function test_sekolah_bisa_dihapus_setelah_pendaftarannya_dibersihkan()
    {
        $school = $this->school();
        [, $reg] = $this->registrasiLintasEvent($school);

        $komponen = Livewire::actingAs($this->admin())
            ->test(Show::class, ['npsn' => $school->npsn]);

        $komponen->call('deleteSchool');
        // Masih ada pendaftaran — baris sekolah harus ditahan.
        $this->assertDatabaseHas('schools', ['npsn' => $school->npsn]);

        $komponen->call('deleteRegistration', $reg->id);
        $komponen->call('deleteSchool');

        $this->assertDatabaseMissing('schools', ['npsn' => $school->npsn]);
    }
}

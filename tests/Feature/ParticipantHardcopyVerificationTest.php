<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Participant\Index as ParticipantIndex;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MemeriksaToast;
use Tests\TestCase;

class ParticipantHardcopyVerificationTest extends TestCase
{
    use RefreshDatabase, MemeriksaToast;

    private User $user;
    private Eventner $eventner;
    private CompetitionCategory $lomba;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);

        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $this->user->update(['eventner_id' => $this->eventner->id]);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LKBB Utama',
        ]);

        $this->lomba = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
            'name' => 'Tingkat SMA/SMK',
        ]);

        RegistrationField::ensureDefaults($this->eventner);
    }

    public function test_model_registration_hardcopy_accessors_and_relations(): void
    {
        $reg = Registration::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMK Taruna Bhakti',
            'no_hp' => '081298765432',
            'status_berkas' => 'Terverifikasi',
            'is_finalized' => true,
            'verification_method' => 'hardcopy',
            'verified_at' => now(),
            'verified_by' => $this->user->id,
            'verification_notes' => 'Surat tugas fisik & formulir basah diserahkan.',
        ]);

        $this->assertTrue($reg->isHardcopyVerified());
        $this->assertTrue($reg->is_hardcopy_verified);
        $this->assertEquals($this->user->id, $reg->verifiedBy->id);
        $this->assertEquals('Surat tugas fisik & formulir basah diserahkan.', $reg->verification_notes);

        // Jika belum Terverifikasi, is_hardcopy_verified harus false
        $reg->update(['status_berkas' => 'Menunggu']);
        $this->assertFalse($reg->fresh()->is_hardcopy_verified);
    }

    public function test_input_manual_dengan_centang_hardcopy_langsung_terverifikasi_dan_finalized(): void
    {
        Livewire::actingAs($this->user)
            ->test(ParticipantIndex::class)
            ->call('openModal', $this->lomba->id)
            ->set('nama_sekolah', 'SMA Negeri 1 Tangerang')
            ->set('npsn', '20601234')
            ->set('nama_pelatih', 'Kapt Inf Supriadi')
            ->set('no_hp', '081311223344')
            ->set('school_email', 'sman1tng@example.test')
            ->set('competition_category_id', $this->lomba->id)
            ->set('jumlah_pasukan', 1)
            ->set('directHardcopyVerify', true)
            ->set('verificationNotes', 'Berkas fisik diserahkan saat technical meeting')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'eventner_id' => $this->eventner->id,
            'nama_sekolah' => 'SMA Negeri 1 Tangerang',
            'status_berkas' => 'Terverifikasi',
            'is_finalized' => 1,
            'verification_method' => 'hardcopy',
            'verified_by' => $this->user->id,
            'verification_notes' => 'Berkas fisik diserahkan saat technical meeting',
        ]);

        $reg = Registration::where('nama_sekolah', 'SMA Negeri 1 Tangerang')->first();
        $this->assertNotNull($reg->verified_at);
        $this->assertTrue($reg->is_hardcopy_verified);
    }

    public function test_input_manual_tanpa_centang_hardcopy_tersimpan_sebagai_menunggu(): void
    {
        Livewire::actingAs($this->user)
            ->test(ParticipantIndex::class)
            ->call('openModal', $this->lomba->id)
            ->set('nama_sekolah', 'SMA Negeri 2 Tangerang')
            ->set('npsn', '20605678')
            ->set('nama_pelatih', 'Pelatih Ahmad')
            ->set('no_hp', '081355667788')
            ->set('school_email', 'sman2tng@example.test')
            ->set('competition_category_id', $this->lomba->id)
            ->set('jumlah_pasukan', 1)
            ->set('directHardcopyVerify', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registrations', [
            'eventner_id' => $this->eventner->id,
            'nama_sekolah' => 'SMA Negeri 2 Tangerang',
            'status_berkas' => 'Menunggu',
            'is_finalized' => 0,
            'verification_method' => 'digital',
            'verified_by' => null,
        ]);
    }

    public function test_verifikasi_biasa_ditolak_jika_peserta_masih_draft(): void
    {
        $reg = Registration::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMA Kartika',
            'no_hp' => '081234567890',
            'status_berkas' => 'Menunggu',
            'is_finalized' => false,
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(ParticipantIndex::class)
            ->call('openVerifyModal', $reg->id)
            ->call('verifyStatus', 'Terverifikasi');

        $this->assertAdaToast($test, 'masih draft');
        $this->assertEquals('Menunggu', $reg->fresh()->status_berkas);
    }

    public function test_panitia_dapat_acc_berkas_fisik_hardcopy_meski_peserta_belum_finalized(): void
    {
        $reg = Registration::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMA Kartika',
            'no_hp' => '081234567890',
            'status_berkas' => 'Menunggu',
            'is_finalized' => false,
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(ParticipantIndex::class)
            ->call('openVerifyModal', $reg->id)
            ->set('verificationNotes', 'Berkas fisik lengkap di meja sekretariat')
            ->call('verifyHardcopy');

        $this->assertAdaToast($test, 'berhasil diverifikasi via berkas fisik');

        $refreshed = $reg->fresh();
        $this->assertEquals('Terverifikasi', $refreshed->status_berkas);
        $this->assertTrue((bool) $refreshed->is_finalized);
        $this->assertEquals('hardcopy', $refreshed->verification_method);
        $this->assertEquals($this->user->id, $refreshed->verified_by);
        $this->assertEquals('Berkas fisik lengkap di meja sekretariat', $refreshed->verification_notes);
        $this->assertNotNull($refreshed->verified_at);
        $this->assertTrue($refreshed->is_hardcopy_verified);
    }

    public function test_portal_magic_link_dan_unduh_formulir_berfungsi_penuh_untuk_peserta_hardcopy(): void
    {
        $reg = Registration::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMK Bina Bangsa',
            'no_hp' => '081299887766',
            'status_berkas' => 'Terverifikasi',
            'is_finalized' => true,
            'verification_method' => 'hardcopy',
            'verified_at' => now(),
            'verified_by' => $this->user->id,
            'verification_notes' => 'Diperiksa fisik di meja panitia',
        ]);

        // 1. Cek halaman portal magic link
        $response = $this->get(route('magic.link', $reg->magic_token));
        $response->assertStatus(200);
        $response->assertSee('Terverifikasi (Hardcopy)');
        $response->assertSee('Berkas Fisik (Hardcopy) Terverifikasi di Meja Panitia');
        $response->assertSee('Diperiksa fisik di meja panitia');

        // 2. Cek download formulir PDF
        $pdfResponse = $this->get(route('magic.link.formulir', $reg->magic_token));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\EoInquiryIndex;
use App\Models\EoInquiry;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panel admin pengajuan EO.
 *
 * Yang dijaga: pengajuan yang masuk lewat /ajukan benar-benar bisa dikerjakan
 * admin — dilihat, ditandai dibalas, dan dicatat. Halaman EO menjanjikan
 * "kami balas hari kerja berikutnya"; janji itu hanya bisa ditepati kalau
 * daftar ini ada dan bisa dipakai.
 */
class AdminEoInquiryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['is_active' => true]);
    }

    private function pengajuan(array $atribut = []): EoInquiry
    {
        return EoInquiry::create(array_merge([
            'instansi' => 'SMAN 1 Berbaris',
            'penanggung_jawab' => 'Ibu Rani',
            'whatsapp' => '08123456789',
            'jenis_lomba' => 'LKBB',
            'status' => 'baru',
        ], $atribut));
    }

    public function test_admin_can_open_the_inquiry_list(): void
    {
        $this->pengajuan(['instansi' => 'SMPN 2 Tangguh']);

        $this->actingAs($this->admin)
            ->get(route('admin.eo-inquiries'))
            ->assertOk()
            ->assertSee('Pengajuan EO')
            ->assertSee('SMPN 2 Tangguh');
    }

    public function test_inquiry_list_is_guarded_for_non_admin(): void
    {
        $this->get(route('admin.eo-inquiries'))->assertRedirect();
    }

    public function test_marking_as_answered_sets_the_reply_timestamp(): void
    {
        $pengajuan = $this->pengajuan();

        $this->assertNull($pengajuan->dibalas_at);

        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->call('ubahStatus', $pengajuan->id, 'dibalas');

        $pengajuan->refresh();

        $this->assertSame('dibalas', $pengajuan->status);
        $this->assertNotNull($pengajuan->dibalas_at);
    }

    public function test_status_change_is_rejected_for_an_unknown_status(): void
    {
        $pengajuan = $this->pengajuan();

        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->call('ubahStatus', $pengajuan->id, 'entah-apa');

        $this->assertSame('baru', $pengajuan->fresh()->status);
    }

    public function test_admin_note_is_saved_from_the_detail_panel(): void
    {
        $pengajuan = $this->pengajuan();

        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->call('lihat', $pengajuan->id)
            ->assertSet('detailId', $pengajuan->id)
            ->set('catatan_admin', 'Sudah dihubungi 23 Okt.')
            ->call('simpanCatatan')
            ->assertHasNoErrors();

        $this->assertSame('Sudah dihubungi 23 Okt.', $pengajuan->fresh()->catatan_admin);
    }

    public function test_default_filter_shows_only_new_inquiries(): void
    {
        $this->pengajuan(['instansi' => 'Sekolah Baru', 'status' => 'baru']);
        $this->pengajuan(['instansi' => 'Sekolah Lama', 'status' => 'selesai']);

        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->assertSet('saring', 'baru')
            ->assertSee('Sekolah Baru')
            ->assertDontSee('Sekolah Lama');
    }

    public function test_search_matches_institution_and_responsible_person(): void
    {
        $this->pengajuan(['instansi' => 'SMPN 2 Tangguh']);
        $this->pengajuan(['instansi' => 'SMAN 1 Berbaris', 'penanggung_jawab' => 'Pak Bagas']);

        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->set('cari', 'Bagas')
            ->assertSee('SMAN 1 Berbaris')
            ->assertDontSee('SMPN 2 Tangguh');
    }

    public function test_deleting_an_inquiry_removes_it_and_closes_the_panel(): void
    {
        $pengajuan = $this->pengajuan();

        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->call('lihat', $pengajuan->id)
            ->call('hapus', $pengajuan->id)
            ->assertSet('detailId', null);

        $this->assertNull(EoInquiry::find($pengajuan->id));
    }

    /**
     * Kontak EO punya kunci sendiri. Kalau halaman ini keliru menulis
     * `landing_contact`, halaman EO akan tetap menampilkan kontak contoh.
     */
    public function test_saving_contact_writes_the_eo_keys_not_the_landing_ones(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->set('eo_nama', 'Tim Lomba Nusantara')
            ->set('eo_email', 'eo@contoh.id')
            ->set('eo_phone', '08123456789')
            ->call('simpanKontak')
            ->assertHasNoErrors();

        $this->assertSame('Tim Lomba Nusantara', Setting::get('eo_nama'));

        $kontak = json_decode(Setting::get('eo_contact'), true);
        $this->assertSame('eo@contoh.id', $kontak['email']);
        $this->assertSame('08123456789', $kontak['phone']);

        $this->assertNull(Setting::get('landing_contact'));
    }

    public function test_invalid_contact_email_is_rejected(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EoInquiryIndex::class)
            ->set('eo_email', 'bukan-surel')
            ->call('simpanKontak')
            ->assertHasErrors(['eo_email']);

        $this->assertNull(Setting::get('eo_contact'));
    }
}

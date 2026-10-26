<?php

namespace Tests\Feature;

use App\Livewire\Public\MagicLink\Registration as MagicLink;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gerbang formulir portal `/reg/{token}` selama masa booking.
 *
 * Aturannya satu: formulir terbuka setelah Technical Meeting lewat, ATAU lebih
 * awal kalau biaya pendaftaran pasukan itu sudah lunas. Yang dijaga di sini
 * adalah kesamaan antara yang dilihat pendaftar (view) dan yang ditegakkan
 * server (komponen Livewire + API) — beda pendapat antara keduanya berarti
 * tombol tampil tapi ditolak, atau sebaliknya.
 */
class PortalFormulirTerbukaTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Booking = deadline pendaftaran masih di depan DAN TM belum lewat.
        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'gerbang-portal',
            'subdomain' => null,
            'tanggal_pendaftaran' => now()->addDays(30)->format('Y-m-d'),
            'technical_meeting' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->category = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create();

        RegistrationField::ensureDefaults($this->eventner);
    }

    private function peserta(array $ganti = []): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create(array_merge([
            'competition_category_id' => $this->category->id,
            'status_berkas' => 'booking',
            'magic_token' => 'TOKEN-GERBANG-01',
        ], $ganti));
    }

    public function test_masa_booking_belum_lunas_dan_tm_belum_lewat_menahan_formulir()
    {
        $reg = $this->peserta(['payment_status' => 'unpaid']);

        $this->assertSame('booking', $reg->eventner->fresh()->registration_status);
        $this->assertFalse($reg->formulirTerbuka());

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->assertDontSee('Simpan Draft')
            ->assertSee('Konfirmasi pendaftaran baru dibuka setelah Technical Meeting');
    }

    /** Pendaftaran lunas membuka formulir lebih awal, tanpa menunggu TM. */
    public function test_sudah_lunas_membuka_formulir_sebelum_tm()
    {
        $reg = $this->peserta(['payment_status' => 'paid']);

        $this->assertSame('booking', $reg->eventner->fresh()->registration_status);
        $this->assertTrue($reg->formulirTerbuka());

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->assertSee('Simpan Draft')
            ->assertSee('Pembayaran pendaftaran sudah lunas')
            ->assertDontSee('Konfirmasi pendaftaran baru dibuka setelah Technical Meeting');
    }

    /**
     * "Sudah membayar" berarti sudah diverifikasi lunas, bukan baru mengunggah
     * bukti. Bukti transfer yang belum diperiksa panitia tidak membuka gerbang.
     */
    public function test_bukti_bayar_belum_diverifikasi_tidak_membuka_formulir()
    {
        $reg = $this->peserta(['payment_status' => 'pending_verification']);

        $this->assertFalse($reg->formulirTerbuka());

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->assertDontSee('Simpan Draft');
    }

    /** TM lewat membuka formulir walau pendaftaran belum dibayar. */
    public function test_tm_lewat_membuka_formulir_tanpa_pembayaran()
    {
        $this->eventner->update(['technical_meeting' => now()->subDay()]);

        $reg = $this->peserta(['payment_status' => 'unpaid']);

        $this->assertTrue($reg->formulirTerbuka());

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->assertSee('Simpan Draft');
    }

    /**
     * Gerbang baca dan gerbang tulis harus sama: kalau formulirnya tampil
     * karena lunas, menyimpan section juga harus diterima.
     */
    public function test_lunas_boleh_menulis_section_sebelum_tm()
    {
        $reg = $this->peserta(['payment_status' => 'paid']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'danton')
            ->set('dantonNama', 'Danton Lunas')
            ->call('simpanSeksi');

        $this->assertSame('Danton Lunas', $reg->fresh()->danton_nama);
    }

    /** Tanpa lunas dan sebelum TM, jalur tulis tetap tertutup. */
    public function test_belum_lunas_tidak_boleh_menulis_section_sebelum_tm()
    {
        $reg = $this->peserta(['payment_status' => 'unpaid']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'danton')
            ->set('dantonNama', 'Danton Ditolak')
            ->call('simpanSeksi')
            ->assertSet('modalSeksi', 'danton');

        $this->assertNull($reg->fresh()->danton_nama);
    }

    /** Konfirmasi & Kirim ikut terbuka lebih awal saat sudah lunas. */
    public function test_konfirmasi_diterima_saat_lunas_sebelum_tm()
    {
        $reg = $this->peserta(['payment_status' => 'paid']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('dantonNama', 'Danton Lunas')
            ->call('confirm');

        $this->assertSame('confirmed', $reg->fresh()->status_berkas);
    }

    /** Sebelum TM dan belum lunas, konfirmasi ditolak server. */
    public function test_konfirmasi_ditolak_saat_belum_lunas_sebelum_tm()
    {
        $reg = $this->peserta(['payment_status' => 'unpaid']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('dantonNama', 'Danton Ditolak')
            ->call('confirm');

        $this->assertSame('booking', $reg->fresh()->status_berkas);
    }

    /** Status berkas selain booking tidak pernah ditahan gerbang TM. */
    public function test_status_selain_booking_tidak_ditahan_gerbang()
    {
        $reg = $this->peserta(['status_berkas' => 'confirmed', 'payment_status' => 'unpaid']);

        $this->assertTrue($reg->formulirTerbuka());
    }
}

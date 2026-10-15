<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Document\Index;
use App\Models\EventDocument;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Juknis & dokumen unggahan panitia.
 *
 * Berkas panitia disajikan lewat response()->download() (bukan asset storage),
 * dan dikurung eventner_id milik pengguna yang login — dua hal itulah yang
 * diuji di sini bersama alur unggah/hapus.
 */
class EventDocumentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Eventner} */
    private function panitia(array $atributEvent = []): array
    {
        $user = User::factory()->eventner()->create();
        $eventner = Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
            // Tanpa subdomain: halaman publik dan rute unduhan diakses lewat
            // /event/{slug}; event ber-subdomain punya jalur sendiri.
            'subdomain' => null,
        ], $atributEvent));

        return [$user, $eventner];
    }

    public function test_panitia_dapat_mengunggah_dokumen()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(Index::class)
            ->set('title', 'Juknis PBB Umum')
            ->set('description', 'Aturan dasar PBB.')
            ->set('newFile', UploadedFile::fake()->create('juknis-pbb.pdf', 500, 'application/pdf'))
            ->call('unggahDokumen')
            ->assertHasNoErrors();

        $doc = EventDocument::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('Juknis PBB Umum', $doc->title);
        $this->assertSame('juknis-pbb.pdf', $doc->original_name);
        $this->assertTrue($doc->is_primary, 'Dokumen pertama harus otomatis jadi utama.');
        Storage::disk('public')->assertExists($doc->file);
    }

    public function test_panel_menampilkan_dokumen_dan_tombol_aksinya()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $doc = EventDocument::factory()->create([
            'eventner_id' => $eventner->id,
            'title' => 'Juknis Variasi Formasi',
            'original_name' => 'variasi-2026.pdf',
            'is_primary' => false,
        ]);

        Livewire::actingAs($user)->test(Index::class)
            ->assertSee('Juknis Variasi Formasi')
            ->assertSee('variasi-2026.pdf')
            ->assertSeeHtml("wire:click=\"delete({$doc->id})\"")
            ->assertSeeHtml("wire:click=\"jadikanUtama({$doc->id})\"")
            ->assertSeeHtml("wire:click=\"naikkan({$doc->id})\"")
            ->assertSeeHtml("wire:click=\"mulaiGantiBerkas({$doc->id})\"");
    }

    public function test_panel_menampilkan_keadaan_kosong()
    {
        Storage::fake('public');
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(Index::class)
            ->assertSee('Belum ada dokumen.')
            ->assertSee('Unggah Dokumen');
    }

    /**
     * Berkas non-gambar tidak punya pratinjau, jadi satu-satunya bukti bahwa
     * berkasnya sudah mendarat di livewire-tmp adalah baris nama + ukuran ini.
     * Tanpa itu layar tampak persis seperti sebelum berkas dipilih.
     */
    public function test_berkas_bukan_gambar_ditandai_siap_diunggah()
    {
        Storage::fake('public');
        [$user] = $this->panitia();

        $komponen = Livewire::actingAs($user)->test(Index::class);

        $komponen->set('newFile', UploadedFile::fake()->create('juknis-pbb.pdf', 500, 'application/pdf'))
            ->assertSee('juknis-pbb.pdf')
            ->assertSee('siap diunggah')
            ->assertDontSee('Pratinjau berkas');

        // Gambar tetap memakai pratinjau, bukan baris penanda.
        $komponen->set('newFile', UploadedFile::fake()->image('poster.jpg'))
            ->assertSee('Pratinjau berkas')
            ->assertDontSee('siap diunggah');
    }

    public function test_halaman_panel_dapat_dibuka_panitia()
    {
        [$user] = $this->panitia();

        $this->actingAs($user)
            ->get(route('eventner.documents.index'))
            ->assertOk();
    }

    public function test_dokumen_kedua_tidak_mengambil_flag_utama()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $komponen = Livewire::actingAs($user)->test(Index::class);

        $komponen->set('title', 'Juknis A')
            ->set('newFile', UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'))
            ->call('unggahDokumen');

        $komponen->set('title', 'Juknis B')
            ->set('newFile', UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'))
            ->call('unggahDokumen');

        $this->assertSame(
            1,
            EventDocument::where('eventner_id', $eventner->id)->where('is_primary', true)->count()
        );
        $this->assertSame('Juknis A', EventDocument::where('is_primary', true)->firstOrFail()->title);
    }

    public function test_jenis_berkas_di_luar_daftar_ditolak()
    {
        Storage::fake('public');
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(Index::class)
            ->set('title', 'Berkas Berbahaya')
            ->set('newFile', UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))
            ->call('unggahDokumen')
            ->assertHasErrors(['newFile']);

        $this->assertSame(0, EventDocument::count());
    }

    public function test_berkas_lebih_dari_sepuluh_mega_ditolak()
    {
        Storage::fake('public');
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(Index::class)
            ->set('title', 'Terlalu Besar')
            ->set('newFile', UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf'))
            ->call('unggahDokumen')
            ->assertHasErrors(['newFile']);

        $this->assertSame(0, EventDocument::count());
    }

    public function test_dokumen_event_lain_tidak_bisa_dihapus()
    {
        Storage::fake('public');
        [$user] = $this->panitia();
        [, $eventLain] = $this->panitia();

        $milikOrangLain = EventDocument::factory()->create(['eventner_id' => $eventLain->id]);

        // findOrFail() pada query yang sudah dikurung eventner_id milik
        // pengguna login: dokumen event lain tidak ditemukan, dan barisnya
        // tidak tersentuh.
        $this->expectException(ModelNotFoundException::class);

        try {
            Livewire::actingAs($user)->test(Index::class)->call('delete', $milikOrangLain->id);
        } finally {
            $this->assertDatabaseHas('event_documents', ['id' => $milikOrangLain->id]);
        }
    }

    public function test_menghapus_dokumen_ikut_menghapus_berkasnya()
    {
        Storage::fake('public');
        [$user] = $this->panitia();

        $file = UploadedFile::fake()->create('juknis.pdf', 200, 'application/pdf');
        $path = $file->store('documents', 'public');

        $doc = EventDocument::factory()->create([
            'eventner_id' => Eventner::firstOrFail()->id,
            'file' => $path,
            'is_primary' => false,
        ]);

        Storage::disk('public')->assertExists($path);

        Livewire::actingAs($user)->test(Index::class)->call('delete', $doc->id);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('event_documents', ['id' => $doc->id]);
    }

    public function test_menghapus_dokumen_utama_mengalihkan_flag_ke_dokumen_tersisa()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $utama = EventDocument::factory()->create(['eventner_id' => $eventner->id, 'is_primary' => true, 'sort_order' => 1]);
        $kedua = EventDocument::factory()->create(['eventner_id' => $eventner->id, 'is_primary' => false, 'sort_order' => 2]);

        Livewire::actingAs($user)->test(Index::class)->call('delete', $utama->id);

        $this->assertTrue($kedua->fresh()->is_primary, 'Event tidak boleh ditinggal tanpa dokumen utama.');
    }

    public function test_jadikan_utama_hanya_menyisakan_satu_flag()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        EventDocument::factory()->create(['eventner_id' => $eventner->id, 'is_primary' => true, 'sort_order' => 1]);
        $kedua = EventDocument::factory()->create(['eventner_id' => $eventner->id, 'is_primary' => false, 'sort_order' => 2]);

        Livewire::actingAs($user)->test(Index::class)->call('jadikanUtama', $kedua->id);

        $this->assertSame(1, EventDocument::where('eventner_id', $eventner->id)->where('is_primary', true)->count());
        $this->assertTrue($kedua->fresh()->is_primary);
    }

    public function test_mengganti_berkas_menghapus_berkas_lama()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $lama = UploadedFile::fake()->create('lama.pdf', 200, 'application/pdf')->store('documents', 'public');

        $doc = EventDocument::factory()->create([
            'eventner_id' => $eventner->id,
            'file' => $lama,
            'original_name' => 'lama.pdf',
        ]);

        Livewire::actingAs($user)->test(Index::class)
            ->call('mulaiGantiBerkas', $doc->id)
            ->set('replacementFile', UploadedFile::fake()->create('baru.pdf', 300, 'application/pdf'))
            ->call('gantiBerkas')
            ->assertHasNoErrors();

        $doc->refresh();

        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($doc->file);
        $this->assertSame('baru.pdf', $doc->original_name);
    }

    public function test_landing_menampilkan_dokumen_dan_hilang_saat_kosong()
    {
        Storage::fake('public');
        [, $eventner] = $this->panitia();

        $tanpa = $this->get("/event/{$eventner->slug}");
        $tanpa->assertOk();
        $tanpa->assertDontSee('Juknis &amp; Dokumen', false);

        $doc = EventDocument::factory()->create([
            'eventner_id' => $eventner->id,
            'title' => 'Juknis Variasi Formasi',
            'is_primary' => true,
        ]);

        $dengan = $this->get("/event/{$eventner->slug}");
        $dengan->assertOk();
        $dengan->assertSee('Juknis Variasi Formasi');
        $dengan->assertSee("/event/{$eventner->slug}/dokumen/{$doc->id}", false);
    }

    public function test_unduhan_mengirim_lampiran_dan_menambah_penghitung()
    {
        Storage::fake('public');
        [, $eventner] = $this->panitia();

        $path = UploadedFile::fake()->create('berkas.pdf', 120, 'application/pdf')->store('documents', 'public');

        $doc = EventDocument::factory()->create([
            'eventner_id' => $eventner->id,
            'file' => $path,
            'original_name' => 'Juknis Resmi 2026.pdf',
            'download_count' => 0,
        ]);

        $respons = $this->get("/event/{$eventner->slug}/dokumen/{$doc->id}");

        $respons->assertOk();
        $this->assertStringContainsString(
            'attachment',
            $respons->headers->get('content-disposition')
        );
        $this->assertSame(1, $doc->fresh()->download_count);
    }

    public function test_unduhan_ditolak_kalau_berkasnya_hilang()
    {
        Storage::fake('public');
        [, $eventner] = $this->panitia();

        $doc = EventDocument::factory()->create([
            'eventner_id' => $eventner->id,
            'file' => 'documents/tidak-ada.pdf',
        ]);

        $this->get("/event/{$eventner->slug}/dokumen/{$doc->id}")->assertNotFound();
    }

    public function test_dokumen_event_belum_disetujui_tidak_bisa_diunduh()
    {
        Storage::fake('public');
        [, $eventner] = $this->panitia(['status' => 'pending']);

        $path = UploadedFile::fake()->create('berkas.pdf', 120, 'application/pdf')->store('documents', 'public');

        $doc = EventDocument::factory()->create([
            'eventner_id' => $eventner->id,
            'file' => $path,
        ]);

        $this->get("/event/{$eventner->slug}/dokumen/{$doc->id}")->assertNotFound();
    }

    public function test_nama_unduhan_dibersihkan_dari_karakter_path()
    {
        $doc = new EventDocument([
            'title' => 'Juknis',
            'file' => 'documents/x.pdf',
            'original_name' => '../../etc/passwd.pdf',
        ]);

        $this->assertStringNotContainsString('/', $doc->nama_unduhan);
        $this->assertStringNotContainsString('\\', $doc->nama_unduhan);
    }

    public function test_juknis_rubrik_lama_tetap_berperilaku_sama()
    {
        [, $eventner] = $this->panitia();

        // Tanpa rubrik penilaian, rute juknis lama tetap 404 — perilaku yang
        // sudah ada sebelum fitur dokumen unggahan masuk.
        $this->get("/event/{$eventner->slug}/juknis")->assertNotFound();
    }
}

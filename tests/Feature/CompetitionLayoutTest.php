<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Layout\Index as LayoutIndex;
use App\Livewire\Eventner\Layout\Show as LayoutShow;
use App\Models\CompetitionCategory;
use App\Models\CompetitionLayout;
use App\Models\Eventner;
use App\Models\LayoutZone;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Denah lapangan: CRUD, guard tenant, dan gerbang terbit.
 *
 * Denah sengaja tidak menyimpan peserta/sekolah — tes di berkas ini karena itu
 * juga menjaga ketiadaan itu: tak ada kolom yang menunjuk pendaftaran.
 */
class CompetitionLayoutTest extends TestCase
{
    use RefreshDatabase;

    /** User::factory()->eventner() menyetel role='Eventner'; tanpa itu
     *  middleware role:Eventner menolak dengan 403 sebelum komponennya jalan. */
    private function panitia(array $atributEventner = []): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
        ], $atributEventner));

        return [$user, $eventner];
    }

    /**
     * Paket berbayar — fitur `layout` terkunci untuk paket gratis; tes di sini
     * menguji denahnya, bukan gerbang paketnya.
     */
    private function sebagai(User $user): void
    {
        $user->eventner->update(['plan' => 'paid', 'trial_ends_at' => null]);

        $this->actingAs($user->fresh());
    }

    public function test_daftar_denah_hanya_berisi_milik_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();
        $lain = CompetitionLayout::factory()->create();

        CompetitionLayout::factory()->create(['eventner_id' => $eventner->id, 'name' => 'Denah Saya']);

        $this->sebagai($user);

        $this->get(route('eventner.denah.index'))
            ->assertOk()
            ->assertSee('Denah Saya')
            ->assertDontSee($lain->name);
    }

    public function test_denah_baru_tersimpan_dengan_ukuran_papan()
    {
        [$user, $eventner] = $this->panitia();

        $this->sebagai($user);

        Livewire::test(LayoutIndex::class)
            ->call('create')
            ->set('name', 'Denah PBB SD')
            ->set('ground_width', '40')
            ->set('ground_height', '22')
            ->call('simpan')
            ->assertHasNoErrors();

        $denah = CompetitionLayout::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('Denah PBB SD', $denah->name);
        $this->assertSame('40.00', $denah->ground_width);
        $this->assertFalse($denah->is_published);
    }

    /**
     * Kategori milik event lain tak boleh lolos lewat request langsung.
     */
    public function test_denah_ditolak_menempel_ke_kategori_event_lain()
    {
        [$user, $eventner] = $this->panitia();
        $kategoriLain = CompetitionCategory::factory()->create();

        $this->sebagai($user);

        Livewire::test(LayoutIndex::class)
            ->call('create')
            ->set('name', 'Denah Nyasar')
            ->set('competition_category_id', (string) $kategoriLain->id)
            ->call('simpan');

        $denah = CompetitionLayout::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertNull(
            $denah->competition_category_id,
            'Kategori milik event lain harus ditolak, bukan ditempelkan.'
        );
    }

    public function test_kategori_sendiri_boleh_ditempelkan()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);

        $this->sebagai($user);

        Livewire::test(LayoutIndex::class)
            ->call('create')
            ->set('name', 'Denah PBB')
            ->set('competition_category_id', (string) $kategori->id)
            ->call('simpan');

        $this->assertSame(
            $kategori->id,
            CompetitionLayout::where('eventner_id', $eventner->id)->firstOrFail()->competition_category_id
        );
    }

    /** Denah milik event lain tidak bisa dibuka, diubah, maupun dihapus. */
    public function test_denah_event_lain_tidak_bisa_disunting()
    {
        [$user] = $this->panitia();
        $asing = CompetitionLayout::factory()->create();

        $this->sebagai($user);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(LayoutIndex::class)->call('edit', $asing->id);
    }

    public function test_denah_event_lain_tidak_bisa_dihapus()
    {
        [$user] = $this->panitia();
        $asing = CompetitionLayout::factory()->create();

        $this->sebagai($user);

        try {
            Livewire::test(LayoutIndex::class)->call('delete', $asing->id);
        } catch (ModelNotFoundException) {
            // yang diharapkan
        }

        $this->assertDatabaseHas('competition_layouts', ['id' => $asing->id]);
    }

    /** Editor denah event lain harus 404, bukan terbuka. */
    public function test_editor_denah_event_lain_ditolak()
    {
        [$user] = $this->panitia();
        $asing = CompetitionLayout::factory()->create();

        $this->sebagai($user);

        $this->get(route('eventner.denah.show', $asing->id))->assertNotFound();
    }

    public function test_hapus_denah_ikut_menghapus_zonanya()
    {
        [$user, $eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->sebagai($user);

        Livewire::test(LayoutIndex::class)->call('delete', $denah->id);

        $this->assertDatabaseMissing('competition_layouts', ['id' => $denah->id]);
        $this->assertDatabaseMissing('layout_zones', ['id' => $zona->id]);
    }

    /**
     * Denah kosong tak boleh diterbitkan.
     *
     * Denah hampa yang terbit tampil sebagai kanvas kosong tanpa penjelasan di
     * halaman publik — peserta menyimpulkan halamannya rusak.
     */
    public function test_denah_kosong_tidak_bisa_diterbitkan()
    {
        [$user, $eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);

        $this->sebagai($user);

        Livewire::test(LayoutIndex::class)->call('terbitkan', $denah->id);

        $this->assertFalse($denah->fresh()->is_published);
    }

    public function test_denah_berisi_bisa_diterbitkan_dan_ditarik()
    {
        [$user, $eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->sebagai($user);

        Livewire::test(LayoutIndex::class)->call('terbitkan', $denah->id);
        $this->assertTrue($denah->fresh()->is_published);
        $this->assertNotNull($denah->fresh()->published_at);

        Livewire::test(LayoutIndex::class)->call('tarik', $denah->id);
        $this->assertFalse($denah->fresh()->is_published);
    }

    /**
     * Fitur terkunci untuk paket gratis.
     *
     * Gerbangnya ada di mount(): halaman daftar maupun editor harus mengalihkan
     * ke /upgrade. Trial harus sudah berakhir — trial aktif membuka semua fitur.
     */
    public function test_paket_gratis_dialihkan_ke_upgrade()
    {
        [$user] = $this->panitia(['plan' => 'free', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($user);

        $this->get(route('eventner.denah.index'))
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    public function test_editor_juga_mengalihkan_paket_gratis_ke_upgrade()
    {
        [$user, $eventner] = $this->panitia(['plan' => 'free', 'trial_ends_at' => now()->subDay()]);
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);

        $this->actingAs($user);

        $this->get(route('eventner.denah.show', $denah->id))
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    /** PDF juga ditolak untuk paket gratis — bukan cuma halamannya. */
    public function test_pdf_denah_ditolak_paket_gratis()
    {
        [$user, $eventner] = $this->panitia(['plan' => 'free', 'trial_ends_at' => now()->subDay()]);
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);

        $this->actingAs($user);

        $this->get(route('eventner.denah.pdf', $denah->id))->assertForbidden();
    }

    public function test_pdf_denah_milik_event_lain_tidak_bisa_diunduh()
    {
        [$user] = $this->panitia();
        $asing = CompetitionLayout::factory()->create();

        $this->sebagai($user);

        $this->get(route('eventner.denah.pdf', $asing->id))->assertNotFound();
    }

    /**
     * Nama tingkat lomba harus benar-benar tercetak sebagai isi <option>.
     *
     * Accessor-nya `full_name`; ditulis `nama_lengkap`, Blade diam saja dan
     * menghasilkan <option value="9"></option> — pilihan yang ada tapi hampa.
     *
     * Diperiksa dari isi tag-nya, bukan assertSee: nama kategorinya juga muncul
     * di placeholder, jadi assertSee tetap lulus walau option-nya kosong.
     */
    public function test_nama_tingkat_lomba_tercetak_di_form()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'Tingkat SD']);

        $this->sebagai($user);

        $html = Livewire::test(LayoutIndex::class)->call('create')->html();

        $this->assertStringContainsString(
            '<option value="'.$kategori->id.'">Tingkat SD</option>',
            $html,
            'Option tingkat lomba harus berisi namanya, bukan kosong.'
        );
    }

    /** Daftar denah juga menyebut tingkat lombanya, bukan cuma namanya. */
    public function test_kolom_tingkat_lomba_terisi_di_daftar()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'Tingkat SMP']);
        CompetitionLayout::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        $this->sebagai($user);

        $this->get(route('eventner.denah.index'))->assertOk()->assertSee('Tingkat SMP');
    }

    /** Katalog fitur & daftar gerbang harus memuat `layout` di kedua sisi. */
    public function test_fitur_layout_terdaftar_di_katalog_dan_gerbang()
    {
        $this->assertArrayHasKey('layout', config('eventner_features'));

        $gerbang = collect(\App\Support\ProductFeatures::groups())
            ->flatMap(fn ($grup) => collect($grup['items'])->flatMap(fn ($item) => $item['gates'] ?? []))
            ->unique()
            ->values()
            ->all();

        $this->assertContains('layout', $gerbang);

        // Katalog menautkan route ini; kalau namanya salah, halaman /fitur
        // melempar RouteNotFoundException saat dibuka.
        $this->assertTrue(Route::has('eventner.denah.index'));
    }
}

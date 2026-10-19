<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Layout\Show as LayoutShow;
use App\Models\CompetitionLayout;
use App\Models\Eventner;
use App\Models\LayoutZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kunci denah: menahan gambar yang sudah pas supaya tak tergeser.
 *
 * Yang dijaga berkas ini dua hal. Pertama, kuncinya MENAHAN — bukan cuma
 * mengubah rupa tombolnya, karena denah yang tampak terkunci tapi tetap bisa
 * diseret lebih buruk daripada tak ada kunci sama sekali. Kedua, kuncinya
 * benar-benar lepas lagi; kunci yang tak bisa dibuka cuma membuat orang
 * berhenti memakainya.
 */
class LayoutKunciTest extends TestCase
{
    use RefreshDatabase;

    private function editor(array $atributLayout = []): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'paid',
            'trial_ends_at' => null,
        ]);
        $denah = CompetitionLayout::factory()->create(array_merge(
            ['eventner_id' => $eventner->id], $atributLayout
        ));

        $this->actingAs($user);

        return [$denah, Livewire::test(LayoutShow::class, ['layout' => $denah])];
    }

    /** Denah baru belum terkunci — kuncinya harus dinyalakan dulu. */
    public function test_denah_baru_belum_terkunci()
    {
        [$denah] = $this->editor();

        $this->assertFalse($denah->is_locked);
    }

    public function test_tombol_kunci_menyalakan_dan_mematikan()
    {
        [$denah, $editor] = $this->editor();

        $editor->call('toggleKunci');
        $this->assertTrue($denah->fresh()->is_locked);

        $editor->call('toggleKunci');
        $this->assertFalse($denah->fresh()->is_locked);
    }

    /**
     * Kuncinya tersimpan di denahnya, bukan di sesi editor.
     *
     * Kalau dikunci hanya di sesi, memuat ulang halaman membukanya kembali —
     * dan denah yang "terkunci" tetap tergeser oleh orang berikutnya yang
     * membukanya.
     */
    public function test_kunci_bertahan_setelah_komponen_dibuka_ulang()
    {
        [$denah] = $this->editor(['is_locked' => true]);

        $editor = Livewire::test(LayoutShow::class, ['layout' => $denah->fresh()]);

        $editor->assertSet('layout.is_locked', true);
        $editor->assertSee('Buka Kunci');
    }

    // ── Yang ditahan ───────────────────────────────────────────────────────

    public function test_seret_zona_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'x' => 10,
            'y' => 10,
        ]);

        $editor->call('simpanPosisi', $zona->id, 60, 60);

        $this->assertSame('10.000', (string) $zona->fresh()->x);
        $this->assertSame('10.000', (string) $zona->fresh()->y);
    }

    public function test_ubah_ukuran_lewat_seret_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'w' => 20,
            'h' => 20,
        ]);

        $editor->call('simpanPosisi', $zona->id, 10, 10, 55, 55);

        $this->assertSame('20.000', (string) $zona->fresh()->w);
    }

    public function test_tambah_zona_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);

        $editor->call('tambahZona', LayoutZone::TYPE_ARENA);

        $this->assertSame(0, $denah->zones()->count());
    }

    public function test_hapus_zona_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $editor->call('hapusZona', $zona->id);

        $this->assertDatabaseHas('layout_zones', ['id' => $zona->id]);
    }

    public function test_duplikat_zona_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $editor->call('duplikatZona', $zona->id);

        $this->assertSame(1, $denah->zones()->count());
    }

    public function test_ubah_ukuran_papan_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor([
            'is_locked' => true,
            'ground_width' => 40,
            'ground_height' => 22,
        ]);

        $editor->set('papanLebar', '60')->set('papanTinggi', '30')->call('simpanPapan');

        $this->assertSame('40.00', (string) $denah->fresh()->ground_width);
    }

    public function test_susun_dp_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);

        $editor->call('susunDp');

        $this->assertSame(0, $denah->zones()->count());
    }

    public function test_ubah_sifat_zona_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Arena Utama',
        ]);

        $editor->call('pilihZona', $zona->id);
        $editor->set('nama', 'Nama Baru')->call('simpanZona');

        $this->assertSame('Arena Utama', $zona->fresh()->name);
    }

    public function test_seret_titik_alur_ditolak_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);

        $denah->update(['route_points' => [
            ['x' => 10, 'y' => 80, 'kind' => 'start', 'label' => 'Mulai'],
            ['x' => 40, 'y' => 40, 'kind' => 'end', 'label' => 'Selesai'],
        ]]);

        $editor->call('simpanTitik', 0, 90, 90);

        $this->assertSame(10.0, $denah->fresh()->alur_titik[0]['x']);
    }

    // ── Yang sengaja TIDAK ditahan ─────────────────────────────────────────

    /**
     * Terbitkan & tarik tetap jalan di denah terkunci.
     *
     * Keduanya mengatur apa yang dilihat publik, bukan menggambar ulang
     * denahnya — dan menahan terbitan justru memaksa orang membuka kunci
     * hanya untuk menyiarkan denah yang sudah pas.
     */
    public function test_terbitkan_tetap_jalan_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $editor->call('terbitkan');

        $this->assertTrue($denah->fresh()->is_published);
    }

    /** Memilih zona tetap boleh: denah terkunci yang tak bisa dibaca sama saja
     *  dengan denah yang tak bisa dibuka. */
    public function test_memilih_zona_tetap_jalan_saat_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Tribun Utama',
        ]);

        $editor->call('pilihZona', $zona->id)->assertSet('selectedId', $zona->id);

        $editor->assertSee('Tribun Utama');
    }

    // ── Kanvas ─────────────────────────────────────────────────────────────

    /**
     * Kanvas terkunci ditandai, dan pegangan ubah ukurannya tak digambar.
     *
     * Yang diperiksa `class="zona-hnd"` dengan tanda kutipnya, bukan
     * `zona-hnd` saja: blok gaya kanvas memuat selektornya juga, dan nama
     * telanjang itu akan lulus walaupun span-nya sudah tidak ada.
     */
    public function test_kanvas_terkunci_tak_menggambar_pegangan_ubah_ukuran()
    {
        [$denah, $editor] = $this->editor();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $editor->call('$refresh')
            ->assertSee('data-denah-terkunci="0"', false)
            ->assertSee('class="zona-hnd"', false);

        [$denahKunci, $terkunci] = $this->editor(['is_locked' => true]);
        LayoutZone::factory()->create(['competition_layout_id' => $denahKunci->id]);

        $terkunci->call('$refresh')
            ->assertSee('data-denah-terkunci="1"', false)
            ->assertDontSee('class="zona-hnd"', false);
    }

    /** Kotak tetap bisa dipilih di kanvas terkunci — kalau tidak, denahnya
     *  cuma gambar yang tak bisa dibaca isinya. */
    public function test_kanvas_terkunci_tetap_membawa_data_zona_untuk_dipilih()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Tribun Utama',
        ]);

        $editor->call('$refresh')
            ->assertSee('data-zona="'.$zona->id.'"', false)
            ->assertSee('Tribun Utama');
    }

    /** Halaman publik tak pernah terkunci — pembacanya memang tak menggambar. */
    public function test_halaman_publik_tak_membawa_penanda_terkunci()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $denah = CompetitionLayout::factory()->terbit()->create([
            'eventner_id' => $eventner->id,
            'is_locked' => true,
        ]);
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Tribun Utama',
        ]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertDontSee('data-denah-terkunci', false);
    }
}

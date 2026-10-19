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
 * Kunci per zona: menahan SATU kotak tanpa membekukan seluruh denah.
 *
 * Bedanya dengan kunci denah bukan cuma cakupannya, tapi juga arah
 * kerjanya: kunci denah dipakai saat gambarnya sudah selesai, kunci zona
 * dipakai saat sebagiannya sudah pas dan sebagiannya belum. Karena itu yang
 * paling penting dijaga di sini adalah zona lain TETAP bisa digeser selama
 * satu zona terkunci — kunci yang menjalar ke seluruh denah sama saja dengan
 * kunci denah, dan tak menjawab kebutuhan yang membuatnya ada.
 */
class LayoutKunciZonaTest extends TestCase
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

    private function zona(int $denahId, array $atribut = []): LayoutZone
    {
        return LayoutZone::factory()->create(array_merge(
            ['competition_layout_id' => $denahId], $atribut
        ));
    }

    /** Zona baru belum terkunci — kuncinya harus dinyalakan dulu. */
    public function test_zona_baru_belum_terkunci()
    {
        [$denah] = $this->editor();
        $zona = $this->zona($denah->id);

        $this->assertFalse($zona->is_locked);
    }

    public function test_tombol_kunci_zona_menyalakan_dan_mematikan()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id);

        $editor->call('toggleKunciZona', $zona->id);
        $this->assertTrue($zona->fresh()->is_locked);

        $editor->call('toggleKunciZona', $zona->id);
        $this->assertFalse($zona->fresh()->is_locked);
    }

    /** Kuncinya di baris zonanya, bukan di sesi editor: memuat ulang halaman
     *  tak boleh membukanya kembali. */
    public function test_kunci_zona_bertahan_setelah_komponen_dibuka_ulang()
    {
        [$denah] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true]);

        $editor = Livewire::test(LayoutShow::class, ['layout' => $denah->fresh()]);

        $editor->call('pilihZona', $zona->id)
            ->assertSee('Buka Kunci Zona');
    }

    // ── Yang ditahan per zona ──────────────────────────────────────────────

    public function test_seret_zona_terkunci_ditolak()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true, 'x' => 10, 'y' => 10]);

        $editor->call('simpanPosisi', $zona->id, 60, 60);

        $this->assertSame('10.000', (string) $zona->fresh()->x);
        $this->assertSame('10.000', (string) $zona->fresh()->y);
    }

    public function test_ubah_ukuran_lewat_seret_zona_terkunci_ditolak()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true, 'w' => 20, 'h' => 20]);

        $editor->call('simpanPosisi', $zona->id, 10, 10, 55, 55);

        $this->assertSame('20.000', (string) $zona->fresh()->w);
        $this->assertSame('20.000', (string) $zona->fresh()->h);
    }

    public function test_ubah_sifat_zona_terkunci_ditolak()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true, 'name' => 'Arena Utama']);

        $editor->call('pilihZona', $zona->id);
        $editor->set('nama', 'Nama Baru')->call('simpanZona');

        $this->assertSame('Arena Utama', $zona->fresh()->name);
    }

    /** Seretan keliru masih bisa ditarik balik; zona yang terhapus tidak. */
    public function test_hapus_zona_terkunci_ditolak()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true]);

        $editor->call('hapusZona', $zona->id);

        $this->assertDatabaseHas('layout_zones', ['id' => $zona->id]);
    }

    public function test_duplikat_zona_terkunci_ditolak()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true]);

        $editor->call('duplikatZona', $zona->id);

        $this->assertSame(1, $denah->zones()->count());
    }

    /**
     * Susun DP membuang DP lama — termasuk yang terkunci.
     *
     * Idempotennya justru yang membuatnya berbahaya di sini: ia menghapus
     * seluruh barisan DP lalu menegakkannya ulang, sehingga kotak DP yang
     * sengaja dilindungi ikut lenyap dan digantikan yang bukan bikinan orangnya.
     */
    public function test_susun_dp_ditolak_saat_ada_dp_terkunci()
    {
        [$denah, $editor] = $this->editor();
        $this->zona($denah->id, ['is_locked' => true]);
        $this->zona($denah->id, ['type' => LayoutZone::TYPE_DP, 'dp_number' => 1, 'is_locked' => true]);

        $editor->call('susunDp');

        $this->assertSame(2, $denah->zones()->count());
    }

    /** Susun DP tetap jalan kalau tak ada DP terkunci — pagarnya cuma untuk
     *  zona yang memang dilindungi. */
    public function test_susun_dp_tetap_jalan_tanpa_dp_terkunci()
    {
        [$denah, $editor] = $this->editor();
        $this->zona($denah->id, ['is_locked' => true, 'type' => LayoutZone::TYPE_ARENA]);

        $editor->set('dpJumlah', '4')->set('dpTinggi', '4')->set('dpLetak', 'bawah')
            ->call('susunDp');

        $this->assertSame(4, $denah->zones()->where('type', LayoutZone::TYPE_DP)->count());
    }

    // ── Yang TIDAK ikut tertahan ───────────────────────────────────────────

    /**
     * Zona lain tetap bisa digeser selama satu zona terkunci.
     *
     * INI inti kuncinya. Kalau kunci per zona menular ke seluruh denah,
     * panitia yang butuh menggeser DP 4 tetap harus membuka kunci arena —
     * dan kuncinya jadi tak ada gunanya.
     */
    public function test_zona_lain_tetap_bisa_digeser()
    {
        [$denah, $editor] = $this->editor();
        $terkunci = $this->zona($denah->id, ['is_locked' => true, 'x' => 10]);
        $bebas = $this->zona($denah->id, ['x' => 10]);

        $editor->call('simpanPosisi', $bebas->id, 70, 70);

        $this->assertSame('70.000', (string) $bebas->fresh()->x);
        $this->assertSame('10.000', (string) $terkunci->fresh()->x);
    }

    /** Memilih zona terkunci tetap boleh: gemboknya harus bisa dibaca dan
     *  tombol pembukanya harus bisa dijangkau. */
    public function test_memilih_zona_terkunci_tetap_jalan()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true, 'name' => 'Tribun Utama']);

        $editor->call('pilihZona', $zona->id)->assertSet('selectedId', $zona->id);

        $editor->assertSee('Tribun Utama');
    }

    /**
     * Zona terkunci tetap bisa DIBUKA walau denahnya terkunci.
     *
     * Kalau tidak, kunci per zona jadi tak bisa dibatalkan selama denahnya
     * terkunci — dan satu-satunya jalan keluar adalah membuka kunci denah
     * lebih dulu, urutan yang tak masuk akal bagi yang sedang mengerjakan.
     */
    public function test_zona_terkunci_bisa_dibuka_walau_denahnya_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = $this->zona($denah->id, ['is_locked' => true]);

        $editor->call('toggleKunciZona', $zona->id);

        $this->assertFalse($zona->fresh()->is_locked);
    }

    /**
     * Sebaliknya: zona yang belum terkunci tak bisa DIKUNCI saat denahnya
     * terkunci. Mengunci itu mengubah isi denah, dan denah terkunci menahan
     * semua perubahan isinya.
     */
    public function test_zona_belum_terkunci_tak_bisa_dikunci_saat_denah_terkunci()
    {
        [$denah, $editor] = $this->editor(['is_locked' => true]);
        $zona = $this->zona($denah->id);

        $editor->call('toggleKunciZona', $zona->id);

        $this->assertFalse($zona->fresh()->is_locked);
    }

    // ── Kanvas & panel ─────────────────────────────────────────────────────

    /**
     * Zona terkunci tak menggambar pegangan ubah ukuran, dan membawa penanda
     * untuk penangan seret di kanvas — sementara zona di sebelahnya tetap
     * punya pegangan.
     *
     * Yang diperiksa `class="zona-hnd"` dengan tanda kutipnya, bukan
     * `zona-hnd` telanjang: blok gaya kanvas memuat selektornya juga, dan nama
     * itu akan lulus walaupun span-nya sudah tidak ada.
     */
    public function test_kanvas_menandai_zona_terkunci_dan_menahan_pegangannya()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id, ['is_locked' => true]);
        $editor->call('$refresh')
            ->assertSee('data-zona-terkunci="1"', false)
            ->assertDontSee('class="zona-hnd"', false);

        $bebas = $this->zona($denah->id);
        $editor->call('$refresh')->assertSee('class="zona-hnd"', false);

        $html = $editor->html();

        $this->assertStringContainsString('data-zona="'.$zona->id.'"', $html);
        $this->assertStringContainsString('data-zona="'.$bebas->id.'"', $html);
    }

    /** Gembok di daftar kiri: tanpa itu, satu-satunya cara tahu zona mana yang
     *  terkunci adalah memilihnya satu per satu. */
    public function test_daftar_zona_menampilkan_gembok()
    {
        [$denah, $editor] = $this->editor();
        $this->zona($denah->id, ['is_locked' => true, 'name' => 'Arena Utama']);

        $editor->call('$refresh')->assertSee('title="Zona terkunci"', false);
    }

    /** Tombol panel kanan berbunyi sesuai keadaan zonanya. */
    public function test_panel_kanan_menampilkan_tombol_kunci_zona()
    {
        [$denah, $editor] = $this->editor();
        $zona = $this->zona($denah->id);

        $editor->call('pilihZona', $zona->id)->assertSee('Kunci Zona');

        $editor->call('toggleKunciZona', $zona->id)->assertSee('Buka Kunci Zona');
    }

    /**
     * Halaman publik tak membawa jejak kunci per zona.
     *
     * Pembaca denah tak perlu tahu kotak mana yang masih dikerjakan panitia —
     * itu urusan kerja, bukan keterangan lapangan; dan gembok di denah publik
     * cuma memunculkan pertanyaan yang tak bisa dijawab denahnya.
     */
    public function test_halaman_publik_tak_membawa_penanda_zona_terkunci()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $denah = CompetitionLayout::factory()->terbit()->create([
            'eventner_id' => $eventner->id,
        ]);
        $this->zona($denah->id, ['is_locked' => true, 'name' => 'Tribun Utama']);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('Tribun Utama')
            ->assertDontSee('data-zona-terkunci', false)
            // Yang dicari span-nya, bukan `zone-gembok` telanjang: gaya kanvas
            // selalu ikut terkirim, selektornya ada di sana, dan pencarian
            // telanjang akan gagal walaupun gemboknya sudah tidak digambar.
            ->assertDontSee('class="zone-gembok"', false);
    }
}

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
 * Alur gerak pasukan di denah: penyimpanan, geometri, dan tampilannya.
 *
 * Dua hal yang dijaga berkas ini. Pertama, kolom route_points itu JSON bebas —
 * apa pun yang ditulis ke sana harus keluar dalam bentuk yang bisa digambar,
 * bukan membuat halaman publik event meledak. Kedua, sudutnya dihitung dalam
 * ruang papan, bukan ruang persen: denah 40 × 22 m yang belokannya digambar
 * dengan 1% lebar = 1% tinggi akan tampak belok 30° padahal di lapangan 90°.
 */
class LayoutAlurTest extends TestCase
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

    private function denah(array $atribut = []): CompetitionLayout
    {
        return CompetitionLayout::factory()->create($atribut);
    }

    // ── Penyaringan route_points ───────────────────────────────────────────

    /** Satu titik cacat tak boleh menjatuhkan seluruh denah. */
    public function test_titik_rusak_dibuang_diam_diam()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => 10, 'y' => 10, 'kind' => 'start'],
            'bukan array',
            ['x' => 'bukan angka', 'y' => 20],
            ['y' => 30],
            ['x' => 40, 'y' => 40, 'kind' => 'end'],
        ]]);

        $this->assertCount(2, $denah->alur_titik);
        $this->assertSame('start', $denah->alur_titik[0]['kind']);
        $this->assertSame('end', $denah->alur_titik[1]['kind']);
    }

    public function test_koordinat_dijepit_ke_rentang_papan()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => -40, 'y' => 150, 'kind' => 'start'],
            ['x' => 500, 'y' => -3, 'kind' => 'end'],
        ]]);

        $this->assertSame(0.0, $denah->alur_titik[0]['x']);
        $this->assertSame(100.0, $denah->alur_titik[0]['y']);
        $this->assertSame(100.0, $denah->alur_titik[1]['x']);
        $this->assertSame(0.0, $denah->alur_titik[1]['y']);
    }

    public function test_jenis_tak_dikenal_jadi_titik_belok()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => 1, 'y' => 1, 'kind' => 'meloncat'],
            ['x' => 2, 'y' => 2, 'kind' => 'end'],
        ]]);

        $this->assertSame('turn', $denah->alur_titik[0]['kind']);
    }

    /** 450° dan -90° sama-sama sah ditulis panitia; CSS hanya paham 0–359. */
    public function test_arah_hadap_dinormalkan_ke_0_sampai_359()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => 1, 'y' => 1, 'heading' => 450],
            ['x' => 2, 'y' => 2, 'heading' => -90],
            ['x' => 3, 'y' => 3, 'heading' => 'bukan angka'],
            ['x' => 4, 'y' => 4],
        ]]);

        $this->assertSame(90, $denah->alur_titik[0]['heading']);
        $this->assertSame(270, $denah->alur_titik[1]['heading']);
        $this->assertNull($denah->alur_titik[2]['heading']);
        $this->assertNull($denah->alur_titik[3]['heading']);
    }

    public function test_label_dibersihkan_dari_tag()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => 1, 'y' => 1, 'label' => '  <b>Hadap</b> kanan  '],
            ['x' => 2, 'y' => 2],
        ]]);

        $this->assertSame('Hadap kanan', $denah->alur_titik[0]['label']);
    }

    /** Satu titik bukan alur; menggambarnya cuma membingungkan pembaca. */
    public function test_satu_titik_bukan_alur()
    {
        $denah = $this->denah(['route_points' => [['x' => 10, 'y' => 10, 'kind' => 'start']]]);

        $this->assertFalse($denah->punya_alur);
    }

    public function test_denah_tanpa_route_points_tidak_punya_alur()
    {
        $this->assertFalse($this->denah()->punya_alur);
    }

    public function test_alur_tampil_mengikuti_saklar_publik()
    {
        $this->assertTrue($this->denah(['route_points' => [
            ['x' => 1, 'y' => 1], ['x' => 2, 'y' => 2],
        ], 'route_visible' => true])->alur_tampil);

        $this->assertFalse($this->denah(['route_points' => [
            ['x' => 1, 'y' => 1], ['x' => 2, 'y' => 2],
        ], 'route_visible' => false])->alur_tampil);
    }

    /** Papan tanpa ukuran tetap punya rasio bawaan; alurnya tak boleh hilang. */
    public function test_alur_di_papan_tanpa_ukuran_tetap_punya_segmen()
    {
        $denah = $this->denah([
            'ground_width' => 0,
            'ground_height' => 0,
            'route_points' => [['x' => 10, 'y' => 10], ['x' => 40, 'y' => 10]],
        ]);

        $this->assertCount(1, $denah->alurSegmen());
    }

    // ── Geometri ruas ──────────────────────────────────────────────────────

    public function test_segmen_menghitung_sudut_dalam_ruang_papan()
    {
        $denah = CompetitionLayout::factory()->beralur()->create([
            'ground_width' => 40,
            'ground_height' => 22,
        ]);

        $segmen = $denah->alurSegmen();

        $this->assertCount(3, $segmen);
        $this->assertSame(0.0, $segmen[0]['sudut'], 'Ruas mendatar harus 0°.');
        $this->assertSame(-90.0, $segmen[1]['sudut'], 'Ruas tegak harus -90°, bukan sudut yang dipadatkan rasio.');
        $this->assertSame(0.0, $segmen[2]['sudut']);
    }

    /** Ruas berawal di titik sebelumnya, bukan di titik sesudahnya. */
    public function test_segmen_berawal_di_titik_sebelumnya()
    {
        $denah = CompetitionLayout::factory()->beralur()->create();

        $segmen = $denah->alurSegmen();

        $this->assertSame(10.0, $segmen[0]['x']);
        $this->assertSame(80.0, $segmen[0]['y']);
        $this->assertSame(40.0, $segmen[1]['x']);
        $this->assertSame(80.0, $segmen[1]['y']);
    }

    /** Dua titik yang menumpuk persis tak menghasilkan ruas; kalau lolos, CSS
     *  menggambar garis nol panjang yang membuat sudutnya NaN. */
    public function test_ruas_bertitik_tumpuk_dilewati()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => 20, 'y' => 20, 'kind' => 'start'],
            ['x' => 20, 'y' => 20, 'kind' => 'turn'],
            ['x' => 60, 'y' => 20, 'kind' => 'end'],
        ]]);

        $this->assertCount(1, $denah->alurSegmen());
        $this->assertSame(20.0, $denah->alurSegmen()[0]['x']);
    }

    public function test_panjang_ruas_dihitung_dari_kedua_sumbu()
    {
        $denah = $this->denah([
            'ground_width' => 40,
            'ground_height' => 40,
            'route_points' => [['x' => 0, 'y' => 0], ['x' => 30, 'y' => 40]],
        ]);

        // 3-4-5: dx 30, dy 40 pada papan bujur sangkar.
        $this->assertSame(50.0, $denah->alurSegmen()[0]['panjang']);
    }

    // ── Daftar langkah ─────────────────────────────────────────────────────

    public function test_langkah_melewati_belok_tanpa_label()
    {
        $denah = CompetitionLayout::factory()->beralur()->create();

        $judul = array_column($denah->alurLangkah(), 'judul');

        $this->assertSame(['Mulai', 'Hadap kanan', 'Selesai'], $judul);
    }

    /** Nomornya nomor langkah, bukan nomor titik di editor: daftar "1, 2, 4"
     *  terbaca seperti langkah yang hilang. */
    public function test_nomor_langkah_berurutan_tanpa_lompatan()
    {
        $denah = CompetitionLayout::factory()->beralur()->create();

        $this->assertSame([1, 2, 3], array_column($denah->alurLangkah(), 'nomor'));
    }

    public function test_belok_berlabel_tetap_muncul_di_daftar()
    {
        $denah = $this->denah(['route_points' => [
            ['x' => 10, 'y' => 10, 'kind' => 'start'],
            ['x' => 40, 'y' => 10, 'kind' => 'turn', 'label' => 'Belok kanan'],
            ['x' => 40, 'y' => 60, 'kind' => 'end'],
        ]]);

        $this->assertSame('Belok kanan', $denah->alurLangkah()[1]['judul']);
    }

    public function test_langkah_membawa_arah_hadap()
    {
        $denah = CompetitionLayout::factory()->beralur()->create();

        $this->assertSame(90, $denah->alurLangkah()[1]['hadap']);
        $this->assertNull($denah->alurLangkah()[0]['hadap']);
    }

    // ── Editor ─────────────────────────────────────────────────────────────

    public function test_mulai_alur_membuat_dua_titik()
    {
        [$denah, $editor] = $this->editor();

        $editor->call('mulaiAlur');

        $denah->refresh();
        $this->assertTrue($denah->punya_alur);
        $this->assertCount(2, $denah->alur_titik);
        $this->assertSame('start', $denah->alur_titik[0]['kind']);
        $this->assertSame('end', $denah->alur_titik[1]['kind']);
    }

    /** Menekan "Buat Alur" dua kali tak boleh menimpa alur yang sudah digambar. */
    public function test_mulai_alur_tidak_menimpa_alur_yang_sudah_ada()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');
        $editor->call('simpanTitik', 0, 55, 55);

        $editor->call('mulaiAlur');

        $this->assertSame(55.0, $denah->refresh()->alur_titik[0]['x']);
    }

    /** Menyisipkan titik di tengah ruas tak boleh mengubah bentuk alurnya. */
    public function test_sisip_titik_tidak_mengubah_bentuk_alur()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $sebelum = $denah->refresh()->alurSegmen();

        $editor->call('sisipTitik', 0);

        $sesudah = $denah->refresh()->alurSegmen();

        $this->assertCount(2, $sesudah);
        $this->assertSame($sebelum[0]['sudut'], $sesudah[0]['sudut']);
        $this->assertSame($sebelum[0]['sudut'], $sesudah[1]['sudut']);
        $this->assertEqualsWithDelta(
            $sebelum[0]['panjang'],
            $sesudah[0]['panjang'] + $sesudah[1]['panjang'],
            0.05,
        );
    }

    public function test_sisip_titik_di_ruas_tak_ada_diabaikan()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('sisipTitik', 7);

        $this->assertCount(2, $denah->refresh()->alur_titik);
    }

    public function test_simpan_titik_menyimpan_posisi_baru()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('simpanTitik', 0, 33.5, 44.25);

        $titik = $denah->refresh()->alur_titik[0];
        $this->assertSame(33.5, $titik['x']);
        $this->assertSame(44.25, $titik['y']);
    }

    /** Seretan yang melewati tepi kanvas menghasilkan angka di luar 0–100. */
    public function test_simpan_titik_menjepit_di_luar_papan()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('simpanTitik', 0, -12, 240);

        $titik = $denah->refresh()->alur_titik[0];
        $this->assertSame(0.0, $titik['x']);
        $this->assertSame(100.0, $titik['y']);
    }

    public function test_simpan_titik_di_indeks_tak_ada_diabaikan()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('simpanTitik', 9, 50, 50);

        $this->assertCount(2, $denah->refresh()->alur_titik);
    }

    public function test_pilih_dan_simpan_sifat_titik()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor
            ->call('pilihTitik', 1)
            ->set('titikLabel', 'Hormat')
            ->set('titikHadap', '180')
            ->call('simpanSifatTitik');

        $titik = $denah->refresh()->alur_titik[1];
        $this->assertSame('Hormat', $titik['label']);
        $this->assertSame(180, $titik['heading']);
    }

    /** Mengosongkan arah hadap harus menghapus panahnya, bukan menyimpan 0°. */
    public function test_mengosongkan_arah_hadap_menghapus_panah()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('pilihTitik', 0)->set('titikHadap', '')->call('simpanSifatTitik');

        $this->assertNull($denah->refresh()->alur_titik[0]['heading']);
    }

    public function test_arah_hadap_di_luar_rentang_ditolak()
    {
        [, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor
            ->call('pilihTitik', 0)
            ->set('titikHadap', '400')
            ->call('simpanSifatTitik')
            ->assertHasErrors('titikHadap');
    }

    public function test_ubah_jenis_titik()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('pilihTitik', 0)->call('ubahJenisTitik', 'aba_aba');

        $this->assertSame('aba_aba', $denah->refresh()->alur_titik[0]['kind']);
    }

    public function test_ubah_jenis_tak_dikenal_diabaikan()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('pilihTitik', 0)->call('ubahJenisTitik', 'terbang');

        $this->assertSame('start', $denah->refresh()->alur_titik[0]['kind']);
    }

    public function test_tambah_titik_di_ujung()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('tambahTitikAkhir');

        $titik = $denah->refresh()->alur_titik;
        $this->assertCount(3, $titik);
        $this->assertSame('turn', $titik[2]['kind']);
    }

    /** Titik baru digeser sedikit: titik yang menumpuk persis tak bisa diseret. */
    public function test_titik_ujung_baru_tidak_menumpuk_titik_sebelumnya()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');
        $editor->call('tambahTitikAkhir');

        $titik = $denah->refresh()->alur_titik;

        $this->assertNotSame($titik[1]['x'], $titik[2]['x']);
    }

    public function test_hapus_titik_menyisakan_alur_yang_masih_bisa_digambar()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');
        $editor->call('tambahTitikAkhir');

        $editor->call('hapusTitik', 1);

        $this->assertCount(2, $denah->refresh()->alur_titik);
    }

    /** Sisa satu titik bukan alur; kolomnya dibersihkan, bukan ditinggal separuh. */
    public function test_hapus_titik_hingga_satu_menghapus_seluruh_alur()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('hapusTitik', 0);

        $denah->refresh();
        $this->assertNull($denah->route_points);
        $this->assertFalse($denah->punya_alur);
    }

    public function test_hapus_alur_mengosongkan_kolomnya()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('hapusAlur');

        $this->assertNull($denah->refresh()->route_points);
    }

    public function test_saklar_alur_publik_bolak_balik()
    {
        [$denah, $editor] = $this->editor();
        $editor->call('mulaiAlur');

        $editor->call('toggleAlurPublik');
        $this->assertFalse($denah->refresh()->route_visible);

        $editor->call('toggleAlurPublik');
        $this->assertTrue($denah->refresh()->route_visible);
    }

    public function test_ganti_mode_menutup_pilihan_yang_terbuka()
    {
        [, $editor] = $this->editor();
        $editor->call('mulaiAlur')->call('pilihTitik', 0);

        $editor->call('gantiMode', 'zona')->assertSet('titikTerpilih', null);

        $editor->call('gantiMode', 'alur');
        $editor->assertSet('mode', 'alur');
    }

    public function test_mode_tak_dikenal_diabaikan()
    {
        [, $editor] = $this->editor();

        $editor->call('gantiMode', 'melayang')->assertSet('mode', 'zona');
    }

    /**
     * Mode alur harus sampai ke kanvas, bukan cuma ke panel.
     *
     * Kanvas membaca mode dari atributnya; kalau atribut itu tak ikut berubah,
     * panitia menekan tab Alur dan tetap menyeret kotak arena.
     */
    public function test_mode_alur_sampai_ke_atribut_kanvas()
    {
        [, $editor] = $this->editor();

        $editor->call('gantiMode', 'alur')
            ->assertSee('data-denah-mode="alur"', false)
            ->assertSee('denah-alur-mode', false);
    }

    public function test_panel_zona_digantikan_panel_alur()
    {
        [, $editor] = $this->editor();

        $editor->assertSee('Tambah Zona');

        $editor->call('gantiMode', 'alur')
            ->assertSee('Alur Gerak Pasukan')
            ->assertDontSee('Tambah Zona');
    }

    /** Editor menggambar alur walau saklar publiknya mati; kalau tidak, alur
     *  yang disembunyikan tak bisa disunting lagi. */
    public function test_editor_tetap_menggambar_alur_yang_disembunyikan()
    {
        [$denah, $editor] = $this->editor([
            'route_points' => CompetitionLayout::factory()->beralur()->make()->route_points,
            'route_visible' => false,
        ]);

        $editor->call('gantiMode', 'alur')->assertSee('class="alur-ruas"', false);
    }

    // ── Halaman publik ─────────────────────────────────────────────────────

    private function eventnerTerbit(array $atributDenah = []): array
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $denah = CompetitionLayout::factory()->create(array_merge([
            'eventner_id' => $eventner->id,
            'is_published' => true,
            'published_at' => now(),
        ], $atributDenah));
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id, 'name' => 'Arena Utama']);

        return [$eventner, $denah];
    }

    public function test_urutan_alur_tampil_di_halaman_publik()
    {
        [$eventner] = $this->eventnerTerbit([
            'route_points' => CompetitionLayout::factory()->beralur()->make()->route_points,
            'route_visible' => true,
        ]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('Urutan Alur Pasukan')
            ->assertSee('Hadap kanan')
            ->assertSee('Selesai');
    }

    /** Saklar "tampilkan di publik" mematikan gambarnya DAN daftar langkahnya. */
    public function test_alur_tersembunyi_tidak_bocor_ke_halaman_publik()
    {
        [$eventner] = $this->eventnerTerbit([
            'route_points' => CompetitionLayout::factory()->beralur()->make()->route_points,
            'route_visible' => false,
        ]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertDontSee('Urutan Alur Pasukan')
            ->assertDontSee('Hadap kanan');
    }

    /** Garis alurnya benar-benar dirender, bukan cuma daftar teksnya. */
    public function test_ruas_alur_ikut_dirender_di_halaman_publik()
    {
        [$eventner] = $this->eventnerTerbit([
            'route_points' => CompetitionLayout::factory()->beralur()->make()->route_points,
            'route_visible' => true,
        ]);

        $html = $this->get(route('event.denah', $eventner->slug))->getContent();

        $this->assertStringContainsString('alur-ruas', $html);
        $this->assertStringContainsString('alur-garis', $html);
    }

    // ── PDF ────────────────────────────────────────────────────────────────

    private function viewPdf(CompetitionLayout $denah): string
    {
        $denah->load('zones');

        return view('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $denah->eventner,
            'tinggiKanvas' => '120mm',
            'ukuranLabel' => '13px',
        ])->render();
    }

    public function test_alur_ikut_tercetak_di_pdf()
    {
        $denah = CompetitionLayout::factory()->beralur()->create();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $html = $this->viewPdf($denah);

        $this->assertStringContainsString('Alur Gerak Pasukan', $html);
        $this->assertStringContainsString('Hadap kanan', $html);
    }

    /** Lembar PDF beredar di luar panitia, jadi ia ikut aturan saklar publik. */
    public function test_alur_tersembunyi_tidak_tercetak_di_pdf()
    {
        $denah = CompetitionLayout::factory()->alurTersembunyi()->create();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $html = $this->viewPdf($denah);

        $this->assertStringNotContainsString('Alur Gerak Pasukan', $html);
        // Yang diperiksa markup-nya, bukan nama kelasnya: gaya CSS-nya memang
        // selalu ikut terkirim (blok @once), dan itu tak menggambar apa pun.
        $this->assertStringNotContainsString('class="alur-ruas"', $html);
    }

    /** Ruas digambar sebagai div diputar — dompdf tak menggambar SVG sebaris. */
    public function test_pdf_tidak_memakai_svg_untuk_alur()
    {
        $denah = CompetitionLayout::factory()->beralur()->create();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->assertStringNotContainsString('<svg', $this->viewPdf($denah));
    }
}

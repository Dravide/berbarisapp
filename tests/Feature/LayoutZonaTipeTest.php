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
 * Daftar jenis zona, dan kanvas yang mengisi seluruh bidangnya.
 *
 * Dua hal yang dijaga berkas ini. Pertama, setiap jenis zona punya ikon yang
 * benar-benar ada di webfont lokal dan kelas warna sendiri — jenis yang cuma
 * terdaftar setengah menyisakan kotak kosong tanpa melempar apa pun. Kedua,
 * papan dan kanvas berimpit: dulu ada pita ukuran yang memakan tepi kanvas,
 * dan bingkai denah menyusut sejauh lebar pitanya.
 */
class LayoutZonaTipeTest extends TestCase
{
    use RefreshDatabase;

    private function denah(array $atribut = []): CompetitionLayout
    {
        return CompetitionLayout::factory()->create($atribut);
    }

    private function viewKanvas(CompetitionLayout $denah, array $opsi = []): string
    {
        $denah->load('zones');

        return view('partials.denah-kanvas', array_merge([
            'layout' => $denah,
            'interaktif' => false,
        ], $opsi))->render();
    }

    /**
     * Buang blok <style> sebelum memeriksa kelas zona.
     *
     * Gaya kanvas ikut terkirim sekali per halaman (blok @once) dan memuat
     * nama setiap kelas zona di selektornya — `.zone.z-danton { … }`. Tanpa
     * dibuang, pemeriksaan "markup-nya punya kelas ini" lulus walaupun zona-nya
     * sama sekali tak membawa kelas itu: yang terbaca cuma aturan gayanya.
     */
    private function markup(string $html): string
    {
        return preg_replace('#<style>.*?</style>#s', '', $html);
    }

    // ── Kanvas penuh ───────────────────────────────────────────────────────

    /** Papan mengisi seluruh kanvas — tak ada pita yang memakan tepinya. */
    public function test_bingkai_menutup_seluruh_kanvas()
    {
        $html = $this->viewKanvas($this->denah());

        $this->assertStringContainsString('left: 0%; top: 0%; width: 100%; height: 100%;', $html);
    }

    /** Pita ukuran sudah tidak ada di markup mana pun. */
    public function test_pita_ukuran_sudah_tidak_ada()
    {
        $html = $this->viewKanvas($this->denah());

        $this->assertStringNotContainsString('class="denah-pita', $html);
        $this->assertStringNotContainsString('class="pita-lurik', $html);
    }

    // ── Editor ─────────────────────────────────────────────────────────────

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

    public function test_setiap_jenis_zona_punya_tombol_berikon()
    {
        [, $editor] = $this->editor();

        foreach (LayoutZone::TIPE_IKON as $jenis => $ikon) {
            $editor->assertSee('ti '.$ikon, false);
        }
    }

    /** Tombol pengubah ukuran papan tetap ada di batang aksi halaman editor.
     *
     *  Dulu ia tombol penggaris kecil yang duduk di pita ukuran; pitanya
     *  dihapus, dan ukuran papan tetap bisa diubah dari batang aksi. */
    public function test_ubah_ukuran_papan_tetap_terjangkau_dari_editor()
    {
        [, $editor] = $this->editor();

        $editor->assertSee('Ukuran Papan')
            ->call('bukaPapanModal')
            ->assertSet('showPapanModal', true);
    }

    /** Tombol penggaris kecil yang dulu di pita harus benar-benar hilang — ia
     *  cuma bisa ada di sana karena ada pitanya. */
    public function test_tombol_penggaris_di_pita_sudah_tidak_ada()
    {
        [, $editor] = $this->editor();

        $editor->assertDontSee('class="pita-ubah"', false);
    }

    // ── Kotak danton ───────────────────────────────────────────────────────

    public function test_kotak_danton_tersedia_di_daftar_tambah_zona()
    {
        [, $editor] = $this->editor();

        $editor->assertSee('Kotak Danton')
            ->call('tambahZona', LayoutZone::TYPE_KOTAK_DANTON)
            ->assertHasNoErrors();
    }

    /**
     * Kotak danton harus bujur sangkar DI LAPANGAN, bukan di kanvas.
     *
     * Persen lebar dan persen tinggi bukan satuan yang sama: 5% dari papan
     * 40 × 22 m adalah 2 m lebar tapi cuma 1,1 m tinggi. Kalau tingginya
     * disamakan angkanya dengan lebarnya, kotaknya tergambar persegi di layar
     * sementara di lapangan danton berdiri di petak yang gepeng.
     */
    public function test_kotak_danton_bujur_sangkar_di_lapangan()
    {
        // Papan 2:1, jadi tinggi persennya harus dua kali lebar persennya.
        [$denah, $editor] = $this->editor(['ground_width' => 40, 'ground_height' => 20]);

        $editor->call('tambahZona', LayoutZone::TYPE_KOTAK_DANTON);

        $zona = $denah->zones()->where('type', LayoutZone::TYPE_KOTAK_DANTON)->firstOrFail();

        $this->assertSame(
            round((float) $zona->w * 2, 3),
            round((float) $zona->h, 3),
            'Tinggi kotak danton harus ikut rasio papan supaya sama sisi di lapangan.'
        );
    }

    /** Satu peleton satu danton: namanya tak perlu nomor urut. */
    public function test_kotak_danton_tidak_diberi_nomor_urut()
    {
        [$denah, $editor] = $this->editor();

        $editor->call('tambahZona', LayoutZone::TYPE_KOTAK_DANTON);
        $editor->call('tambahZona', LayoutZone::TYPE_KOTAK_DANTON);

        $nama = $denah->zones()->where('type', LayoutZone::TYPE_KOTAK_DANTON)->pluck('name');

        $this->assertSame(['Kotak Danton', 'Kotak Danton'], $nama->all());
    }

    public function test_zona_kotak_danton_punya_kelas_kanvas_sendiri()
    {
        $denah = $this->denah();
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Kotak Danton',
            'type' => LayoutZone::TYPE_KOTAK_DANTON,
        ]);

        $this->assertStringContainsString('z-danton', $this->markup($this->viewKanvas($denah)));
    }

    // ── Tiang bendera ──────────────────────────────────────────────────────

    public function test_tiang_bendera_tersedia_di_daftar_tambah_zona()
    {
        [, $editor] = $this->editor();

        $editor->assertSee('Tiang Bendera')
            ->call('tambahZona', LayoutZone::TYPE_TIANG_BENDERA)
            ->assertHasNoErrors();
    }

    /** Tiang bendera itu tonggak: sempit dan tinggi, bukan bidang. */
    public function test_tiang_bendera_berukuran_tonggak()
    {
        [$denah, $editor] = $this->editor();

        $editor->call('tambahZona', LayoutZone::TYPE_TIANG_BENDERA);

        $zona = $denah->zones()->where('type', LayoutZone::TYPE_TIANG_BENDERA)->firstOrFail();

        $this->assertLessThan((float) $zona->h, (float) $zona->w);
    }

    public function test_tiang_bendera_boleh_dipakai_lebih_dari_sekali()
    {
        [$denah, $editor] = $this->editor();

        $editor->call('tambahZona', LayoutZone::TYPE_TIANG_BENDERA);
        $editor->call('tambahZona', LayoutZone::TYPE_TIANG_BENDERA);

        $this->assertSame(2, $denah->zones()->where('type', LayoutZone::TYPE_TIANG_BENDERA)->count());
    }

    public function test_zona_tiang_bendera_punya_kelas_kanvas_sendiri()
    {
        $denah = $this->denah();
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Tiang Bendera 1',
            'type' => LayoutZone::TYPE_TIANG_BENDERA,
        ]);

        $this->assertStringContainsString('z-bendera', $this->viewKanvas($denah));
    }

    /**
     * Ikon & kelas harus lengkap untuk SETIAP jenis yang terdaftar.
     *
     * Jenis tanpa ikon cuma menyisakan kotak kosong di tombolnya; jenis tanpa
     * kelas digambar sebagai teks biasa. Keduanya lolos dari mata karena tak
     * melempar apa pun.
     */
    public function test_tiap_jenis_punya_ikon_dan_kelas()
    {
        foreach (array_keys(LayoutZone::TIPE_LABEL) as $jenis) {
            $this->assertArrayHasKey($jenis, LayoutZone::TIPE_IKON, "Ikon {$jenis} belum diisi.");
            $this->assertArrayHasKey($jenis, LayoutZone::TIPE_KELAS, "Kelas {$jenis} belum diisi.");
        }

        $this->assertSame([], array_diff(array_keys(LayoutZone::TIPE_IKON), array_keys(LayoutZone::TIPE_LABEL)));
        $this->assertSame([], array_diff(array_keys(LayoutZone::TIPE_KELAS), array_keys(LayoutZone::TIPE_LABEL)));
    }

    /** Ikon yang tak ada di webfont lokal cuma jadi kotak kosong. */
    public function test_ikon_yang_dipakai_ada_di_webfont_lokal()
    {
        $css = file_get_contents(public_path('templates/assets/fonts/tabler-icons/tabler-icons.css'));

        foreach (array_unique(LayoutZone::TIPE_IKON) as $ikon) {
            $this->assertStringContainsString('.'.$ikon.':before', $css, "Ikon {$ikon} tak ada di tabler-icons.css.");
        }
    }

    // ── Panah ──────────────────────────────────────────────────────────────

    /** Panah arah gerak: kotaknya bukan lagi pita bergaris putus di peramban,
     *  tapi gambar panah. */
    public function test_zona_panah_digambar_sebagai_panah()
    {
        $denah = $this->denah();
        $this->panah($denah->id, ['w' => 62, 'h' => 3]);

        $markup = $this->markup($this->viewKanvas($denah));

        $this->assertStringContainsString('<svg', $markup);
        $this->assertStringContainsString('class="panah"', $markup);
        $this->assertStringContainsString('panah-batang', $markup);
        $this->assertStringContainsString('panah-mata', $markup);
    }

    /** Arah bacaannya ikut bentuk kotaknya: pita mendatar → panah mendatar. */
    public function test_panah_mendatar_untuk_kotak_yang_lebar()
    {
        $denah = $this->denah();
        $this->panah($denah->id, ['w' => 62, 'h' => 3]);

        $this->assertStringNotContainsString('panah-tegak', $this->markup($this->viewKanvas($denah)));
    }

    public function test_panah_tegak_untuk_kotak_yang_tinggi()
    {
        $denah = $this->denah();
        $this->panah($denah->id, ['w' => 3, 'h' => 72]);

        $this->assertStringContainsString('panah-tegak', $this->markup($this->viewKanvas($denah)));
    }

    /** Zona selain panah tak ikut menggambar panah. */
    public function test_zona_bukan_panah_tak_menggambar_panah()
    {
        $denah = $this->denah();
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Arena Utama',
        ]);

        $this->assertStringNotContainsString('<svg', $this->markup($this->viewKanvas($denah)));
    }

    /**
     * Kertas tak bisa menggambar SVG sebaris — dompdf membuangnya tanpa
     * keluhan, dan panah yang hilang di denah tercetak lebih buruk daripada
     * panah yang tidak sedetail di layar.
     *
     * Karena itu `$cetak` mengembalikan zona panah ke wujud lamanya: pita
     * bergaris putus lengkap dengan namanya. Yang dijaga di sini justru
     * sebaliknya dari tes di atas — bukan "panahnya ada", tapi "panahnya
     * TIDAK dipaksakan ada" di permukaan yang tak bisa menampilkannya.
     */
    public function test_kanvas_kertas_menahan_panah_sebagai_pita_nama()
    {
        $denah = $this->denah();
        $this->panah($denah->id, ['w' => 62, 'h' => 3]);

        $markup = $this->markup($this->viewKanvas($denah, ['cetak' => true]));

        $this->assertStringNotContainsString('<svg', $markup);
        $this->assertStringNotContainsString('panah-mata', $markup);
        $this->assertStringContainsString('Arah gerak pasukan', $markup);
        $this->assertStringContainsString('z-arah', $markup);
    }

    private function panah(int $denahId, array $atribut = []): LayoutZone
    {
        return LayoutZone::factory()->create(array_merge([
            'competition_layout_id' => $denahId,
            'name' => 'Arah gerak pasukan',
            'type' => LayoutZone::TYPE_PANAH,
            'x' => 14,
            'y' => 80,
        ], $atribut));
    }

    // ── Halaman publik & PDF ───────────────────────────────────────────────

    public function test_zona_ikut_di_halaman_publik()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $denah = CompetitionLayout::factory()->terbit()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Tribun Utama',
        ]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('Tribun Utama');
    }

    public function test_zona_ikut_tercetak_di_pdf()
    {
        $denah = $this->denah();
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Tribun Utama',
        ]);
        $denah->load('zones');

        $html = view('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $denah->eventner,
            'tinggiKanvas' => '120mm',
            'ukuranLabel' => '13px',
        ])->render();

        $this->assertStringContainsString('Tribun Utama', $html);
    }
}

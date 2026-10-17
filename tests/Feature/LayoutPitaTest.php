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
 * Pita ukuran (garis polisi) di tepi denah, dan zona tiang bendera.
 *
 * Dua hal yang dijaga berkas ini. Pertama, pitanya mengelilingi keempat tepi
 * tanpa menimbun zona: persen zona dihitung dari papan, sedangkan pitanya
 * duduk di bantalan kanvas — kalau bantalannya lupa, denahnya bergeser. Kedua,
 * pita itu bisa dimatikan lewat $pita, karena ia hiasan tepi, bukan bagian
 * dari tata letak yang digambar panitia.
 */
class LayoutPitaTest extends TestCase
{
    use RefreshDatabase;

    private function denah(array $atribut = []): CompetitionLayout
    {
        return CompetitionLayout::factory()->create($atribut);
    }

    // ── Ukuran siap baca ───────────────────────────────────────────────────

    public function test_ukuran_teks_membuang_desimal_nol()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 22]);

        $this->assertSame('40 × 22 m', $denah->ukuran_teks);
    }

    public function test_ukuran_teks_memakai_koma_untuk_desimal()
    {
        $denah = $this->denah(['ground_width' => 40.5, 'ground_height' => 22.25]);

        $this->assertSame('40,5 × 22,3 m', $denah->ukuran_teks);
    }

    /** Pita tegak cuma memuat satu angka; "40 × 22 m" di tepi setinggi 3%
     *  papan tak terbaca. */
    public function test_tinggi_teks_hanya_memuat_angka_tinggi()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 22]);

        $this->assertSame('22 m', $denah->tinggi_teks);
    }

    // ── Markup pita ────────────────────────────────────────────────────────

    private function viewKanvas(CompetitionLayout $denah, array $opsi = []): string
    {
        $denah->load('zones');

        return view('partials.denah-kanvas', array_merge([
            'layout' => $denah,
            'interaktif' => false,
        ], $opsi))->render();
    }

    /**
     * Buang blok <style> sebelum memeriksa "tidak ada".
     *
     * Gaya kanvas ikut terkirim sekali per halaman (blok @once) dan memuat
     * nama kelas pita di selektornya — termasuk di komentar yang menjelaskan
     * kenapa gradien tidak dipakai. Yang menentukan apakah pitanya tergambar
     * adalah markup-nya, bukan gaya yang menunggu dipakai.
     */
    private function markup(string $html): string
    {
        return preg_replace('#<style>.*?</style>#s', '', $html);
    }

    public function test_pita_digambar_di_keempat_tepi()
    {
        $html = $this->markup($this->viewKanvas($this->denah()));

        foreach (['denah-pita-atas', 'denah-pita-bawah', 'denah-pita-kiri', 'denah-pita-kanan'] as $kelas) {
            $this->assertStringContainsString('class="denah-pita '.$kelas.'"', $html);
        }
    }

    /** Angkanya sekali per sisi: pita ukur yang mengulang "40 × 22 m" di empat
     *  tepi justru terbaca seperti empat ukuran berbeda. */
    public function test_angka_ukuran_muncul_sekali_di_tiap_sisi()
    {
        $html = $this->viewKanvas($this->denah(['ground_width' => 40, 'ground_height' => 22]));

        $this->assertSame(1, substr_count($html, '>40 × 22 m<'));
        $this->assertSame(2, substr_count($html, '>22 m<'));
    }

    /** Luriknya span berselang-seling, bukan gradien: dompdf mengabaikan
     *  repeating-linear-gradient sepenuhnya. */
    public function test_lurik_dibuat_dari_span_bukan_gradien()
    {
        $html = $this->markup($this->viewKanvas($this->denah()));

        $this->assertStringContainsString('class="pita-lurik"', $html);
        $this->assertStringNotContainsString('repeating-linear-gradient', $html);
    }

    public function test_pita_bisa_dimatikan()
    {
        $html = $this->markup($this->viewKanvas($this->denah(), ['pita' => false]));

        $this->assertStringNotContainsString('class="denah-pita', $html);
        $this->assertStringNotContainsString('class="pita-lurik', $html);
    }

    /** Tanpa pita tak ada bantalan: papan memakai seluruh kanvas. */
    public function test_tanpa_pita_bingkai_menutup_seluruh_kanvas()
    {
        $html = $this->viewKanvas($this->denah(), ['pita' => false]);

        $this->assertStringContainsString('left: 0%; top: 0%; width: 100%; height: 100%;', $html);
    }

    /**
     * Bantalan tegak = bantalan × rasio.
     *
     * Persen tinggi bukan persen lebar: papan 40 × 22 m butuh bantalan tegak
     * ~1,8× lipat supaya pita atas setebal pita kiri.
     */
    public function test_bantalan_tegak_mengikuti_rasio_papan()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 22]);
        $html = $this->viewKanvas($denah);

        // 2,6 × (40/22) = 4,727
        $this->assertStringContainsString('top: 4.727%;', $html);
    }

    /** Pita atas & bawah memakai tinggi yang sama dengan bantalan tegaknya. */
    public function test_pita_atas_setebal_bantalan_tegak()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 22]);
        $html = $this->viewKanvas($denah);

        $this->assertStringContainsString('denah-pita-atas" style="left: 0; top: 0; width: 100%; height: 4.727%;', $html);
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

    /** Tombol ubah ukuran papan duduk di pita — ukuran lapangan dan tempat
     *  mengubahnya memang satu hal. */
    public function test_tombol_ubah_papan_ada_di_pita_editor()
    {
        [, $editor] = $this->editor();

        $editor->assertSee('class="pita-ubah"', false)
            ->assertSee('Ubah ukuran papan');
    }

    /** Tombolnya setinggi pitanya, bukan px tetap: pita atas menipis di kanvas
     *  sempit, dan tombol yang lebih tinggi dari pitanya menutupi sudut papan. */
    public function test_tombol_ubah_papan_setinggi_pitanya()
    {
        [, $editor] = $this->editor();

        // Bantalan editor 3,6% × rasio 40/22 = 6,545%.
        $editor->assertSee('right: 3.6%; top: 0; height: 6.545%;', false);
    }

    /** Bantalan editor lebih lebar karena pitanya memuat tombol. */
    public function test_bantalan_editor_lebih_lebar()
    {
        [, $editor] = $this->editor();

        $editor->assertSee('left: 3.6%;', false);
    }

    public function test_setiap_jenis_zona_punya_tombol_berikon()
    {
        [, $editor] = $this->editor();

        foreach (LayoutZone::TIPE_IKON as $jenis => $ikon) {
            $editor->assertSee('ti '.$ikon, false);
        }
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

        $this->assertStringContainsString('z-bendera', $this->markup($this->viewKanvas($denah)));
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

    // ── Halaman publik & PDF ───────────────────────────────────────────────

    public function test_pita_ikut_di_halaman_publik()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $denah = CompetitionLayout::factory()->terbit()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('class="denah-pita denah-pita-atas"', false)
            ->assertSee('40 × 22 m');
    }

    public function test_pita_ikut_tercetak_di_pdf()
    {
        $denah = $this->denah();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);
        $denah->load('zones');

        $html = view('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $denah->eventner,
            'tinggiKanvas' => '120mm',
            'ukuranLabel' => '13px',
        ])->render();

        $this->assertStringContainsString('class="denah-pita denah-pita-atas"', $html);
        $this->assertStringContainsString('40 × 22 m', $html);
    }

    /** Tombol ubah papan itu alat editor; ia tak boleh bocor ke kertas. */
    public function test_pdf_tidak_memuat_tombol_editor()
    {
        $denah = $this->denah();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);
        $denah->load('zones');

        $html = view('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $denah->eventner,
            'tinggiKanvas' => '120mm',
            'ukuranLabel' => '13px',
        ])->render();

        $this->assertStringNotContainsString('class="pita-ubah"', $this->markup($html));
    }
}

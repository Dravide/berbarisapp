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
 * Ukuran meter milik arena, lepas dari ukuran papan.
 *
 * Arena lomba kerap berukuran tetap sementara papannya cuma kanvas untuk
 * menggambar. Yang dijaga berkas ini adalah kesepakatan antara ANGKA dan
 * GAMBAR: kotak yang tertulis "15 × 15 m" harus benar-benar 15 × 15 m di atas
 * papan — bukan angka yang melayang di atas kotak yang mengatakannya lain.
 */
class LayoutArenaMeterTest extends TestCase
{
    use RefreshDatabase;

    private function denah(array $atribut = []): CompetitionLayout
    {
        return CompetitionLayout::factory()->create(array_merge(
            ['ground_width' => 40, 'ground_height' => 22],
            $atribut
        ));
    }

    private function editor(CompetitionLayout $denah): \Livewire\Features\SupportTesting\Testable
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'paid',
            'trial_ends_at' => null,
        ]);
        $denah->update(['eventner_id' => $eventner->id]);

        $this->actingAs($user);

        return Livewire::test(LayoutShow::class, ['layout' => $denah->fresh()]);
    }

    // ── Angka menang atas hitungan papan ───────────────────────────────────

    /** Tanpa ukuran sendiri, ukurannya disimpulkan dari papan seperti dulu. */
    public function test_zona_tanpa_ukuran_sendiri_dihitung_dari_papan()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->make(['w' => 50, 'h' => 25]);

        $this->assertSame('20 × 5 m', $zona->ukuranMeter($denah));
    }

    /**
     * Ukuran sendiri menang atas hitungan papan.
     *
     * Arena 15 m tetap 15 m walau kotaknya di kanvas 37,5% papan 40 m.
     */
    public function test_ukuran_sendiri_menang_atas_hitungan_papan()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->make(['w' => 50, 'h' => 50, 'width_m' => 15, 'height_m' => 15]);

        $this->assertSame('15 × 15 m', $zona->ukuranMeter($denah));
    }

    public function test_ukuran_sendiri_ditandai_punya_ukuran_sendiri()
    {
        $denah = $this->denah();

        $this->assertFalse(LayoutZone::factory()->make()->punya_ukuran_sendiri);
        $this->assertTrue(LayoutZone::factory()->make(['width_m' => 15, 'height_m' => 15])->punya_ukuran_sendiri);
    }

    /** Satu angka saja tidak cukup — ukuran butuh lebar DAN tinggi. */
    public function test_ukuran_setengah_tidak_dianggap_punya_ukuran_sendiri()
    {
        $zona = LayoutZone::factory()->make(['width_m' => 15, 'height_m' => null]);

        $this->assertFalse($zona->punya_ukuran_sendiri);
    }

    public function test_angka_meter_membuang_desimal_nol()
    {
        $this->assertSame('15', LayoutZone::angka(15.0));
        $this->assertSame('22,5', LayoutZone::angka(22.5));
    }

    // ── Mengisi ukuran membentuk kotaknya ──────────────────────────────────

    /** Inti fiturnya: angka yang diketik menentukan bentuk kotaknya. */
    public function test_mengisi_ukuran_membentuk_ulang_kotaknya()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'w' => 62,
            'h' => 50,
        ]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '20')
            ->set('arenaTinggi', '10')
            ->call('simpanZona')
            ->assertHasNoErrors();

        $zona->refresh();

        // 20 m dari papan 40 m = 50%; 10 m dari papan 20 m = 50%.
        $this->assertSame('50.000', (string) $zona->w);
        $this->assertSame('50.000', (string) $zona->h);
        $this->assertSame(20.0, (float) $zona->width_m);
    }

    /** Yang diketik dan yang tergambar harus sama — itu seluruh gunanya. */
    public function test_ukuran_tergambar_selebar_angkanya()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '30')
            ->set('arenaTinggi', '5')
            ->call('simpanZona');

        $this->assertSame('30 × 5 m', $zona->fresh()->ukuranMeter($denah));
    }

    public function test_mengosongkan_ukuran_kembali_menghitung_dari_papan()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->arenaMeter(20, 10)->create([
            'competition_layout_id' => $denah->id,
            'w' => 50,
            'h' => 50,
        ]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '')
            ->set('arenaTinggi', '')
            ->call('simpanZona')
            ->assertHasNoErrors();

        $zona->refresh();

        $this->assertNull($zona->width_m);
        $this->assertSame('20 × 10 m', $zona->ukuranMeter($denah));
    }

    /** Hanya arena yang punya medan ukuran ini; zona lain tak boleh tersentuh. */
    public function test_zona_selain_arena_tidak_menyimpan_ukuran_sendiri()
    {
        $denah = $this->denah();
        $zona = LayoutZone::factory()->dp(1)->create(['competition_layout_id' => $denah->id]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '15')
            ->set('arenaTinggi', '15')
            ->call('simpanZona')
            ->assertHasNoErrors();

        $this->assertNull($zona->fresh()->width_m);
    }

    // ── Arena yang tak muat di papannya ────────────────────────────────────

    /**
     * Arena yang lebih besar dari papannya DITOLAK, bukan diperkecil diam-diam.
     *
     * Memperkecilnya akan membuat kotak "20 m" tergambar 15 m — kebohongan
     * yang sama, cuma dari arah sebaliknya.
     */
    public function test_arena_lebih_besar_dari_papan_ditolak()
    {
        $denah = $this->denah(['ground_width' => 20, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '30')
            ->set('arenaTinggi', '10')
            ->call('simpanZona')
            ->assertHasErrors('arenaLebar');

        $this->assertNull($zona->fresh()->width_m, 'Zona tak boleh tersimpan saat ukurannya ditolak.');
    }

    /** Pesannya menyebut angka yang salah, supaya panitia tahu mana yang keliru. */
    public function test_penolakan_menyebut_ukuran_arena_dan_papan()
    {
        $denah = $this->denah(['ground_width' => 20, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '30')
            ->set('arenaTinggi', '10')
            ->call('simpanZona')
            ->assertSee('Arenanya 30 m, papannya cuma 20 m');
    }

    /** Arena yang membesar tapi masih muat digeser ke dalam, bukan dipotong. */
    public function test_arena_besar_digeser_muar_di_dalam_papan()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'x' => 70,
            'y' => 70,
        ]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->set('arenaLebar', '40')
            ->set('arenaTinggi', '20')
            ->call('simpanZona')
            ->assertHasNoErrors();

        $zona->refresh();

        $this->assertSame('0.000', (string) $zona->x, 'Arena selebar papan tak bisa mulai dari 70%.');
        $this->assertSame('100.000', (string) $zona->w, 'Ukurannya tetap penuh, bukan dipotong.');
    }

    // ── Seretan menulis ulang angkanya ─────────────────────────────────────

    /**
     * Menyeret arena mengubah angka meternya.
     *
     * Kalau gambar boleh berubah tanpa angkanya ikut, labelnya berhenti cocok
     * dengan kotaknya — persis yang ingin dicegah kolom ini.
     */
    public function test_menyeret_arena_menulis_ulang_angka_meternya()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->arenaMeter(20, 10)->create([
            'competition_layout_id' => $denah->id,
            'w' => 50,
            'h' => 50,
        ]);

        $this->editor($denah)
            ->call('simpanPosisi', $zona->id, 10, 10, 25, 25);

        $zona->refresh();

        // 25% dari 40 m = 10 m; 25% dari 20 m = 5 m.
        $this->assertSame('10.00', (string) $zona->width_m);
        $this->assertSame('5.00', (string) $zona->height_m);
        $this->assertSame('10 × 5 m', $zona->ukuranMeter($denah));
    }

    /** Zona tanpa ukuran sendiri tetap tanpa ukuran sesudah diseret — kalau
     *  tidak, menyeret arena sekali membekukan ukurannya seumur hidup. */
    public function test_menyeret_zona_biasa_tidak_membekukan_ukurannya()
    {
        $denah = $this->denah();
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'w' => 50,
            'h' => 50,
        ]);

        $this->editor($denah)->call('simpanPosisi', $zona->id, 10, 10, 25, 25);

        $this->assertNull($zona->fresh()->width_m);
    }

    /** Panel kanan harus ikut angka sesudah diseret, bukan angka sebelum. */
    public function test_panel_ikut_angka_sesudah_seretan()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->arenaMeter(20, 10)->create([
            'competition_layout_id' => $denah->id,
            'w' => 50,
            'h' => 50,
        ]);

        $this->editor($denah)
            ->call('pilihZona', $zona->id)
            ->call('simpanPosisi', $zona->id, 10, 10, 25, 25)
            ->assertSet('arenaLebar', '10')
            ->assertSet('arenaTinggi', '5');
    }

    // ── Papan berubah ukuran ───────────────────────────────────────────────

    /**
     * Arena berukuran tetap harus 15 m di papan mana pun.
     *
     * Kalau persennya dibiarkan saat papan berubah, angka di layar berubah
     * jadi 22,5 m dan labelnya berhenti cocok dengan lapangan.
     */
    public function test_mengubah_papan_menjaga_angka_arena()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->arenaMeter(20, 10)->create([
            'competition_layout_id' => $denah->id,
            'w' => 50,
            'h' => 50,
        ]);

        $this->editor($denah)
            ->call('bukaPapanModal')
            ->set('papanLebar', '80')
            ->set('papanTinggi', '40')
            ->call('simpanPapan')
            ->assertHasNoErrors();

        $zona->refresh();

        $this->assertSame('25.000', (string) $zona->w, 'Papan dua kali lipat → persennya separuh.');
        $this->assertSame('20 × 10 m', $zona->ukuranMeter($denah->fresh()));
    }

    /** Arena yang tak muat lagi di papan yang menyusut dilepas ukurannya. */
    public function test_arena_tak_muat_lepaskan_ukuran_saat_papan_menyusut()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->arenaMeter(30, 15)->create([
            'competition_layout_id' => $denah->id,
            'w' => 75,
            'h' => 75,
        ]);

        $this->editor($denah)
            ->call('bukaPapanModal')
            ->set('papanLebar', '20')
            ->set('papanTinggi', '10')
            ->call('simpanPapan');

        $this->assertNull(
            $zona->fresh()->width_m,
            'Arena 30 m di papan 20 m tak punya arti; ukurannya dilepas, bukan dipotong.'
        );
    }

    /** Papan berubah tak boleh menyentuh zona yang memang tak berukuran tetap. */
    public function test_papan_berubah_tidak_menyentuh_zona_biasa()
    {
        $denah = $this->denah(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'w' => 50,
            'h' => 50,
        ]);

        $this->editor($denah)
            ->call('bukaPapanModal')
            ->set('papanLebar', '80')
            ->set('papanTinggi', '40')
            ->call('simpanPapan');

        $this->assertSame('50.000', (string) $zona->fresh()->w);
    }

    // ── Duplikat ───────────────────────────────────────────────────────────

    /** Salinan arena mewarisi ukurannya, kalau tidak salinannya menggambar
     *  ukuran yang berbeda dari aslinya. */
    public function test_salinan_arena_mewarisi_ukuran_meternya()
    {
        $denah = $this->denah();
        $zona = LayoutZone::factory()->arenaMeter(15, 15)->create(['competition_layout_id' => $denah->id]);

        $this->editor($denah)
            ->call('duplikatZona', $zona->id)
            ->assertHasNoErrors();

        $salin = $denah->zones()->where('id', '!=', $zona->id)->firstOrFail();

        $this->assertSame(15.0, (float) $salin->width_m);
        $this->assertSame(15.0, (float) $salin->height_m);
    }

    // ── Yang terlihat panitia ──────────────────────────────────────────────

    /** Kolom isian ukuran cuma muncul untuk arena. */
    public function test_kolom_ukuran_muncul_hanya_untuk_arena()
    {
        $denah = $this->denah();
        $arena = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);
        $dp = LayoutZone::factory()->dp(1)->create(['competition_layout_id' => $denah->id]);

        $this->editor($denah)
            ->call('pilihZona', $arena->id)
            ->assertSee('Ukuran Arena (m)')
            ->call('pilihZona', $dp->id)
            ->assertDontSee('Ukuran Arena (m)');
    }

    /** Arena berukuran tetap menuliskan angkanya di kotaknya di kanvas. */
    public function test_ukuran_arena_tertulis_di_kanvas()
    {
        $denah = $this->denah();
        LayoutZone::factory()->arenaMeter(15, 15)->create([
            'competition_layout_id' => $denah->id,
            'name' => 'Arena Utama',
        ]);

        $html = view('partials.denah-kanvas', [
            'layout' => $denah->load('zones'),
            'interaktif' => false,
        ])->render();

        $this->assertStringContainsString('class="zone-ukur"', $html);
        $this->assertStringContainsString('15 × 15 m', $html);
    }

    /** Zona lain tak diberi baris ukuran di kanvas — angkanya cuma simpulan
     *  dari papan dan tak menambah apa pun di kotak yang sudah kecil. */
    public function test_zona_lain_tidak_menulis_ukuran_di_kanvas()
    {
        $denah = $this->denah();
        LayoutZone::factory()->dp(1)->create(['competition_layout_id' => $denah->id]);

        $html = view('partials.denah-kanvas', [
            'layout' => $denah->load('zones'),
            'interaktif' => false,
        ])->render();

        $this->assertStringNotContainsString('class="zone-ukur"', $html);
    }

    /** Ukuran arena ikut ke halaman publik — justru itu yang dicari peserta. */
    public function test_ukuran_arena_ikut_di_halaman_publik()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $denah = CompetitionLayout::factory()->terbit()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->arenaMeter(15, 15)->create(['competition_layout_id' => $denah->id]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('15 × 15 m');
    }

    /** Dan ikut tercetak: lembar PDF-nya dibaca di lapangan. */
    public function test_ukuran_arena_ikut_tercetak_di_pdf()
    {
        $denah = $this->denah();
        LayoutZone::factory()->arenaMeter(15, 15)->create(['competition_layout_id' => $denah->id]);
        $denah->load('zones');

        $html = view('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $denah->eventner,
            'tinggiKanvas' => '120mm',
            'ukuranLabel' => '13px',
        ])->render();

        $this->assertStringContainsString('15 × 15 m', $html);
    }
}

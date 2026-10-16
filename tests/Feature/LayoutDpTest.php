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
 * DP (Daerah Persiapan) — aturan penomoran dan susun-otomatisnya.
 *
 * Yang dijaga di sini urutannya, bukan tampilannya: DP 1 selalu di ujung yang
 * paling dekat arena. Kalau terbalik, pasukan berjalan ke arah yang salah.
 */
class LayoutDpTest extends TestCase
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

    // ── Aturan penomoran: DP 1 terdekat arena ──────────────────────────────

    /**
     * Barisan di BAWAH arena: DP 1 di kanan, DP N di kiri.
     *
     * Diukur dari koordinatnya, bukan namanya — nama bisa benar sementara
     * koordinatnya tertukar.
     */
    public function test_dp_1_ada_di_ujung_terdekat_arena_barisan_bawah()
    {
        $layout = CompetitionLayout::factory()->create(['ground_width' => 40, 'ground_height' => 22]);

        $baris = LayoutZone::susunDp(5, 'bawah', 5.0, 40.0, 22.0);
        $dp = collect($baris)->where('type', LayoutZone::TYPE_DP)->keyBy('dp_number');

        $this->assertCount(5, $dp);
        $this->assertGreaterThan(
            (float) $dp[5]['x'],
            (float) $dp[1]['x'],
            'Barisan di bawah arena: DP 1 harus di ujung kanan (paling dekat arena), DP 5 di kiri.'
        );
    }

    /** Barisan di ATAS arena dicerminkan: DP 1 di kiri. */
    public function test_dp_1_ada_di_ujung_terdekat_arena_barisan_atas()
    {
        $baris = LayoutZone::susunDp(5, 'atas', 5.0, 40.0, 22.0);
        $dp = collect($baris)->where('type', LayoutZone::TYPE_DP)->keyBy('dp_number');

        $this->assertLessThan(
            (float) $dp[5]['x'],
            (float) $dp[1]['x'],
            'Barisan di atas arena: DP 1 harus di ujung kiri.'
        );
    }

    /** Barisan di samping kiri: DP 1 paling atas. */
    public function test_dp_1_ada_di_ujung_terdekat_arena_barisan_samping()
    {
        $baris = LayoutZone::susunDp(5, 'samping', 4.0, 40.0, 22.0);
        $dp = collect($baris)->where('type', LayoutZone::TYPE_DP)->keyBy('dp_number');

        $this->assertLessThan(
            (float) $dp[5]['y'],
            (float) $dp[1]['y'],
            'Barisan di samping: DP 1 harus di ujung atas, DP 5 di bawah.'
        );
    }

    public function test_barisan_tidak_saling_menimpa()
    {
        $baris = LayoutZone::susunDp(5, 'bawah', 5.0, 40.0, 22.0);
        $dp = collect($baris)->where('type', LayoutZone::TYPE_DP)->sortBy('x')->values();

        for ($i = 1; $i < $dp->count(); $i++) {
            $sebelumnya = $dp[$i - 1];
            $this->assertGreaterThanOrEqual(
                (float) $sebelumnya['x'] + (float) $sebelumnya['w'],
                (float) $dp[$i]['x'],
                'DP '.$dp[$i]['dp_number'].' menimpa DP '.$sebelumnya['dp_number'].'.'
            );
        }
    }

    // ── susunDp() di editor ───────────────────────────────────────────────

    public function test_susun_dp_membuat_seluruh_barisan()
    {
        [$denah, $panel] = $this->editor();

        $panel->set('dpJumlah', '5')
            ->set('dpLetak', 'bawah')
            ->set('dpTinggi', '5')
            ->call('susunDp')
            ->assertHasNoErrors();

        $dp = LayoutZone::where('competition_layout_id', $denah->id)->dp()->get();

        $this->assertCount(5, $dp);
        $this->assertSame([1, 2, 3, 4, 5], $dp->pluck('dp_number')->sort()->values()->all());
        $this->assertSame(
            ['DP 1', 'DP 2', 'DP 3', 'DP 4', 'DP 5'],
            $dp->sortBy('dp_number')->pluck('name')->all()
        );
    }

    /**
     * IDEMPOTEN: menekan tombolnya dua kali menghasilkan denah yang sama.
     *
     * Tanpa membuang DP lama lebih dulu, klik kedua menumpuk barisan baru di
     * atas yang lama — hasilnya sepuluh kotak bertumpuk.
     */
    public function test_susun_dp_dua_kali_tidak_menumpuk()
    {
        [$denah, $panel] = $this->editor();

        $panel->set('dpJumlah', '5')->set('dpTinggi', '5')->call('susunDp');
        $pertama = $this->ringkasDp($denah);

        $panel->set('dpJumlah', '5')->set('dpTinggi', '5')->call('susunDp');

        $kedua = LayoutZone::where('competition_layout_id', $denah->id)->dp()->get();

        $this->assertCount(5, $kedua, 'Susun DP dua kali harus menghasilkan lima DP, bukan sepuluh.');
        $this->assertSame(
            $pertama,
            $this->ringkasDp($denah),
            'Hasil susun kedua harus sama persis dengan yang pertama.'
        );
    }

    /**
     * Potret satu barisan DP tanpa kolom `id`.
     *
     * ID-nya wajib berbeda — susun ulang membuang baris lama lalu membuat yang
     * baru. Yang harus sama adalah nama, nomor, dan geometrinya.
     */
    private function ringkasDp(CompetitionLayout $denah): array
    {
        return LayoutZone::where('competition_layout_id', $denah->id)
            ->dp()
            ->orderBy('dp_number')
            ->get()
            ->map(fn (LayoutZone $z) => [
                'dp_number' => $z->dp_number,
                'name' => $z->name,
                'gaya' => $z->gaya,
            ])
            ->all();
    }

    /** Jumlah DP bisa diubah — aturan PBB memang bukan selalu lima. */
    public function test_jumlah_dp_bisa_diubah()
    {
        [$denah, $panel] = $this->editor();

        $panel->set('dpJumlah', '5')->call('susunDp');
        $this->assertSame(5, LayoutZone::where('competition_layout_id', $denah->id)->dp()->count());

        $panel->set('dpJumlah', '3')->call('susunDp');
        $this->assertSame(3, LayoutZone::where('competition_layout_id', $denah->id)->dp()->count());
        $this->assertSame(
            [1, 2, 3],
            LayoutZone::where('competition_layout_id', $denah->id)->dp()->pluck('dp_number')->sort()->values()->all()
        );
    }

    /** Jumlah DP di luar 1–12 ditolak, bukan diterima lalu meledak. */
    public function test_jumlah_dp_di_luar_batas_ditolak()
    {
        [, $panel] = $this->editor();

        $panel->set('dpJumlah', '0')->call('susunDp')->assertHasErrors('dpJumlah');
        $panel->set('dpJumlah', '40')->call('susunDp')->assertHasErrors('dpJumlah');
    }

    /**
     * Zona lain yang digambar panitia tidak ikut terhapus.
     *
     * Arena, tribun, dan pos penilaian itu hasil kerja manual — susun DP cuma
     * mengurus barisannya sendiri.
     */
    public function test_susun_dp_tidak_menghapus_zona_lain()
    {
        [$denah, $panel] = $this->editor();

        $tribun = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_TRIBUN,
            'name' => 'Tribun Penonton',
        ]);
        $pos = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_POS_PENILAIAN,
            'name' => 'Pos Penilaian A',
        ]);

        $panel->set('dpJumlah', '5')->call('susunDp');

        $this->assertDatabaseHas('layout_zones', ['id' => $tribun->id]);
        $this->assertDatabaseHas('layout_zones', ['id' => $pos->id]);
    }

    /**
     * Arena bawaan dibuat hanya kalau papan belum punya arena.
     *
     * Arena yang sudah digambar panitia disesuaikan dengan lapangan sungguhan;
     * menggantinya diam-diam membuang penyesuaian itu.
     */
    public function test_arena_bawaan_dibuat_bila_belum_ada()
    {
        [$denah, $panel] = $this->editor();

        $panel->set('dpJumlah', '5')->set('dpLetak', 'bawah')->call('susunDp');

        $arena = LayoutZone::where('competition_layout_id', $denah->id)
            ->where('type', LayoutZone::TYPE_ARENA)->get();

        $this->assertCount(1, $arena);
        $this->assertSame('Arena Utama', $arena->first()->name);
    }

    public function test_arena_yang_sudah_digambar_tidak_diganti()
    {
        [$denah, $panel] = $this->editor();

        $arena = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_ARENA,
            'name' => 'Lapangan Utama',
            'x' => 20, 'y' => 20, 'w' => 50, 'h' => 40,
        ]);

        $panel->set('dpJumlah', '5')->call('susunDp');

        $arena->refresh();

        $this->assertSame('Lapangan Utama', $arena->name);
        $this->assertSame('50.000', $arena->w);
        $this->assertSame(
            1,
            LayoutZone::where('competition_layout_id', $denah->id)->where('type', LayoutZone::TYPE_ARENA)->count()
        );
    }

    /** Panah penunjuk arah ikut dibuat saat diminta, dan tak dibuat saat tidak. */
    public function test_panah_arah_mengikuti_pilihannya()
    {
        [$denah, $panel] = $this->editor();

        $panel->set('dpJumlah', '5')->set('dpDenganPanah', true)->call('susunDp');
        $this->assertSame(
            1,
            LayoutZone::where('competition_layout_id', $denah->id)->where('type', LayoutZone::TYPE_PANAH)->count()
        );

        $panel->set('dpDenganPanah', false)->call('susunDp');
        $this->assertSame(
            0,
            LayoutZone::where('competition_layout_id', $denah->id)->where('type', LayoutZone::TYPE_PANAH)->count()
        );
    }

    /** Panah yang digambar ulang panitia (namanya diubah) tidak ikut dibuang. */
    public function test_panah_buatan_sendiri_tidak_ikut_terhapus()
    {
        [$denah, $panel] = $this->editor();

        $panah = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_PANAH,
            'name' => 'Jalur masuk dari parkir',
        ]);

        $panel->set('dpJumlah', '5')->set('dpDenganPanah', false)->call('susunDp');

        $this->assertDatabaseHas('layout_zones', ['id' => $panah->id]);
    }

    // ── Zona & kanvas ─────────────────────────────────────────────────────

    public function test_zona_dp_baru_mendapat_nomor_berikutnya()
    {
        [$denah, $panel] = $this->editor();

        LayoutZone::factory()->dp(1)->create(['competition_layout_id' => $denah->id]);
        LayoutZone::factory()->dp(2)->create(['competition_layout_id' => $denah->id]);

        $panel->call('tambahZona', LayoutZone::TYPE_DP);

        $baru = LayoutZone::where('competition_layout_id', $denah->id)->dp()->orderByDesc('id')->first();

        $this->assertSame(3, $baru->dp_number);
        $this->assertSame('DP 3', $baru->name);
    }

    /**
     * Nomor DP di luar zona DP tak boleh tersimpan.
     *
     * Kalau ikut tertulis di tribun, daftar DP di halaman publik menampilkan
     * "DP 1" dua kali dan peserta bingung mana yang benar.
     */
    public function test_nomor_dp_tidak_menempel_di_zona_bukan_dp()
    {
        [$denah, $panel] = $this->editor();

        $tribun = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_TRIBUN,
            'name' => 'Tribun',
        ]);

        $panel->call('pilihZona', $tribun->id)
            ->set('nomorDp', '7')
            ->call('simpanZona');

        $this->assertNull($tribun->fresh()->dp_number);
    }

    /**
     * Posisi zona dijepit ke 0–100.
     *
     * Kanvas bisa menghasilkan angka di luar rentang saat zona diseret melewati
     * tepi. Tanpa jepitan, zonanya tersimpan di luar papan dan hilang selamanya.
     */
    public function test_posisi_zona_dijepit_ke_dalam_papan()
    {
        [$denah, $panel] = $this->editor();
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $panel->call('simpanPosisi', $zona->id, 150, -20, 300, 5);

        $zona->refresh();

        $this->assertSame('100.000', $zona->x);
        $this->assertSame('0.000', $zona->y);
        $this->assertSame('100.000', $zona->w);
    }

    /** Ukuran papan mengubah arti persen, tapi zona tidak digeser. */
    public function test_ubah_ukuran_papan_tidak_menggeser_zona()
    {
        [$denah, $panel] = $this->editor(['ground_width' => 40, 'ground_height' => 22]);
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'x' => 25, 'y' => 30, 'w' => 20, 'h' => 10,
        ]);

        $panel->call('bukaPapanModal')
            ->set('papanLebar', '60')
            ->set('papanTinggi', '30')
            ->call('simpanPapan');

        $this->assertSame('60.00', $denah->fresh()->ground_width);
        $this->assertSame('25.000', $zona->fresh()->x, 'Posisi zona adalah persen — ubah ukuran papan tak boleh menggesernya.');
    }

    public function test_hapus_zona_mengosongkan_pilihan()
    {
        [$denah, $panel] = $this->editor();
        $zona = LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $panel->call('pilihZona', $zona->id)->call('hapusZona', $zona->id);

        $panel->assertSet('selectedId', null);
        $this->assertDatabaseMissing('layout_zones', ['id' => $zona->id]);
    }

    public function test_duplikat_zona_menggeser_salinannya()
    {
        [$denah, $panel] = $this->editor();
        $zona = LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id, 'x' => 10, 'y' => 10,
        ]);

        $panel->call('duplikatZona', $zona->id);

        $salin = LayoutZone::where('competition_layout_id', $denah->id)->orderByDesc('id')->first();

        $this->assertNotSame($zona->id, $salin->id);
        $this->assertGreaterThan((float) $zona->x, (float) $salin->x, 'Salinan harus digeser, bukan menumpuk persis.');
    }

    /**
     * Zona di papan tanpa titik desimal tetap tampil sebagai persen utuh.
     *
     * Kolom x/y/w/h bertipe decimal — nilai mentahnya "25.000", benar untuk
     * perhitungan tapi salah kalau tercetak apa adanya ke atribut style.
     */
    public function test_gaya_zona_berupa_persen()
    {
        $zona = LayoutZone::factory()->make(['x' => 25, 'y' => 30, 'w' => 20.5, 'h' => 10]);

        $this->assertSame('left:25.000%;top:30.000%;width:20.500%;height:10.000%', $zona->gaya);
    }

    public function test_ukuran_zona_dihitung_dari_ukuran_papan()
    {
        $denah = CompetitionLayout::factory()->make(['ground_width' => 40, 'ground_height' => 20]);
        $zona = LayoutZone::factory()->make(['w' => 50, 'h' => 25]);

        $this->assertSame('20 × 5 m', $zona->ukuranMeter($denah));
    }
}

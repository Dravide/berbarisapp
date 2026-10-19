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
 * Garis pandu penyelaras di kanvas editor.
 *
 * Perilakunya hidup di JavaScript, dan tes PHP tak bisa menyeret apa pun —
 * jadi yang dijaga berkas ini kontrak antara markup, gaya, dan skripnya:
 * elemen panduannya ada, selektornya cocok dengan yang dicari skrip, dan
 * bawaannya memang tersembunyi.
 *
 * Yang TERAKHIR itu yang paling mudah rusak tanpa terasa: panduan yang
 * bawaannya tampil akan tercetak di PNG dan PDF — dan pembaca denah melihat
 * garis merah yang tak berarti apa-apa.
 */
class LayoutPanduTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kanvas editor HARUS lewat komponen: `@script`-nya butuh konteks Livewire.
     *
     * Isi blok skripnya keluar dua kali ter-escape dari Livewire: dulu jadi
     * entitas HTML (`>` jadi `&gt;`), lalu kutipnya jadi `\"` karena muatannya
     * diangkut sebagai JSON. Keduanya dibalikkan di sini — tanpa itu, yang
     * dicari cuma tak ketemu dan tesnya gagal tanpa alasan yang jelas.
     */
    private function editor(array $atributLayout = []): string
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
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->actingAs($user);

        $html = html_entity_decode(
            Livewire::test(LayoutShow::class, ['layout' => $denah])->html(),
            ENT_QUOTES | ENT_HTML5,
        );

        return str_replace('\\"', '"', $html);
    }

    public function test_kanvas_editor_punya_dua_elemen_pandu()
    {
        $html = $this->editor();

        $this->assertStringContainsString('data-pandu="tegak"', $html);
        $this->assertStringContainsString('data-pandu="datar"', $html);
    }

    /** Skrip mencari `[data-pandu="tegak"]` / `[data-pandu="datar"]`; nama itu
     *  harus sama persis, karena salah ketik cuma berarti panduannya diam. */
    public function test_skrip_kanvas_merujuk_kedua_elemen_pandu()
    {
        $html = $this->editor();

        $this->assertStringContainsString('[data-pandu="tegak"]', $html);
        $this->assertStringContainsString('[data-pandu="datar"]', $html);
    }

    /** Panduan menandai garis di PAPAN, jadi persennya persen papan. */
    public function test_panduan_dinyatakan_dalam_persen()
    {
        $this->assertStringContainsString('hasil.garis.nilai + ', $this->editor());
    }

    // ── Tak boleh bocor ke permukaan lain ──────────────────────────────────

    public function test_kanvas_publik_tak_punya_elemen_pandu()
    {
        $denah = CompetitionLayout::factory()->create();
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);
        $denah->load('zones');

        $html = view('partials.denah-kanvas', [
            'layout' => $denah,
            'interaktif' => false,
        ])->render();

        $this->assertStringNotContainsString('data-pandu="', $html);
    }

    /**
     * Denah terkunci tak menyiapkan panduan: tak ada seretan, tak ada garis.
     *
     * Yang dicari elemen `<span>`-nya, bukan `data-pandu` telanjang: nama itu
     * juga muncul di skripnya sebagai selektor, dan pencarian telanjang akan
     * gagal walaupun span-nya sudah tidak ada.
     */
    public function test_kanvas_terkunci_tak_punya_elemen_pandu()
    {
        $html = $this->editor(['is_locked' => true]);

        $this->assertStringNotContainsString('<span class="pandu', $html);
    }

    /**
     * Bawaan gaya pandunya tersembunyi.
     *
     * Kalau `display` bawaannya bukan `none`, garis merahnya ikut tergambar di
     * kanvas setiap kali halaman dibuka — dan di PNG serta PDF yang diunduh
     * dari kanvas itu.
     */
    public function test_gaya_pandu_bawaannya_tersembunyi()
    {
        $this->assertMatchesRegularExpression(
            '/\.denah-kanvas \.pandu \{[^}]*display:\s*none/s',
            $this->editor(),
        );
    }

    /** Panduan menempel tepi papan dibedakan warnanya dari panduan sejajar
     *  zona lain — dua hal berbeda bagi yang membaca denah. */
    public function test_panduan_tepi_papan_punya_warna_sendiri()
    {
        $this->assertMatchesRegularExpression(
            '/\.pandu\.pandu-papan \{[^}]*background:/s',
            $this->editor(),
        );
    }

    /**
     * Skripnya mencari elemen pandu SAAT MENAMPILKANNYA, bukan sekali di awal.
     *
     * Elemen itu tak ada di DOM selama denah terkunci, dan Livewire memorph
     * kanvas di tempat — referensi yang ditangkap sekali jadi elemen yatim
     * begitu kuncinya dibuka, dan garisnya berhenti muncul tanpa error apa pun.
     */
    public function test_elemen_pandu_dicari_saat_dipakai_bukan_di_awal()
    {
        $html = $this->editor();

        $this->assertStringNotContainsString('const panduTegak = kanvas.querySelector', $html);

        $awal = mb_strpos($html, 'const tampilkanPandu');
        $this->assertNotFalse($awal, 'tampilkanPandu tak ditemukan di skrip kanvas.');

        $badan = mb_substr($html, $awal, 900);

        $this->assertStringContainsString('kanvas.querySelector', $badan);
    }
}

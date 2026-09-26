<?php

namespace Tests\Feature;

use App\Livewire\Public\EventResult;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pemilih kategori + grup di /event/{slug}/results.
 *
 * Dua hal yang dijaga di sini:
 *
 *  1. Kedua select berdampingan, bukan bertumpuk. Wrapper-nya dulu memakai
 *     utility Bootstrap (d-flex / justify-content-center) padahal halaman ini
 *     memakai layout Tailwind — kelas itu tidak berefek, jadi tiap select
 *     jadi blok selebar penuh dan halaman tampak berantakan persis saat
 *     tingkat lomba punya grup.
 *  2. Kategori terpilih benar-benar ditandai. Dulu nilai select diikat
 *     wire:model.live sekaligus wire:change ke method yang sama; sekarang
 *     hanya wire:change, jadi penandaannya harus eksplisit lewat @selected.
 */
class PublicResultsSelectorTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionCategory $levelLain;

    private CompetitionGroup $groupA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'lomba-pbb',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Inti',
        ]);
        $this->levelLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Cadangan',
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);

        Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'nama_sekolah' => 'SMPN 1',
            'status_berkas' => 'confirmed',
        ]);
    }

    private function panel()
    {
        return Livewire::test(EventResult::class, ['slug' => 'lomba-pbb']);
    }

    /**
     * Wrapper pemilih harus memakai kelas Tailwind yang benar-benar dimuat.
     *
     * Kelas Bootstrap seperti d-flex nol definisinya di CSS halaman ini —
     * memakainya kembali akan mengulang bug yang sama tanpa ketahuan, karena
     * markup-nya tetap "terlihat benar" saat dibaca.
     */
    public function test_pemilih_tidak_memakai_kelas_bootstrap_yang_mati()
    {
        $html = $this->panel()->html();

        // Potongan pemilih saja — bukan seluruh halaman.
        $potongan = substr($html, (int) strpos($html, 'Pilih Kategori Lomba'), 1600);

        $this->assertNotSame('', $potongan, 'Pemilih kategori tidak ditemukan di halaman.');
        $this->assertStringNotContainsString('d-flex', $potongan);
        $this->assertStringNotContainsString('justify-content-center', $potongan);
        $this->assertStringNotContainsString('form-select', $potongan);

        // Yang dipakai sebagai gantinya: flex Tailwind.
        $this->assertStringContainsString('flex', $potongan);
    }

    /** Kedua select berada dalam satu wrapper, bukan dua blok terpisah. */
    public function test_kategori_dan_grup_duduk_di_satu_wrapper()
    {
        $html = $this->panel()->html();

        $posKategori = strpos($html, 'Pilih Kategori Lomba');
        $posGrup = strpos($html, 'Pilih Grup');

        $this->assertNotFalse($posKategori);
        $this->assertNotFalse($posGrup);
        $this->assertGreaterThan($posKategori, $posGrup, 'Select grup harus setelah kategori.');

        // Kalau wrapper-nya lepas, di antara keduanya akan ada penanda wadah
        // lain. Jarak karakter saja tidak bisa dipakai: nama kategori panjang
        // membuat jaraknya melebar tanpa wrapper-nya rusak.
        $antara = substr($html, $posKategori, $posGrup - $posKategori);

        $this->assertStringNotContainsString('container-landing', $antara);
        $this->assertStringNotContainsString('</form>', $antara);
    }

    /** Kategori aktif ditandai selected, bukan sekadar jadi opsi pertama. */
    public function test_kategori_terpilih_ditandai()
    {
        $html = $this->panel()->html();

        $this->assertMatchesRegularExpression(
            '/value="'.$this->level->id.'"[^>]*selected/',
            $html,
            'Kategori pertama tidak ditandai selected.'
        );
    }

    /** Ganti kategori memperbarui pilihan yang ditandai. */
    public function test_ganti_kategori_memindahkan_penanda()
    {
        $html = $this->panel()->call('switchCategory', $this->levelLain->id)->html();

        $this->assertMatchesRegularExpression(
            '/value="'.$this->levelLain->id.'"[^>]*selected/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/value="'.$this->level->id.'"[^>]*selected/',
            $html
        );
    }

    /** Ganti kategori mengosongkan grup — grup tingkat lama bukan milik tingkat baru. */
    public function test_ganti_kategori_mengosongkan_pilihan_grup()
    {
        $this->panel()
            ->call('switchGroup', $this->groupA->id)
            ->assertSet('selectedGroupId', (string) $this->groupA->id)
            ->call('switchCategory', $this->levelLain->id)
            ->assertSet('selectedGroupId', '');
    }

    /** Tingkat bergrup: select grup ikut tampil, bukan cuma kategori. */
    public function test_select_grup_muncul_pada_tingkat_bergrup()
    {
        $html = $this->panel()->html();

        $this->assertStringContainsString('Pilih Grup', $html);
        $this->assertStringContainsString('Peringkat Gabungan', $html);
        $this->assertStringContainsString('Grup A', $html);
    }

    /** Halaman tetap 200 setelah perubahan markup. */
    public function test_halaman_tetap_terbuka()
    {
        $this->get('/event/lomba-pbb/results')->assertOk();
    }
}

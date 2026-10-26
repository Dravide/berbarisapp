<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScoreboardSwitchCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $catA;
    private CompetitionCategory $catB;
    private AssessmentCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-SWITCH',
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->catA = CompetitionCategory::factory()->child($parent)->for($this->eventner, 'eventner')->create(['name' => 'Kategori A']);
        $this->catB = CompetitionCategory::factory()->child($parent)->for($this->eventner, 'eventner')->create(['name' => 'Kategori B']);

        // Format nilai: 1 kategori penilaian → 1 sub → 1 kriteria bobot 1
        $assessmentCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->catA->id,
            'name' => 'Penilaian Umum',
            'sort_order' => 1,
        ]);
        $subCategory = AssessmentSubCategory::create([
            'assessment_category_id' => $assessmentCategory->id,
            'name' => 'Sub Umum',
            'sort_order' => 1,
        ]);
        $this->criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subCategory->id,
            'name' => 'Teknik',
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    public function test_ganti_kategori_lewat_dropdown_data_ranking_berubah()
    {
        $regA = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catA->id,
            'nama_sekolah' => 'SMP Alpha',
        ]);
        $regB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catB->id,
            'nama_sekolah' => 'SMP Beta',
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $regA->id,
            'assessment_criteria_id' => $this->criteria->id,
            'score' => 20,
        ]);

        $component = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ]);

        // Awal: kategori pertama (A, urut nama) → SMP Alpha tampil
        $component->assertSee('SMP Alpha');

        // Tekan tab kategori B
        $component->call('switchCategory', $this->catB->id);

        // Data ranking harus ikut berubah → SMP Beta muncul, Alpha hilang
        $component->assertSee('SMP Beta');
        $component->assertDontSee('SMP Alpha');
    }

    /**
     * Pemilih kategori berupa TAB yang selalu ada.
     *
     * Dulu pemilihnya dropdown yang dibungkus `!$selectedChampionCategoryId`:
     * begitu kategori juara dipilih, seluruh blok pemilih ter-render hilang dan
     * tidak ada jalan kembali ke kategori lomba tanpa menyunting URL. Tes ini
     * menjaga dua hal sekaligus — tab tetap tampil di mode juara, dan menekan
     * tab kategori lomba mengembalikan mode juara ke mode biasa.
     */
    public function test_pilihan_kategori_tetap_ada_di_mode_juara_dan_bisa_kembali()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catA->id,
            'nama_sekolah' => 'SMP Alpha',
        ]);

        $champion = \App\Models\ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
        ]);

        $component = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ]);

        // Tab kategori lomba dan tab juara sama-sama tampil.
        $component->assertSee('Kategori A')->assertSee('Juara Umum');

        $component->call('switchChampionCategory', $champion->id);
        $component->assertSet('selectedChampionCategoryId', $champion->id);

        // Mode juara TIDAK boleh menyembunyikan tab kategori lomba.
        $component->assertSee('Kategori A')->assertSee('Kategori B');

        // Kembali ke kategori lomba dari mode juara.
        $component->call('switchCategory', $this->catA->id);

        $component->assertSet('selectedChampionCategoryId', null)
            ->assertSee('SMP Alpha');
    }

    /**
     * Pemilih kategori harus benar-benar berada DI DALAM komponen Livewire.
     *
     * Regresi yang tes ini kunci: view diawali `<style>` sebagai elemen
     * pertama, sehingga Livewire menempelkan `wire:id`/`wire:snapshot` ke tag
     * <style> itu — bukan ke pembungkus. Seluruh kartu, termasuk pemilih
     * kategori, jadi SAUDARA komponen, bukan bagian di dalamnya, dan
     * `wire:change` tidak pernah terpasang. Di browser dropdown bisa dipilih
     * tapi tidak terjadi apa-apa; `->call(...)` tetap lulus karena memanggil
     * method langsung tanpa menyentuh DOM sama sekali. Karena itu tes ini
     * memeriksa MARKUP, bukan method.
     */
    public function test_pemilih_kategori_berada_di_dalam_root_komponen()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catA->id,
            'nama_sekolah' => 'SMP Alpha',
        ]);

        $html = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ])->html();

        // Root komponen harus elemen pembungkus, bukan <style>.
        $this->assertDoesNotMatchRegularExpression(
            '/<style[^>]*wire:(id|snapshot)/',
            $html,
            'wire:id menempel pada <style>: Livewire tidak akan mengikat wire:change di dalamnya.'
        );

        // Livewire menulis `wire:key` lebih dulu, baru `wire:snapshot`/`wire:id`.
        $this->assertSame(
            1,
            preg_match('/<div[^>]*wire:snapshot/', $html, $cocok, PREG_OFFSET_CAPTURE),
            'Root komponen harus berupa <div>.'
        );
        $rootStart = $cocok[0][1];

        // Dropdown harus muncul SETELAH root dan pada kedalaman > 0,
        // artinya berada di dalam subtree komponen.
        $pilihPos = strpos($html, 'id="pilih-kategori"', $rootStart);
        $this->assertNotFalse($pilihPos, 'Dropdown pemilih kategori tidak ditemukan.');

        $segment = substr($html, $rootStart, $pilihPos - $rootStart);
        $depth = preg_match_all('/<div\b/i', $segment) - preg_match_all('/<\/div>/i', $segment);

        $this->assertGreaterThanOrEqual(
            1,
            $depth,
            'Dropdown berada di luar root komponen — wire:change tidak akan terpasang.'
        );
    }

    /** Setiap pilihan kategori ada di dalam satu dropdown, bukan tab. */
    public function test_pemilih_kategori_berupa_satu_dropdown()
    {
        $html = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ])->html();

        // Satu elemen <select> dengan id ini memuat kedua jenis kategori.
        $this->assertMatchesRegularExpression('/<select[^>]*id="pilih-kategori"/', $html);
        $this->assertMatchesRegularExpression('/value="cat:' . $this->catA->id . '"/', $html);
        $this->assertMatchesRegularExpression('/value="cat:' . $this->catB->id . '"/', $html);
        $this->assertMatchesRegularExpression('/wire:change="switchFromDropdown\(\$event\.target\.value\)"/', $html);

        // Tab lama sudah tidak dipakai.
        $this->assertStringNotContainsString('wire:click="switchCategory(', $html);
    }

    /** Pilihan berawalan `champ:` membuka mode juara, dan bisa kembali. */
    public function test_dropdown_bisa_pindah_ke_juara_dan_kembali()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catA->id,
            'nama_sekolah' => 'SMP Alpha',
        ]);

        $champion = \App\Models\ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
        ]);

        $component = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ]);

        // Opsi juara ikut tampil di dropdown yang sama.
        $component->assertSee('Kategori Juara')->assertSee('Juara Umum');

        $component->call('switchFromDropdown', 'champ:' . $champion->id);
        $component->assertSet('selectedChampionCategoryId', $champion->id);

        // Mode juara tidak boleh menyembunyikan pilihan kategori lomba.
        $component->assertSee('Kategori A')->assertSee('Kategori B');

        $component->call('switchFromDropdown', 'cat:' . $this->catA->id);
        $component->assertSet('selectedChampionCategoryId', null)->assertSee('SMP Alpha');
    }

    /** Nilai DOM tidak dipercaya: id milik event lain ditolak. */
    public function test_dropdown_menolak_kategori_milik_event_lain()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $kategoriAsing = CompetitionCategory::factory()->for($lain, 'eventner')->create();

        $component = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ]);

        $semula = $component->get('selectedCategoryId');

        $component->call('switchFromDropdown', 'cat:' . $kategoriAsing->id);

        $component->assertSet('selectedCategoryId', $semula);
        $component->assertHasErrors('selectedCategoryId');
    }

    /** Nilai tak dikenal (termasuk kosong) tidak mengubah apa pun. */
    public function test_dropdown_menolak_nilai_tak_dikenal()
    {
        $component = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ]);

        $semula = $component->get('selectedCategoryId');

        $component->call('switchFromDropdown', 'ngawur');
        $component->assertSet('selectedCategoryId', $semula)->assertHasErrors('selectedCategoryId');

        $component->call('switchFromDropdown', '');
        $component->assertSet('selectedCategoryId', $semula)->assertHasErrors('selectedCategoryId');
    }
}

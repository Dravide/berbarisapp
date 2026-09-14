<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Livewire\Eventner\FormatNilai\Import;
use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan audit #19, #31, #33 — gerbang fitur dan scoping Bina Rubrik.
 *
 * #19: halaman unduh + seluruh aksi FormatNilaiController tidak punya gerbang
 *      fitur berbayar; hanya Builder yang dijaga FeatureGatedComponent.
 * #31: unduhan rubrik per tingkat membuang rubrik global (NULL).
 * #33: activeTab ditulis langsung jadi competition_category_id tanpa
 *      memeriksa bahwa tingkat itu milik eventner ini.
 */
class FormatNilaiGateTest extends TestCase
{
    use RefreshDatabase;

    private function buatEventner(array $atribut): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
        ], $atribut));
    }

    private function eventnerTerkunci(): Eventner
    {
        // Free, trial habis → fitur locked_free terkunci.
        return $this->buatEventner([
            'plan' => 'free',
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    private function tingkat(Eventner $eventner, string $nama = 'PBB Beregu'): CompetitionCategory
    {
        $parent = CompetitionCategory::factory()->for($eventner, 'eventner')->create();

        return CompetitionCategory::factory()->child($parent)
            ->for($eventner, 'eventner')
            ->create(['name' => $nama]);
    }

    /** #19 — unduhan PDF rubrik ikut terkunci untuk event tanpa paket. */
    public function test_unduhan_pdf_rubrik_terkunci_untuk_event_gratis()
    {
        $eventner = $this->eventnerTerkunci();
        $this->actingAs($eventner->user);

        $this->get(route('eventner.format-nilai.pdf'))->assertForbidden();
    }

    /** #19 — template Excel juga terkunci. */
    public function test_unduhan_template_terkunci_untuk_event_gratis()
    {
        $eventner = $this->eventnerTerkunci();
        $this->actingAs($eventner->user);

        $this->get(route('eventner.format-nilai.template'))->assertForbidden();
    }

    /** #19 — halaman unduh rubrik (Livewire) juga terkunci. */
    public function test_halaman_unduh_terkunci_untuk_event_gratis()
    {
        $eventner = $this->eventnerTerkunci();
        $this->actingAs($eventner->user);

        Livewire::test(\App\Livewire\Eventner\FormatNilai\Download::class)
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    /** Event berbayar tetap bisa mengunduh — gerbangnya tidak terlalu ketat. */
    public function test_unduhan_pdf_tetap_terbuka_untuk_event_berbayar()
    {
        $eventner = $this->buatEventner([
            'plan' => 'paid',
            'saas_plan_id' => null,
        ]);
        $this->actingAs($eventner->user);

        $this->get(route('eventner.format-nilai.pdf'))->assertOk();
    }

    /**
     * #31 — rubrik global (competition_category_id NULL) ikut terunduh saat
     * memfilter per tingkat: rubrik itu memang dipakai menilai peserta di
     * tingkat mana pun.
     */
    public function test_unduhan_per_tingkat_menyertakan_rubrik_global()
    {
        $eventner = $this->buatEventner(['plan' => 'paid']);
        $tingkat = $this->tingkat($eventner);

        AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => 'Rubrik Tingkat Ini',
            'sort_order' => 1,
        ]);
        AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => null,
            'name' => 'Rubrik Umum',
            'sort_order' => 2,
        ]);

        $this->actingAs($eventner->user);

        // Halaman unduh per tingkat memuat kedua rubrik.
        $kategori = Livewire::test(\App\Livewire\Eventner\FormatNilai\Download::class)
            ->set('selectedLevelId', $tingkat->id)
            ->get('categories');

        $this->assertTrue(
            $kategori->pluck('name')->contains('Rubrik Tingkat Ini')
                && $kategori->pluck('name')->contains('Rubrik Umum'),
            'Rubrik global harus ikut terunduh bersama rubrik tingkat ini.'
        );
    }

    /**
     * #33 — rubrik yang dibuat saat tab menunjuk tingkat event LAIN tidak
     * boleh tersimpan dengan competition_category_id tingkat itu.
     */
    public function test_kategori_baru_tidak_bisa_ditempelkan_ke_tingkat_event_lain()
    {
        $eventner = $this->buatEventner(['plan' => 'paid']);
        $tingkat = $this->tingkat($eventner);

        $lain = $this->buatEventner(['plan' => 'paid']);
        $tingkatLain = $this->tingkat($lain, 'PBB Lain');

        $this->actingAs($eventner->user);

        Livewire::test(Builder::class)
            ->set('activeTab', (string) $tingkatLain->id)
            ->set('newCategoryName', 'Rubrik Selundupan')
            ->call('addCategory');

        // Tersimpan, tapi sebagai rubrik global — bukan menempel ke tingkat
        // milik event lain.
        $this->assertDatabaseHas('assessment_categories', [
            'eventner_id' => $eventner->id,
            'name' => 'Rubrik Selundupan',
            'competition_category_id' => null,
        ]);
        $this->assertDatabaseMissing('assessment_categories', [
            'eventner_id' => $eventner->id,
            'name' => 'Rubrik Selundupan',
            'competition_category_id' => $tingkatLain->id,
        ]);

        // Tingkat miliknya sendiri tetap sah dipakai.
        Livewire::test(Builder::class)
            ->set('activeTab', (string) $tingkat->id)
            ->set('newCategoryName', 'Rubrik Sendiri')
            ->call('addCategory');

        $this->assertDatabaseHas('assessment_categories', [
            'eventner_id' => $eventner->id,
            'name' => 'Rubrik Sendiri',
            'competition_category_id' => $tingkat->id,
        ]);
    }
}

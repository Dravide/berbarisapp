<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationPasukanTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-PASUKAN',
            'vote_active' => true,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addDay(),
        ]);
        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->category = CompetitionCategory::factory()->child($parent)->for($this->eventner, 'eventner')->create([
            'max_registrations_per_school' => 2,
        ]);
    }

    public function test_display_name_tanpa_label_pasukan_return_nama_sekolah()
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
        ]);

        $this->assertSame($reg->nama_sekolah, $reg->display_name);
    }

    public function test_display_name_dengan_label_pasukan_sertakan_suffix()
    {
        $reg = Registration::factory()->pasukan('B')->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
        ]);

        $this->assertSame($reg->nama_sekolah . ' — Pasukan B', $reg->display_name);
    }

    public function test_display_name_masuk_toarray_via_appends()
    {
        $reg = Registration::factory()->pasukan('A')->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
        ]);

        $array = $reg->toArray();
        $this->assertArrayHasKey('display_name', $array);
        $this->assertSame($reg->nama_sekolah . ' — Pasukan A', $array['display_name']);
    }

    public function test_halaman_peserta_publik_menampilkan_suffix_pasukan()
    {
        Registration::factory()->pasukan('A')->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'status_berkas' => 'confirmed',
        ]);

        $response = $this->get("/event/{$this->eventner->slug}/participant");

        $response->assertStatus(200)->assertSee('Pasukan A');
    }

    public function test_scoreboard_menampilkan_suffix_pasukan()
    {
        Registration::factory()->pasukan('B')->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'status_berkas' => 'confirmed',
        ]);

        $response = $this->get('/scoreboard/SC-PASUKAN');

        $response->assertStatus(200)->assertSee('Pasukan B');
    }
}

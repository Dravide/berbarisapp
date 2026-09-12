<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParticipantDaftarUlangTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private CompetitionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'U13',
        ]);
    }

    private function makeRegistration(string $school, ?CompetitionCategory $category = null, array $attrs = []): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create(array_merge([
            'competition_category_id' => ($category ?? $this->category)->id,
            'nama_sekolah' => $school,
        ], $attrs));
    }

    public function test_halaman_peserta_menyediakan_unduh_daftar_ulang()
    {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\Participant\Index::class)
            ->assertSee('Daftar Ulang');
    }

    public function test_pdf_daftar_ulang_per_kategori_berhasil_diunduh()
    {
        $this->makeRegistration('SD Negeri 1');
        $this->makeRegistration('SD Negeri 2');

        $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang', ['category_id' => $this->category->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** Tanpa parameter: satu PDF berisi semua kategori. */
    public function test_pdf_daftar_ulang_semua_kategori()
    {
        $parent2 = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'RUKIBRA',
        ]);
        $other = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent2->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SD Negeri 1');
        $this->makeRegistration('SMP Negeri 1', $other);

        $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** Kategori milik event lain tidak boleh terbaca lewat ?category_id=. */
    public function test_kategori_event_lain_ditolak()
    {
        $otherUser = User::factory()->eventner()->create(['is_active' => true]);
        $otherEventner = Eventner::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'approved',
        ]);
        $otherParent = CompetitionCategory::factory()->create([
            'eventner_id' => $otherEventner->id,
            'parent_id' => null,
        ]);
        $foreign = CompetitionCategory::factory()->create([
            'eventner_id' => $otherEventner->id,
            'parent_id' => $otherParent->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang', ['category_id' => $foreign->id]))
            ->assertNotFound();
    }

    /** Kategori tanpa peserta tetap menghasilkan PDF (halaman kosong), bukan error. */
    public function test_kategori_tanpa_peserta_tetap_membuat_pdf()
    {
        $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang', ['category_id' => $this->category->id]))
            ->assertOk();
    }

    /** Tanpa parameter: hanya tingkat (child) yang tampil, bukan induk. */
    public function test_pdf_semua_kategori_hanya_memuat_tingkat_lomba()
    {
        $this->makeRegistration('SD Negeri 1');

        $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * ?category_id= boleh berisi kategori INDUK (parent_id null) — panitia
     * memilih dari daftar kategori, dan yang terpilih sering induk. Dulu ini
     * 404; sekarang induk dipecah jadi semua tingkat di bawahnya.
     */
    public function test_kategori_induk_mengunduh_semua_tingkat_di_bawahnya()
    {
        $sibling = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->category->parent_id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SD Negeri 1');
        $this->makeRegistration('SMP Negeri 1', $sibling);

        $response = $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang', ['category_id' => $this->category->parent_id]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        // Nama unduhan memakai nama induk, bukan tingkat pertama.
        $this->assertStringContainsString('LOBB', $response->headers->get('content-disposition'));
    }

    /** Induk tanpa tingkat anak tidak menghasilkan PDF kosong — tetap 404. */
    public function test_kategori_induk_tanpa_tingkat_anak_ditolak()
    {
        $emptyParent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'KOSONG',
        ]);

        $this->actingAs($this->user)
            ->get(route('eventner.participants.daftar-ulang', ['category_id' => $emptyParent->id]))
            ->assertNotFound();
    }

    public function test_tamu_tidak_bisa_mengunduh()
    {
        $this->get(route('eventner.participants.daftar-ulang'))->assertRedirect();
    }
}

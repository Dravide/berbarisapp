<?php

namespace Tests\Feature;

use App\Livewire\Eventner\CompetitionCategory\Index as CategoryIndex;
use App\Livewire\Eventner\Venue\Index as VenueIndex;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventnerVenueTest extends TestCase
{
    use RefreshDatabase;

    private function makeEventner(): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        return [$user, $eventner];
    }

    /** Dua tempat, dua tingkat — LOBB di SMA 1, RUKIBRA di SMA 2. */
    public function test_tingkat_lomba_menunjuk_tempat_masing_masing()
    {
        [, $eventner] = $this->makeEventner();

        $smaSatu = EventnerVenue::factory()->create(['eventner_id' => $eventner->id, 'name' => 'SMA 1', 'alamat' => 'Jl. Melati 3']);
        $smaDua = EventnerVenue::factory()->create(['eventner_id' => $eventner->id, 'name' => 'SMA 2']);

        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB']);

        $lobb = CompetitionCategory::factory()->child($parent)->create(['name' => 'LOBB', 'venue_id' => $smaSatu->id]);
        $rukibra = CompetitionCategory::factory()->child($parent)->create(['name' => 'RUKIBRA', 'venue_id' => $smaDua->id]);

        $this->assertSame($smaSatu->id, $lobb->fresh()->venue->id);
        $this->assertSame($smaDua->id, $rukibra->fresh()->venue->id);
        $this->assertSame('SMA 1 — Jl. Melati 3', $smaSatu->label);
    }

    /** venueId datang dari klien — tempat milik eventner lain harus ditolak. */
    public function test_simpan_tingkat_dengan_tempat_milik_eventner_lain_ditolak()
    {
        [$user, $eventner] = $this->makeEventner();
        [, $eventnerLain] = $this->makeEventner();

        $asing = EventnerVenue::factory()->create(['eventner_id' => $eventnerLain->id, 'name' => 'SMA Orang Lain']);
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB']);

        Livewire::actingAs($user)
            ->test(CategoryIndex::class)
            ->set('parentId', $parent->id)
            ->set('name', 'LOBB')
            ->set('venueId', $asing->id)
            ->call('save')
            ->assertHasErrors('venueId');

        $this->assertSame(0, CompetitionCategory::where('venue_id', $asing->id)->count());
    }

    /** Tempat yang masih dipakai tingkat lomba tidak boleh hilang diam-diam. */
    public function test_hapus_tempat_yang_masih_dipakai_ditolak()
    {
        [$user, $eventner] = $this->makeEventner();

        $venue = EventnerVenue::factory()->create(['eventner_id' => $eventner->id, 'name' => 'SMA 1']);
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        CompetitionCategory::factory()->child($parent)->create(['name' => 'LOBB', 'venue_id' => $venue->id]);

        Livewire::actingAs($user)->test(VenueIndex::class)->call('delete', $venue->id);

        $this->assertDatabaseHas('eventner_venues', ['id' => $venue->id]);
    }

    /** nullOnDelete: hapus tempat tidak boleh ikut menghapus tingkat lomba. */
    public function test_hapus_tempat_langsung_hanya_mengosongkan_venue_id()
    {
        [, $eventner] = $this->makeEventner();

        $venue = EventnerVenue::factory()->create(['eventner_id' => $eventner->id]);
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $child = CompetitionCategory::factory()->child($parent)->create(['name' => 'LOBB', 'venue_id' => $venue->id]);

        $venue->delete();

        $this->assertDatabaseHas('competition_categories', ['id' => $child->id, 'venue_id' => null]);
    }

    /** Eventner lain tidak boleh mengedit/menghapus tempat bukan miliknya. */
    public function test_eventner_lain_tidak_bisa_menyentuh_tempat_bukan_miliknya()
    {
        [$userA, $eventnerA] = $this->makeEventner();
        [, $eventnerB] = $this->makeEventner();

        $venueB = EventnerVenue::factory()->create(['eventner_id' => $eventnerB->id, 'name' => 'SMA 2']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userA)->test(VenueIndex::class)->call('edit', $venueB->id);
    }

    /** Halaman publik tiket mencetak semua tempat, bukan hanya satu. */
    public function test_halaman_tiket_menampilkan_semua_tempat()
    {
        [, $eventner] = $this->makeEventner();

        EventnerVenue::factory()->create(['eventner_id' => $eventner->id, 'name' => 'SMA 1', 'sort_order' => 1]);
        EventnerVenue::factory()->create(['eventner_id' => $eventner->id, 'name' => 'SMA 2', 'sort_order' => 2]);

        $html = view('livewire.public.partials.pdf.ticket', [
            'eventner' => $eventner,
            'qrPath' => 'data:image/png;base64,',
            'ticket' => new \App\Models\Ticket([
                'order_code' => 'TKT-0001',
                'buyer_name' => 'Budi',
                'quantity' => 1,
                'status' => 'PAID',
            ]),
        ])->render();

        $this->assertStringContainsString('Tempat Pelaksanaan', $html);
        $this->assertStringContainsString('SMA 1', $html);
        $this->assertStringContainsString('SMA 2', $html);
    }
}

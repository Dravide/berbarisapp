<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventTicketQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_and_decrement_quantity_buttons_work()
    {
        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
            'ticket_max_per_order' => 5,
        ]);

        $component = Livewire::test(\App\Livewire\Public\EventTicket::class, ['slug' => $event->slug]);

        // Default 1 — decrement tidak turun di bawah 1
        $component->call('decrementQuantity')->assertSet('quantity', 1);

        // Increment sampai max
        $component->call('incrementQuantity')->assertSet('quantity', 2)
            ->call('incrementQuantity')->assertSet('quantity', 3)
            ->call('incrementQuantity')->assertSet('quantity', 4)
            ->call('incrementQuantity')->assertSet('quantity', 5)
            // Increment lagi — tidak melebihi max
            ->call('incrementQuantity')->assertSet('quantity', 5)
            ->call('decrementQuantity')->assertSet('quantity', 4);

        // Total computed ikut jumlah
        $component->assertSet('total', 200000);
    }
}

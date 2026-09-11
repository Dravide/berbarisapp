<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\User;
use App\Support\EventnerHelp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventnerHelpTest extends TestCase
{
    use RefreshDatabase;

    private function setupEventner(): User
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        return $user;
    }

    public function test_help_map_keys_are_real_eventner_routes(): void
    {
        $names = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter();

        foreach (array_keys(EventnerHelp::all()) as $key) {
            $this->assertTrue($names->contains($key), "Kunci help '$key' bukan nama route yang ada.");
        }
    }

    public function test_help_entries_have_required_shape(): void
    {
        foreach (EventnerHelp::all() as $key => $help) {
            $this->assertNotEmpty($help['title'], "Entri '$key' tanpa title.");
            $this->assertNotEmpty($help['intro'], "Entri '$key' tanpa intro.");
            $this->assertNotEmpty($help['steps'], "Entri '$key' tanpa langkah.");
        }
    }

    public function test_eventner_page_renders_help_button_and_tutorial(): void
    {
        $user = $this->setupEventner();

        $this->actingAs($user)
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertSee('data-bs-target="#eventnerHelpModal"', false)
            ->assertSee('Cara menggunakan: Dashboard');
    }

    public function test_eventner_page_without_help_entry_returns_null(): void
    {
        $request = \Illuminate\Http\Request::create('/eventner/format-nilai/copy/1');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));
        app()->instance('request', $request);

        $this->assertNull(EventnerHelp::current());
    }

    public function test_admin_pages_never_render_help_button(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.eventner.index'))
            ->assertOk()
            ->assertDontSee('eventnerHelpModal');
    }
}

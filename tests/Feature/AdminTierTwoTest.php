<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLog;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Eventner\Index;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\School;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Tier-2 admin: ekspor CSV, pencarian global, audit log, suspend akun.
 */
class AdminTierTwoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    // ────────────────────────────────────────────────
    // Ekspor CSV
    // ────────────────────────────────────────────────

    public function test_ekspor_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.exports.eventners'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.exports.registrations'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.exports.transactions'))->assertForbidden();
    }

    public function test_ekspor_eventner_menghasilkan_csv()
    {
        $admin = $this->admin();
        Eventner::factory()->create(['nama_event' => 'Lomba Ekspor Satu']);

        $res = $this->actingAs($admin)->get(route('admin.exports.eventners'));

        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('Lomba Ekspor Satu', $res->streamedContent());
    }

    public function test_ekspor_pendaftar_menyertakan_sekolah_dan_kategori()
    {
        $eventner = Eventner::factory()->create();
        $kategori = \App\Models\CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
            'nama_sekolah' => 'SMP Coba Nusantara',
        ]);

        $res = $this->actingAs($this->admin())->get(route('admin.exports.registrations'));

        $res->assertOk();
        $isi = $res->streamedContent();
        $this->assertStringContainsString('SMP Coba Nusantara', $isi);
        $this->assertStringContainsString($kategori->name, $isi);
    }

    public function test_ekspor_transaksi_menggabungkan_voting_dan_tiket()
    {
        $eventner = Eventner::factory()->create();
        $reg = Registration::factory()->create(['eventner_id' => $eventner->id]);

        VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-CSV-1',
            'amount' => 10000,
            'votes_earned' => 10,
            'status' => 'PAID',
        ]);

        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli CSV',
            'buyer_email' => 'csv@example.com',
            'quantity' => 1,
            'price_per_ticket' => 25000,
            'total_amount' => 25000,
            'status' => 'PAID',
        ]);

        $res = $this->actingAs($this->admin())->get(route('admin.exports.transactions'));

        $res->assertOk();
        $isi = $res->streamedContent();
        $this->assertStringContainsString('AGP-CSV-1', $isi);
        $this->assertStringContainsString('Pembeli CSV', $isi);
    }

    // ────────────────────────────────────────────────
    // Pencarian global di dashboard
    // ────────────────────────────────────────────────

    public function test_pencarian_global_menemukan_empat_entitas()
    {
        Eventner::factory()->create(['nama_event' => 'Festival Cari Nusantara']);
        School::create([
            'npsn' => '20980001',
            'nama_sekolah' => 'SMA Cari Mudah',
            'status_sekolah' => 'Negeri',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->set('globalSearch', 'Cari')
            ->assertSet('showSearchResults', true)
            ->assertSee('Festival Cari Nusantara')
            ->assertSee('SMA Cari Mudah');
    }

    public function test_pencarian_global_satu_karakter_diabaikan()
    {
        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->set('globalSearch', 'a')
            ->assertSet('showSearchResults', false);
    }

    public function test_pencarian_global_user_dan_pendaftar()
    {
        $eventner = Eventner::factory()->create(['nama_event' => 'Event Sumber']);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'nama_sekolah' => 'SD Pencari Cerdas',
        ]);

        // User cari lewat username factory default, pendaftar lewat nama sekolah.
        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->set('globalSearch', 'Pencari')
            ->assertSee('SD Pencari Cerdas');
    }

    // ────────────────────────────────────────────────
    // Audit log
    // ────────────────────────────────────────────────

    public function test_audit_log_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.audit-log'))->assertForbidden();
    }

    public function test_audit_log_menampilkan_aktivitas_tercatat()
    {
        $admin = $this->admin();
        $eventner = Eventner::factory()->create(['nama_event' => 'Event Terpantau']);

        activity()
            ->performedOn($eventner)
            ->causedBy($admin)
            ->withProperties(['status' => 'approved'])
            ->log('Menyetujui event dari panel uji');

        $this->actingAs($admin)->get(route('admin.audit-log'))
            ->assertSee('Menyetujui event dari panel uji')
            ->assertSee($admin->name);
    }

    public function test_audit_log_filter_log_name()
    {
        $admin = $this->admin();

        activity()->inLog('sekolah')->log('Impor sekolah uji');
        activity()->inLog('paket')->log('Perubahan paket uji');

        $this->actingAs($admin)->get(route('admin.audit-log'))
            ->assertSee('Impor sekolah uji')
            ->assertSee('Perubahan paket uji');

        Livewire::actingAs($admin)
            ->test(AuditLog::class)
            ->set('filterLog', 'paket')
            ->assertDontSee('Impor sekolah uji')
            ->assertSee('Perubahan paket uji');
    }

    // ────────────────────────────────────────────────
    // Suspend / aktifkan eventner
    // ────────────────────────────────────────────────

    public function test_suspend_menonaktifkan_akun_penyelenggara()
    {
        $eventner = Eventner::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('toggleActive', $eventner->id);

        $this->assertFalse((bool) $eventner->user->fresh()->is_active);
    }

    public function test_aktifkan_kembali_akun_yang_disuspend()
    {
        $eventner = Eventner::factory()->create();
        $eventner->user->update(['is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('toggleActive', $eventner->id);

        $this->assertTrue((bool) $eventner->user->fresh()->is_active);
    }

    public function test_akun_suspend_ditolak_dari_area_eventner()
    {
        $eventner = Eventner::factory()->create();
        $eventner->user->update(['is_active' => false]);

        $this->actingAs($eventner->user)
            ->get(route('eventner.dashboard', ['subdomain' => $eventner->slug]))
            ->assertRedirect();
    }
}

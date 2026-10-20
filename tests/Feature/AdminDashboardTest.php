<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\AssessmentCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dashboard admin yang dipertingkat: antrean approval, paket & trial,
 * kesehatan pembayaran, tren 6 bulan, event mendekati hari-H.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    // ────────────────────────────────────────────────
    // Akses
    // ────────────────────────────────────────────────

    public function test_dashboard_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_statistik_global_tetap_tampil()
    {
        $eventner = Eventner::factory()->create();
        $kategori = \App\Models\CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        Registration::factory()->count(3)->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('totalEventners', 1)
            ->assertSet('totalRegistrations', 3);
    }

    // ────────────────────────────────────────────────
    // Antrean persetujuan
    // ────────────────────────────────────────────────

    public function test_antrean_persetujuan_menampilkan_event_pending()
    {
        $eventner = Eventner::factory()->pending()->create(['nama_event' => 'Lomba PBB Kota A']);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSee('Antrean Persetujuan')
            ->assertSee('Lomba PBB Kota A')
            ->assertSet('pendingCount', 1);
    }

    public function test_approve_dari_dashboard_mengaktifkan_event()
    {
        $admin = $this->admin();
        $eventner = Eventner::factory()->pending()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('approve', $eventner->id);

        $this->assertSame('approved', $eventner->fresh()->status);
        $this->assertSame($admin->id, $eventner->fresh()->approved_by);
        $this->assertTrue((bool) $eventner->user->fresh()->is_active);
    }

    public function test_tolak_dari_dashboard_menyimpan_alasan()
    {
        $eventner = Eventner::factory()->pending()->create();

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->call('openRejectModal', $eventner->id)
            ->set('rejectionReason', 'Data tidak lengkap')
            ->call('reject');

        $this->assertSame('rejected', $eventner->fresh()->status);
        $this->assertSame('Data tidak lengkap', $eventner->fresh()->rejection_reason);
    }

    public function test_event_sudah_diproses_tidak_diproses_ulang()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->call('approve', $eventner->id);

        $this->assertSame('approved', $eventner->fresh()->status);
    }

    // ────────────────────────────────────────────────
    // Paket & masa uji
    // ────────────────────────────────────────────────

    public function test_trial_berakhir_dan_menipis_dihitung()
    {
        Eventner::factory()->expiredTrial()->create(['nama_event' => 'Event Habis']);
        Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(3),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSee('Event Habis')
            ->assertSet('trialExpiredCount', 1)
            ->assertSet('trialSoonCount', 1);
    }

    // ────────────────────────────────────────────────
    // Kesehatan pembayaran
    // ────────────────────────────────────────────────

    public function test_transaksi_menggantung_lama_terhitung()
    {
        $eventner = Eventner::factory()->create();
        $reg = Registration::factory()->create(['eventner_id' => $eventner->id]);

        $vote = VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-TEST-1',
            'amount' => 10000,
            'votes_earned' => 10,
            'status' => 'PENDING',
        ]);
        $vote->forceFill(['created_at' => now()->subHours(3)])->save();

        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli B',
            'buyer_email' => 'b@example.com',
            'quantity' => 1,
            'price_per_ticket' => 25000,
            'total_amount' => 25000,
            'status' => 'PENDING',
        ]);
        $ticket->forceFill(['created_at' => now()->subHour()])->save(); // belum 2 jam — tidak dihitung

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('stuckVoteCount', 1)
            ->assertSet('stuckTicketCount', 0);
    }

    public function test_transaksi_segar_diabaikan_dari_kesehatan()
    {
        $eventner = Eventner::factory()->create();
        $reg = Registration::factory()->create(['eventner_id' => $eventner->id]);

        VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-TEST-2',
            'amount' => 10000,
            'votes_earned' => 10,
            'status' => 'PENDING',
            'created_at' => now()->subMinutes(10),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('stuckVoteCount', 0);
    }

    // ────────────────────────────────────────────────
    // Tren 6 bulan
    // ────────────────────────────────────────────────

    public function test_tren_menghitung_pendaftar_bulan_ini()
    {
        Eventner::factory()->create();
        Registration::factory()->count(2)->create();

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class);

        $tren = collect($component->get('registrationsTrend'));
        $this->assertSame(2, (int) $tren->last()['total']);
        $this->assertCount(6, $tren);
    }

    public function test_tren_revenue_menyertakan_aktivasi_paket()
    {
        // `registration_source => 'self'` wajib: hanya pendaftar mandiri yang
        // melewati QRIS. Pembelian selalu berpasangan dengan `plan='paid'`,
        // dan itu memang bentuk satu-satunya yang menghasilkan uang.
        //
        // Dulu tes ini memakai default factory (`registration_source =>
        // 'admin'`), yaitu bentuk akun yang DIBUAT admin lewat assignPlan() —
        // yang menstempel registration_paid_at sebagai penanda "diaktifkan"
        // tanpa QRIS. Tren pendapatan menghitungnya sebagai Rp 150.000 yang
        // tak pernah masuk kas; itu yang sekarang disaring, dan kasus
        // sebaliknya dijaga TrialConversionTest.
        Eventner::factory()->paid()->create([
            'registration_source' => 'self',
            'registration_paid_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class);

        $tren = collect($component->get('revenueTrend'));
        $this->assertSame(150000, $tren->last()['total']);
    }

    /**
     * Sisi lain dari tes di atas: paket yang diaktifkan admin tanpa bayar
     * tidak boleh menaikkan tren pendapatan, meski `registration_paid_at`
     * terisi. Kalau saringan `registration_source` dilepas, tes ini gagal.
     */
    public function test_tren_revenue_mengabaikan_aktivasi_oleh_admin()
    {
        $eventner = Eventner::factory()->create(['plan' => 'free']);
        $eventner->assignPlan(
            \App\Models\SaasPlan::create([
                'name' => 'Gratis Uji Dashboard',
                'slug' => 'gratis-uji-dash-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)),
                'price' => 0,
                'registration_fee' => 0,
                'is_active' => true,
                'is_free' => true,
                'is_contact' => false,
                'sort_order' => 1,
            ]),
            'admin'
        );

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class);

        $tren = collect($component->get('revenueTrend'));
        $this->assertSame(0, $tren->last()['total']);
    }

    // ────────────────────────────────────────────────
    // Event mendekati hari-H
    // ────────────────────────────────────────────────

    public function test_event_mendekati_hari_h_tampil_dengan_tanda_persiapan()
    {
        $eventner = Eventner::factory()->create([
            'nama_event' => ' Lomba 3 Hari Lagi',
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);
        AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Aspek Utama',
        ]);
        Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Bpk. Juri',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSee('Lomba 3 Hari Lagi')
            ->assertSee('Rubrik 1')
            ->assertSee('Juri 1');
    }

    public function test_event_belum_siap_ditandai()
    {
        Eventner::factory()->create([
            'nama_event' => 'Lomba Besok Tanpa Persiapan',
            'tanggal' => now()->addDay()->toDateString(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSee('Lomba Besok Tanpa Persiapan')
            ->assertSee('Rubrik kosong')
            ->assertSee('Juri kosong')
            ->assertSee('Belum siap');
    }

    public function test_event_di_luar_7_hari_tidak_tampil()
    {
        Eventner::factory()->create([
            'nama_event' => 'Lomba Jauh Di Hari',
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertDontSee('Lomba Jauh Di Hari');
    }
}

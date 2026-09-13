<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\SaasPlan;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EventnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function setupEventnerUser(): array
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
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        return [$user, $eventner, $category];
    }

    private function createVoteTransaction($eventner, $registration, array $attrs = []): VoteTransaction
    {
        return VoteTransaction::create(array_merge([
            'eventner_id' => $eventner->id,
            'registration_id' => $registration->id,
            'autogopay_transaction_id' => 'AGP-' . uniqid(),
            'qr_url' => 'https://example.com/qr.png',
            'amount' => 10000,
            'votes_earned' => 10,
            'voter_name' => 'Voter Satu',
            'voter_email' => 'voter@example.com',
            'comment' => 'Semangat!',
            'status' => 'PAID',
            'paid_at' => now(),
        ], $attrs));
    }

    public function test_dashboard_shows_vote_and_ticket_stats()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
        ]);

        $this->createVoteTransaction($eventner, $reg, ['votes_earned' => 10]);
        $this->createVoteTransaction($eventner, $reg, ['votes_earned' => 5, 'status' => 'PENDING', 'paid_at' => null]);

        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli A',
            'buyer_email' => 'a@example.com',
            'quantity' => 3,
            'price_per_ticket' => 25000,
            'total_amount' => 75000,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);
        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli B',
            'buyer_email' => 'b@example.com',
            'quantity' => 2,
            'price_per_ticket' => 25000,
            'total_amount' => 50000,
            'status' => 'CHECKED_IN',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('totalVotes', 10)
            ->assertSet('votePaidCount', 1)
            ->assertSet('votePendingCount', 1)
            ->assertSet('ticketsSold', 5)
            ->assertSet('ticketsCheckedIn', 1);
    }

    public function test_dashboard_shows_verification_counts()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'paid',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'pending_verification',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'unpaid',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'status_berkas' => 'Menunggu',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('pendingVerificationCount', 1)
            ->assertSet('berkasMenungguCount', 1);
    }

    public function test_hasil_pengundian_hanya_tingkat_lomba_bukan_jenisnya()
    {
        [$user, $eventner, $parent] = $this->setupEventnerUser();

        // setupEventnerUser membuat parent (jenis) + satu child (tingkat).
        // Tambah satu tingkat lagi agar beda parent vs child jelas.
        $childB = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Tingkat B',
        ]);

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $childB->id,
            'urutan_tampil' => 1,
        ]);

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $rows = $component->get('drawingData');

        // Parent tidak boleh muncul sebagai baris tersendiri.
        $this->assertCount(2, $rows, 'Hanya dua tingkat lomba yang jadi baris.');
        $this->assertSame(['Tingkat B', $parent->name], $rows->pluck('name')->sort()->values()->all());

        // Nama baris = nama child saja, bukan "Parent — Child".
        foreach ($rows as $row) {
            $this->assertStringNotContainsString('—', $row['name']);
        }

        $tingkatB = $rows->firstWhere('name', 'Tingkat B');
        $this->assertSame(1, $tingkatB['total']);
        $this->assertSame(1, $tingkatB['drawn']);
    }

    public function test_dashboard_revenue_labels_fixed()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Total Pendapatan')
            ->assertDontSee('Estimasi Voting')
            ->assertDontSee('paymentStatusChart')
            ->assertDontSee('Status Pembayaran Pendaftar');
    }

    public function test_dashboard_vote_schedule_belum()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();
        $eventner->update([
            'vote_active' => true,
            'vote_start' => now()->addDays(3),
            'vote_end' => now()->addDays(10),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('voteStatus', 'belum');
    }

    public function test_dashboard_vote_schedule_selesai()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();
        $eventner->update([
            'vote_active' => true,
            'vote_start' => now()->subDays(10),
            'vote_end' => now()->subDay(),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('voteStatus', 'selesai');
    }

    public function test_dashboard_vote_schedule_nonaktif()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();
        $eventner->update(['vote_active' => false]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('voteStatus', 'nonaktif');
    }

    public function test_dashboard_readiness_shows_progress()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        // Event kosong: 1 kategori tanpa kuota, tanpa juri, tanpa rundown — readiness < 100
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('readinessPercent', fn ($percent) => $percent < 100)
            ->assertSee('Kesiapan Event Anda')
            ->assertSee('Juri belum ada')
            ->assertSee('Rundown kosong');

        // Isi semua readiness → 100%
        // Refresh user — Auth::user()->eventner relasi ter-cache dari mount pertama
        $user = $user->fresh();
        $eventner->update([
            'logo_event' => 'logos/test.png',
            'poster' => 'posters/test.png',
            'vote_active' => true,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addDays(5),
        ]);
        $category->update(['kuota' => 10]);
        \App\Models\Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juri Satu',
            'phone_number' => '08123456789',
        ]);
        \App\Models\AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Penampilan',
        ]);
        \App\Models\EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Opening',
            'start_time' => '08:00',
        ]);
        // Undian: 1 registrasi dengan urutan_tampil
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'urutan_tampil' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('readinessPercent', 100);
    }

    public function test_dashboard_alerts_generated()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'pending_verification',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'pending_verification',
        ]);

        // Kuota penuh: kuota 1 + 1 pendaftar = 100% ≥ 80%
        $category->update(['kuota' => 1]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Perlu Perhatian')
            ->assertSee('2 pembayaran menunggu verifikasi')
            ->assertSee('% penuh');
    }

    public function test_dashboard_kpi_modules_render()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Aksi Cepat')
            ->assertSee('Kategori Lomba')
            ->assertSee('Undian')
            ->assertSee('Skoring')
            ->assertSee('Check-in Tiket')
            ->assertDontSee('Pintasan Panitia')
            ->assertDontSee('Informasi Event Anda');
    }

    // ────────────────────────────────────────────────
    // Penjelasan paket di header
    // ────────────────────────────────────────────────

    private function makePlan(string $name, array $features, array $attrs = []): SaasPlan
    {
        $plan = SaasPlan::create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'price' => 150000,
            'registration_fee' => 50000,
            'is_active' => true,
            'is_free' => false,
            'is_contact' => false,
            'sort_order' => 1,
        ], $attrs));

        $plan->features()->createMany(
            collect($features)->map(fn ($key) => ['feature_key' => $key])->all()
        );

        return $plan;
    }

    /**
     * Header harus menyebut paket yang terpasang dan fitur yang BENAR-BENAR
     * bisa dipakai. Daftar fitur diambil dari canAccessFeature(), jadi fitur
     * di luar paket tidak boleh ikut terdaftar sebagai tersedia.
     */
    public function test_header_menampilkan_paket_terpasang_dan_fiturnya()
    {
        [$user, $eventner] = $this->setupEventnerUser();

        $plan = $this->makePlan('Paket Sosial', ['vote_settings', 'vote_results', 'tickets']);
        $eventner->assignPlan($plan, 'admin');

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $this->assertSame(
            ['Tiket Event', 'Pengaturan Vote', 'Hasil Voting'],
            $component->get('planInfo')['included'],
            'Hanya fitur paket yang dilaporkan tersedia.'
        );

        $component
            ->assertSee('Paket Sosial')
            ->assertSee('Aktif')
            ->assertSee('Fitur tersedia')
            ->assertSee('Tiket Event')
            ->assertSee('Hasil Voting')
            // Fitur di luar paket masuk daftar "Belum termasuk", bukan "tersedia".
            ->assertSee('Belum termasuk')
            ->assertSee('Sertifikat');
    }

    /**
     * Paket gratis tidak menyimpan feature_key sama sekali. Header tidak boleh
     * menyimpulkan "semua terkunci" dari daftar kosong itu — fitur di luar
     * config (peserta, juri, penilaian, scoreboard) tetap terbuka.
     */
    public function test_header_paket_gratis_tidak_menampilkan_fitur_premium()
    {
        [$user, $eventner] = $this->setupEventnerUser();

        $plan = SaasPlan::create([
            'name' => 'Gratis',
            'slug' => 'gratis-' . Str::lower(Str::random(6)),
            'price' => 0,
            'registration_fee' => 0,
            'is_active' => true,
            'is_free' => true,
            'is_contact' => false,
            'sort_order' => 0,
        ]);
        // Meski fitur tidak sengaja tersimpan, paket gratis tetap harus kosong.
        $plan->features()->createMany([['feature_key' => 'tickets']]);

        $eventner->assignPlan($plan, 'admin');

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $this->assertSame([], $component->get('planInfo')['included']);
        $this->assertSame('Gratis (gratis)', $component->get('planInfo')['name']);
        $this->assertSame('Aktif', $component->get('planInfo')['status']);

        $component->assertSee('Belum ada fitur premium');
    }

    /** Eventner legacy (plan=paid tanpa paket) tetap akses penuh. */
    public function test_header_eventner_legacy_menampilkan_akses_penuh()
    {
        [$user, $eventner] = $this->setupEventnerUser();
        $eventner->update(['plan' => 'paid', 'saas_plan_id' => null, 'trial_ends_at' => null]);

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $this->assertSame('Akses Penuh', $component->get('planInfo')['name']);
        $this->assertSame('Aktif', $component->get('planInfo')['status']);
        $this->assertSame([], $component->get('lockedFeatures'));

        $component->assertSee('Akses Penuh')->assertDontSee('Belum termasuk');
    }

    /**
     * Trial berjalan: semua fitur premium terbuka, jadi "Fitur tersedia"
     * memuat seluruh isi config dan tidak ada yang terkunci.
     */
    public function test_header_menampilkan_trial_berjalan()
    {
        [$user, $eventner] = $this->setupEventnerUser();
        $eventner->update(['plan' => 'free', 'trial_ends_at' => now()->addDays(5)]);

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $this->assertSame('Gratis (masa uji coba)', $component->get('planInfo')['name']);
        $this->assertSame('Trial 5 hari lagi', $component->get('planInfo')['status']);
        $this->assertCount(14, $component->get('planInfo')['included']);
        $this->assertSame([], $component->get('lockedFeatures'));

        $component->assertSee('Fitur tersedia')->assertDontSee('Belum termasuk');
    }

    /** Trial habis: fitur premium terkunci dan disebut di header. */
    public function test_header_menampilkan_trial_habis_dengan_fitur_terkunci()
    {
        [$user, $eventner] = $this->setupEventnerUser();
        $eventner->update(['plan' => 'free', 'trial_ends_at' => now()->subDay()]);

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $this->assertSame('Gratis', $component->get('planInfo')['name']);
        $this->assertSame('Trial Berakhir', $component->get('planInfo')['status']);
        $this->assertSame([], $component->get('planInfo')['included']);
        $this->assertCount(14, $component->get('lockedFeatures'));

        $component
            ->assertSee('Trial Berakhir')
            ->assertSee('Belum ada fitur premium')
            ->assertSee('Belum termasuk')
            // Panel Fitur Terkunci tetap ada untuk kasus trial habis tanpa paket.
            ->assertSee('Fitur Terkunci');
    }

    /**
     * Eventner berbayar yang dinonaktifkan paketnya (is_active=0) tidak lagi
     * ditawarkan admin, tapi eventner yang sudah memakainya tidak boleh
     * kehilangan aksesnya — dan header harus tetap melaporkannya "Aktif".
     */
    public function test_header_paket_nonaktif_yang_terpasang_tetap_aktif()
    {
        [$user, $eventner] = $this->setupEventnerUser();

        $plan = $this->makePlan('Paket Lama', ['certificate']);
        $eventner->assignPlan($plan, 'admin');
        $plan->update(['is_active' => false]);

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Dashboard::class);

        $this->assertSame('Paket Lama', $component->get('planInfo')['name']);
        $this->assertSame('Aktif', $component->get('planInfo')['status']);
        $this->assertSame(['Sertifikat'], $component->get('planInfo')['included']);
    }
}

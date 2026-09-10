<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Settings\Billing\Upgrade;
use App\Models\Eventner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase;

    private function webhookPayload(string $transactionId, string $status = 'settlement'): array
    {
        return [
            'event' => 'transaction.received',
            // `transaction_id` — bentuk asli dari AutoGoPay (lihat dokumentasi).
            // Sebelumnya di sini tertulis `id`, field yang tidak pernah dikirim
            // gateway, sehingga webhook selalu 400 di produksi sementara tes hijau.
            'transaction' => [
                'transaction_id' => $transactionId,
                'status' => $status,
            ],
        ];
    }

    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('services.autogopay.api_key'));

        return $this->postJson('/webhook/autogopay', $payload, ['X-Signature' => $signature]);
    }

    // ────────────────────────────────────────────────
    // Webhook: settle → plan paid
    // ────────────────────────────────────────────────

    public function test_webhook_settlement_upgrades_approved_eventner_to_paid()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'saas_plan_id' => \App\Models\SaasPlan::where('is_free', false)->value('id'),
            'status' => 'approved',
            'approved_at' => now()->subDays(5),
            'trial_ends_at' => now()->subDays(2),
            'autogopay_transaction_id' => 'AGP-UPG-001',
        ]);

        $response = $this->postWebhook($this->webhookPayload('AGP-UPG-001'));

        $response->assertOk();
        $this->assertEquals('paid', $eventner->fresh()->plan);
        $this->assertEquals('approved', $eventner->fresh()->status);
        $this->assertNotNull($eventner->fresh()->registration_paid_at);
        // approved_at lama tidak tertimpa
        $this->assertTrue($eventner->fresh()->approved_at->isSameDay(now()->subDays(5)));
    }

    public function test_webhook_settlement_approves_pending_paid_registration()
    {
        $user = User::factory()->create(['role' => 'Eventner', 'is_active' => false]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'plan' => 'paid',
            'status' => 'pending',
            'autogopay_transaction_id' => 'AGP-NEW-001',
        ]);

        $response = $this->postWebhook($this->webhookPayload('AGP-NEW-001'));

        $response->assertOk();
        $this->assertEquals('paid', $eventner->fresh()->plan);
        $this->assertEquals('approved', $eventner->fresh()->status);
        $this->assertTrue((bool) $user->fresh()->is_active);
    }

    public function test_webhook_settlement_is_idempotent()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'status' => 'approved',
            'autogopay_transaction_id' => 'AGP-IDEM-001',
        ]);
        $firstPaidAt = now();

        // Webhook pertama (ret) — webhook kedua tidak mengubah apapun
        $this->postWebhook($this->webhookPayload('AGP-IDEM-001'))->assertOk();
        $this->postWebhook($this->webhookPayload('AGP-IDEM-001'))->assertOk();

        $this->assertEquals('paid', $eventner->fresh()->plan);
    }

    public function test_webhook_expire_keeps_plan_free()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'status' => 'approved',
            'autogopay_transaction_id' => 'AGP-EXP-001',
        ]);

        $response = $this->postWebhook($this->webhookPayload('AGP-EXP-001', 'expire'));

        $response->assertOk();
        $this->assertEquals('free', $eventner->fresh()->plan);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $eventner = Eventner::factory()->create([
            'autogopay_transaction_id' => 'AGP-BAD-001',
        ]);

        $response = $this->postJson('/webhook/autogopay', $this->webhookPayload('AGP-BAD-001'), [
            'X-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('free', $eventner->fresh()->plan);
    }

    public function test_webhook_ignores_unknown_transaction()
    {
        $response = $this->postWebhook($this->webhookPayload('AGP-UNKNOWN-999'));
        $response->assertOk();
    }

    // ────────────────────────────────────────────────
    // Portal upgrade
    // ────────────────────────────────────────────────

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-GEN-001',
                    'order_id' => 'ORD-GEN-001',
                    'amount' => 150000,
                    'transaction_status' => 'pending',
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/upgrade.png',
                    'transaction_time' => now()->toIso8601String(),
                    'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                ],
            ], 200),
        ]);
    }

    public function test_upgrade_page_generates_qris_for_free_eventner()
    {
        $this->fakeAutoGoPay();

        $user = User::factory()->eventner()->create();
        Eventner::factory()->create(['user_id' => $user->id, 'plan' => 'free']);

        Livewire::actingAs($user)->test(Upgrade::class)
            ->call('generatePayment')
            ->assertSet('showPayment', true)
            ->assertSet('paymentTransactionId', 'AGP-GEN-001');

        $this->assertEquals('AGP-GEN-001', $user->eventner->fresh()->autogopay_transaction_id);
    }

    public function test_upgrade_page_redirects_paid_eventner_to_dashboard()
    {
        $user = User::factory()->eventner()->create();
        Eventner::factory()->create(['user_id' => $user->id, 'plan' => 'paid']);

        $this->actingAs($user)->get(route('eventner.billing.upgrade'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_feature_gate_redirects_to_upgrade_instead_of_403()
    {
        $user = User::factory()->eventner()->create();
        Eventner::factory()->create([
            'user_id' => $user->id,
            'plan' => 'free',
            'trial_ends_at' => now()->subDay(), // trial expired
        ]);

        // drawing adalah fitur locked_free
        $this->actingAs($user)->get(route('eventner.drawing.index'))
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    // ────────────────────────────────────────────────
    // Halaman pricing publik
    // ────────────────────────────────────────────────

    public function test_pricing_page_renders_publicly()
    {
        $this->get(route('pricing'))->assertOk();
    }

    // ────────────────────────────────────────────────
    // Harga & paket: setting admin + landing
    // ────────────────────────────────────────────────

    public function test_pricing_reflects_saas_plans()
    {
        // Admin punya paket berbayar dengan fitur tertentu (tanpa 'certificate')
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();
        $plan->update(['price' => 200000]);
        $plan->features()->delete();
        $plan->features()->createMany([
            ['feature_key' => 'tickets'],
            ['feature_key' => 'drawing'],
        ]);

        $response = $this->get(route('pricing'));
        $response->assertOk();
        $response->assertSee('200.000');
        $response->assertSee('Tiket Event');
        $response->assertSee('Drawing / Undian');
        $response->assertDontSee('Sertifikat'); // tidak termasuk paket
    }

    public function test_landing_pricing_section_renders()
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Event Penuh')
            ->assertSee('Daftar Gratis');
    }

    public function test_admin_pricing_settings_page_saves_plan()
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PricingSettings::class)
            ->call('createPlan')
            ->set('name', 'Paket Standar')
            ->set('price', 250000)
            ->set('registration_fee', 75000)
            ->set('sort_order', 3)
            ->set('plan_features.tickets', true)
            ->set('plan_features.certificate', false)
            ->call('savePlan')
            ->assertHasNoErrors();

        $plan = \App\Models\SaasPlan::where('name', 'Paket Standar')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(250000, $plan->price);
        $this->assertEquals(75000, $plan->registration_fee);
        $this->assertTrue($plan->features->pluck('feature_key')->contains('tickets'));
        $this->assertFalse($plan->features->pluck('feature_key')->contains('certificate'));
    }

    public function test_admin_pricing_settings_toggles_and_deletes_plan()
    {
        $admin = User::factory()->admin()->create();
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PricingSettings::class)
            ->call('toggleActive', $plan->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $plan->fresh()->is_active);

        // Paket masih dipakai event → tidak bisa dihapus
        $eventner = Eventner::factory()->paid()->create();
        $eventner->update(['saas_plan_id' => $plan->id]);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PricingSettings::class)
            ->call('deletePlan', $plan->id)
            ->assertHasNoErrors();

        $this->assertNotNull(\App\Models\SaasPlan::find($plan->id)); // masih ada
    }

    public function test_feature_gate_respects_plan_features()
    {
        // Paket berbayar tanpa fitur certificate
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();
        $plan->features()->delete();
        $plan->features()->create(['feature_key' => 'tickets']);

        $user = User::factory()->eventner()->create();
        $eventner = Eventner::factory()->paid()->create([
            'user_id' => $user->id,
            'saas_plan_id' => $plan->id,
        ]);

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertFalse($eventner->canAccessFeature('certificate')); // tidak di paket
        $this->assertArrayHasKey('certificate', $eventner->lockedFeatures());
    }

    public function test_admin_pricing_settings_route_requires_admin()
    {
        $user = User::factory()->eventner()->create();
        $this->actingAs($user)->get(route('admin.pricing-settings'))->assertForbidden();
    }

    // ────────────────────────────────────────────────
    // Paket khusus (hubungi admin)
    // ────────────────────────────────────────────────

    private function createContactPlan(): \App\Models\SaasPlan
    {
        $plan = \App\Models\SaasPlan::create([
            'name' => 'Paket Khusus',
            'slug' => 'paket-khusus',
            'price' => 0,
            'registration_fee' => 0,
            'description' => 'Sesuai kebutuhan event besar',
            'is_active' => true,
            'is_free' => false,
            'is_contact' => true,
            'contact_url' => 'https://wa.me/6281234567890',
            'sort_order' => 3,
        ]);
        $plan->features()->create(['feature_key' => 'tickets']);

        return $plan;
    }

    public function test_contact_plan_renders_hubungi_admin_button()
    {
        $this->createContactPlan();

        $response = $this->get(route('pricing'));
        $response->assertOk()
            ->assertSee('Paket Khusus')
            ->assertSee('Kustom')
            ->assertSee('Hubungi Admin')
            ->assertSee('https://wa.me/6281234567890');
    }

    public function test_contact_plan_excluded_from_register_and_upgrade()
    {
        $plan = $this->createContactPlan();

        // Halaman register tidak menampilkan paket contact
        $register = $this->get(route('register.eventner'));
        $register->assertOk()->assertDontSee('Paket Khusus');

        // Slug paket contact ditolak saat validasi register
        Livewire::test(\App\Livewire\Public\EventnerRegister::class)
            ->set('plan', 'paket-khusus')
            ->set('name', 'Panitia')
            ->set('username', 'panitia_contact')
            ->set('email', 'contact@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('nama_event', 'Event Contact')
            ->set('lokasi', 'Bandung')
            ->set('agreeTerms', true)
            ->call('save')
            ->assertHasErrors(['plan']);
    }

    public function test_contact_plan_not_used_as_default_paid_price()
    {
        $this->createContactPlan();

        // Harga berbayar default tetap dari paket QRIS (Event Penuh)
        $this->assertEquals(150000, \App\Support\Pricing::planPrice());

        // Upgrade hanya menawarkan paket QRIS
        $user = User::factory()->eventner()->create();
        Eventner::factory()->create(['user_id' => $user->id, 'plan' => 'free']);

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Settings\Billing\Upgrade::class)
            ->assertDontSee('Paket Khusus');
    }

    /**
     * Jalur polling dulu tidak mengisi qr_code_path (komentar lama: hanya webhook
     * yang mengisi karena punya amount untuk verifikasi). Akibatnya tiket yang
     * terkonfirmasi lewat polling jadi PAID tanpa QR masuk sama sekali.
     */
    public function test_polling_sync_membuat_qr_masuk_tiket(): void
    {
        Storage::fake('public');


        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-QR-001', 'transaction_status' => 'settlement'],
            ], 200),
        ]);

        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-QR-001',
            'status' => 'PENDING',
        ]);

        (new \App\Jobs\SyncPendingPayments)->handle();

        $ticket->refresh();
        $this->assertSame('PAID', $ticket->status);
        $this->assertNotNull($ticket->qr_code_path, 'tiket PAID wajib punya QR masuk');
        Storage::disk('public')->assertExists($ticket->qr_code_path);
    }

    /**
     * Halaman tiket eventner punya sync + konfirmasi manual sendiri, dan
     * keduanya dulu hanya menulis status — tiket jadi PAID tanpa QR, sehingga
     * "QR tidak muncul" di halaman pembeli. Ini jalur yang paling sering dipakai
     * eventner, jadi ikut ditutup.
     */
    public function test_sync_dan_konfirmasi_manual_di_halaman_tiket_membuat_qr(): void
    {
        Storage::fake('public');

        $user = User::factory()->eventner()->create();
        $eventner = Eventner::factory()->paid()->create(['user_id' => $user->id, 'status' => 'approved']);

        $settled = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Sari',
            'buyer_email' => 'sari@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-PAGE-001',
            'status' => 'PENDING',
        ]);
        $manual = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Tono',
            'buyer_email' => 'tono@example.com',
            'quantity' => 2,
            'price_per_ticket' => 50000,
            'total_amount' => 100000,
            'autogopay_transaction_id' => 'AGP-PAGE-002',
            'status' => 'PENDING',
        ]);

        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-PAGE-001', 'transaction_status' => 'settlement'],
            ], 200),
        ]);

        $component = Livewire::actingAs($user)->test(\App\Livewire\Eventner\Ticket\Index::class)
            ->call('syncPending');

        $this->assertSame('PAID', $settled->fresh()->status);
        $this->assertNotNull($settled->fresh()->qr_code_path, 'sync di halaman tiket wajib membuat QR');
        Storage::disk('public')->assertExists($settled->fresh()->qr_code_path);

        $component->call('markAsPaid', $manual->id);

        $this->assertSame('PAID', $manual->fresh()->status);
        $this->assertNotNull($manual->fresh()->qr_code_path, 'konfirmasi manual wajib membuat QR');
        Storage::disk('public')->assertExists($manual->fresh()->qr_code_path);
    }

    /**
     * Command payment:sync-pending jalan tiap menit dan juga menandai tiket PAID.
     */
    public function test_command_sync_pending_membuat_qr_masuk_tiket(): void
    {
        Storage::fake('public');

        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-CMD-001', 'transaction_status' => 'settlement'],
            ], 200),
        ]);

        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Rina',
            'buyer_email' => 'rina@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-CMD-001',
            'status' => 'PENDING',
        ]);

        $this->artisan('payment:sync-pending')->assertSuccessful();

        $ticket->refresh();
        $this->assertSame('PAID', $ticket->status);
        $this->assertNotNull($ticket->qr_code_path, 'command sync wajib membuat QR masuk');
        Storage::disk('public')->assertExists($ticket->qr_code_path);
    }

    /**
     * claimPaid() harus menolak klaim kedua supaya webhook & polling yang
     * berbarengan tidak mengirim email konfirmasi dua kali ke pembeli.
     */
    public function test_claim_paid_hanya_berhasil_sekali(): void
    {
        Storage::fake('public');

        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Dewi',
            'buyer_email' => 'dewi@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-ONCE-001',
            'status' => 'PENDING',
        ]);

        $this->assertTrue($ticket->claimPaid(), 'klaim pertama harus menang');
        $this->assertFalse($ticket->claimPaid(), 'klaim kedua harus ditolak');
    }

    /**
     * Tiket bisa diunduh sebagai PDF berisi QR-nya (cadangan kalau QR di halaman
     * tidak sempat dimuat di ponsel pembeli).
     */
    public function test_tiket_pdf_bisa_diunduh_dan_memuat_qr(): void
    {
        Storage::fake('public');

        $eventner = Eventner::factory()->create(['status' => 'approved', 'slug' => 'event-pdf']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Bayu',
            'buyer_email' => 'bayu@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-PDF-001',
            'status' => 'PENDING',
        ]);
        $ticket->claimPaid();

        $response = $this->get(route('event.ticket.pdf', [
            'slug' => 'event-pdf',
            'orderCode' => $ticket->order_code,
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString(
            'tiket-' . $ticket->order_code,
            $response->headers->get('content-disposition') ?? ''
        );
    }

    /**
     * Tiket lama yang terlanjur PAID tanpa file QR tetap bisa diunduh —
     * QR diterbitkan saat PDF diminta (murni lokal dari order_code).
     */
    public function test_tiket_pdf_menerbitkan_qr_yang_belum_ada(): void
    {
        Storage::fake('public');

        $eventner = Eventner::factory()->create(['status' => 'approved', 'slug' => 'event-pdf-2']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Citra',
            'buyer_email' => 'citra@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-PDF-002',
            'status' => 'PAID',
            'qr_code_path' => null,
        ]);

        $this->get(route('event.ticket.pdf', [
            'slug' => 'event-pdf-2',
            'orderCode' => $ticket->order_code,
        ]))->assertOk();

        $this->assertNotNull($ticket->fresh()->qr_code_path);
        Storage::disk('public')->assertExists($ticket->fresh()->qr_code_path);
    }

    /**
     * Tiket belum bayar / order code ngawur tidak boleh bisa diunduh publik —
     * PDF memuat data pembeli, jadi jangan sampai bocor lintas event.
     */
    public function test_tiket_pdf_menolak_yang_belum_bayar_dan_event_lain(): void
    {
        Storage::fake('public');

        $eventner = Eventner::factory()->create(['status' => 'approved', 'slug' => 'event-a']);
        $lain = Eventner::factory()->create(['status' => 'approved', 'slug' => 'event-b']);

        $belumBayar = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Dedi',
            'buyer_email' => 'dedi@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-PDF-003',
            'status' => 'PENDING',
        ]);
        $punyaEventLain = Ticket::create([
            'eventner_id' => $lain->id,
            'buyer_name' => 'Eka',
            'buyer_email' => 'eka@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-PDF-004',
            'status' => 'PENDING',
        ]);
        $punyaEventLain->claimPaid();

        $this->get(route('event.ticket.pdf', ['slug' => 'event-a', 'orderCode' => $belumBayar->order_code]))
            ->assertNotFound();

        // order_code valid tapi milik event lain — tidak boleh bocor
        $this->get(route('event.ticket.pdf', ['slug' => 'event-a', 'orderCode' => $punyaEventLain->order_code]))
            ->assertNotFound();
    }

    /**
     * Route PDF harus benar saat event pakai subdomain — kalau tidak, tombolnya
     * menunjuk ke path yang salah dan hanya gagal di produksi.
     */
    public function test_url_tiket_pdf_benar_untuk_event_subdomain(): void
    {
        $eventner = Eventner::factory()->create(['subdomain' => 'smk1', 'slug' => 'smk1']);

        $url = $eventner->publicUrl('ticket.pdf', ['orderCode' => 'TKT-ABC123']);

        $this->assertStringEndsWith('/tiket/TKT-ABC123/pdf', $url);
        $this->assertStringContainsString('smk1.', $url);
    }

    /**
     * Email tiket ikut menautkan PDF-nya.
     *
     * Catatan: Maily.id tidak menerima attachment — parameter body-nya hanya
     * from/to/reply_to/subject/html/text. Jadi "kirim PDF ke email" di sini
     * berarti tautan unduh, bukan lampiran berkas.
     */
    public function test_email_tiket_menautkan_pdf(): void
    {
        Storage::fake('public');
        config(['maily.enabled' => true, 'maily.api_key' => 'ml_test_key']);

        Http::fake(['maily.id/*' => Http::response(['id' => 'abc', 'status' => 'queued'], 202)]);

        $eventner = Eventner::factory()->create(['status' => 'approved', 'slug' => 'event-mail']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Fajar',
            'buyer_email' => 'fajar@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-MAIL-001',
            'status' => 'PENDING',
        ]);
        $ticket->claimPaid();

        app(\App\Services\MailyService::class)->sendTicketConfirmation($ticket->fresh());

        $sentHtml = null;
        Http::assertSent(function ($request) use (&$sentHtml) {
            $sentHtml = $request['html'] ?? '';

            return true;
        });

        $this->assertNotNull($sentHtml, 'email tiket harus terkirim');
        $this->assertStringContainsString(
            '/tiket/' . $ticket->order_code . '/pdf',
            $sentHtml,
            'email harus memuat tautan unduh PDF'
        );
    }

    /**
     * Bikin eventner yang sudah "tua" seperti kondisi produksi. created_at bukan
     * kolom fillable, jadi backdate wajib lewat forceFill — kalau tidak, nilainya
     * diabaikan diam-diam dan tes lolos palsu.
     */
    private function agedEventner(array $attributes): Eventner
    {
        $eventner = Eventner::factory()->create($attributes);
        $eventner->forceFill(['created_at' => now()->subHours(25)])->save();

        return $eventner;
    }

    /**
     * Pendaftaran yang QRIS-nya kadaluarsa harus dibersihkan supaya email &
     * username-nya bisa dipakai daftar ulang. Sebelumnya user + eventner
     * nyangkut: tidak bisa login (is_active false) dan tidak bisa daftar ulang
     * (email/username masih unique).
     */
    public function test_pendaftaran_kadaluarsa_dibersihkan(): void
    {
        $user = User::factory()->create(['role' => 'Eventner', 'is_active' => false]);
        $eventner = $this->agedEventner([
            'user_id' => $user->id,
            'plan' => 'paid',
            'status' => 'pending',
            'registration_paid_at' => null,
            'autogopay_transaction_id' => 'AGP-ABANDON-001',
        ]);

        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-ABANDON-001', 'transaction_status' => 'expire'],
            ], 200),
        ]);

        (new \App\Jobs\SyncPendingPayments)->handle();

        $this->assertNull(Eventner::find($eventner->id), 'eventner kadaluarsa harus terhapus');
        $this->assertNull(User::find($user->id), 'user ikut terhapus supaya email bebas dipakai lagi');
    }

    /**
     * Yang TIDAK boleh dihapus: sudah dibayar, akun aktif, atau transaksinya
     * masih bisa dibayar.
     */
    public function test_cleanup_tidak_menghapus_yang_sudah_bayar_atau_masih_pending(): void
    {
        $bayar = $this->agedEventner([
            'plan' => 'paid',
            'status' => 'pending',
            'registration_paid_at' => now(),
            'autogopay_transaction_id' => 'AGP-AMAN-001',
        ]);
        $aktif = $this->agedEventner([
            'plan' => 'paid',
            'status' => 'pending',
            'registration_paid_at' => null,
            'autogopay_transaction_id' => 'AGP-AKTIF-001',
        ]);
        $aktif->user->update(['is_active' => true]);
        $masihPending = $this->agedEventner([
            'plan' => 'paid',
            'status' => 'pending',
            'registration_paid_at' => null,
            'autogopay_transaction_id' => 'AGP-PENDING-001',
        ]);

        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'X', 'transaction_status' => 'pending'],
            ], 200),
        ]);

        (new \App\Jobs\SyncPendingPayments)->handle();

        $this->assertNotNull(Eventner::find($bayar->id), 'sudah dibayar tidak boleh dihapus');
        $this->assertNotNull(Eventner::find($aktif->id), 'akun aktif tidak boleh dihapus');
        $this->assertNotNull(Eventner::find($masihPending->id), 'transaksi masih pending tidak boleh dihapus');
    }

    /**
     * Endpoint status error → jangan hapus apa pun. Lebih baik nyangkut
     * sehari lagi daripada menghapus pendaftaran yang sebenarnya sudah dibayar.
     */
    public function test_cleanup_tidak_menghapus_saat_cek_status_gagal(): void
    {
        $eventner = $this->agedEventner([
            'plan' => 'paid',
            'status' => 'pending',
            'registration_paid_at' => null,
            'autogopay_transaction_id' => 'AGP-ERROR-001',
        ]);

        Http::fake(['*/qris/status' => Http::response('server error', 500)]);

        (new \App\Jobs\SyncPendingPayments)->handle();

        $this->assertNotNull(Eventner::find($eventner->id), 'cek status gagal → jangan hapus');
    }
}

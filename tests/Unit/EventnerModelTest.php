<?php

namespace Tests\Unit;

use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventnerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_generated_on_create()
    {
        $eventner = Eventner::factory()->create([
            'nama_event' => 'Lomba Pramuka Se-Jawa Barat',
            'slug' => null,
        ]);

        $this->assertNotNull($eventner->slug);
        $this->assertStringContainsString('lomba-pramuka', $eventner->slug);
    }

    public function test_slug_updated_when_nama_event_changes()
    {
        $eventner = Eventner::factory()->create();
        $oldSlug = $eventner->slug;

        $eventner->update(['nama_event' => 'Event Baru Total']);

        $this->assertNotEquals($oldSlug, $eventner->fresh()->slug);
        $this->assertStringContainsString('event-baru-total', $eventner->fresh()->slug);
    }

    public function test_slug_stays_same_when_other_fields_change()
    {
        $eventner = Eventner::factory()->create();
        $oldSlug = $eventner->slug;

        $eventner->update(['lokasi' => 'Bandung']);

        $this->assertEquals($oldSlug, $eventner->fresh()->slug);
    }

    public function test_slug_collision_handled()
    {
        $eventner1 = Eventner::factory()->create(['nama_event' => 'Sama']);
        $eventner2 = Eventner::factory()->create(['nama_event' => 'Sama']);

        $this->assertNotNull($eventner1->slug);
        $this->assertNotNull($eventner2->slug);
        $this->assertNotEquals($eventner1->slug, $eventner2->slug);
    }

    public function test_is_on_trial_when_plan_free_and_not_expired()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(10),
        ]);

        $this->assertTrue($eventner->isOnTrial());
        $this->assertFalse($eventner->isTrialExpired());
    }

    public function test_trial_expired_correctly()
    {
        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertFalse($eventner->isOnTrial());
        $this->assertTrue($eventner->isTrialExpired());
    }

    public function test_paid_plan_not_on_trial()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertFalse($eventner->isOnTrial());
        $this->assertFalse($eventner->isTrialExpired());
    }

    public function test_trial_days_left()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->assertEquals(5, $eventner->trialDaysLeft());
    }

    public function test_trial_days_left_zero_when_not_on_trial()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertEquals(0, $eventner->trialDaysLeft());
    }

    public function test_can_access_feature_when_paid()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertTrue($eventner->canAccessFeature('vote_settings'));
        $this->assertTrue($eventner->canAccessFeature('unknown_feature'));
    }

    public function test_can_access_feature_when_on_trial()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertTrue($eventner->canAccessFeature('vote_settings'));
    }

    public function test_cannot_access_locked_feature_when_trial_expired()
    {
        config(['eventner_features.tickets' => ['label' => 'Tiket Event', 'locked_free' => true]]);

        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertFalse($eventner->canAccessFeature('tickets'));
    }

    public function test_can_access_unlocked_feature_when_trial_expired()
    {
        config(['eventner_features.some_feature' => ['label' => 'Some Feature', 'locked_free' => false]]);

        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertTrue($eventner->canAccessFeature('some_feature'));
    }

    public function test_can_access_unknown_feature_always()
    {
        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertTrue($eventner->canAccessFeature('completely_unknown'));
    }

    public function test_locked_features_empty_when_paid()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertEmpty($eventner->lockedFeatures());
    }

    public function test_locked_features_empty_when_on_trial()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->assertEmpty($eventner->lockedFeatures());
    }

    public function test_locked_features_contains_gated_keys_when_expired()
    {
        config(['eventner_features' => [
            'tickets' => ['label' => 'Tiket Event', 'locked_free' => true],
            'free_feature' => ['label' => 'Free', 'locked_free' => false],
        ]]);

        $eventner = Eventner::factory()->expiredTrial()->create();
        $locked = $eventner->lockedFeatures();

        $this->assertArrayHasKey('tickets', $locked);
        $this->assertArrayNotHasKey('free_feature', $locked);
    }

    // ── Paket dari DB ───────────────────────────────────────────────────
    //
    // Empat bentuk eventner:
    //
    //   trial aktif    → semua terbuka
    //   trial lewat    → fitur config terkunci
    //   paket gratis   → fitur config terkunci (nol feature_key)
    //   paket berbayar → hanya fitur paketnya yang terbuka
    //
    // Bentuk "paket gratis" yang paling mudah salah: plan-nya tetap 'free'
    // sementara trial_ends_at sudah dinolkan assignPlan(). Cabang yang
    // bercabang pada `plan` saja akan menjatuhkannya ke aturan trial habis —
    // hasil akhirnya kebetulan sama-sama mengunci, tapi lewat jalur yang
    // salah, dan itu baru terlihat pada fitur config ber-locked_free=false.

    /** Fitur di luar config tak pernah dikunci, apa pun paketnya. */
    public function test_fitur_di_luar_config_terbuka_untuk_paket_berbayar()
    {
        config(['eventner_features' => ['tickets' => ['label' => 'Tiket', 'locked_free' => true]]]);

        $plan = $this->buatPaket(['certificate']);
        $eventner = Eventner::factory()->paid()->create(['saas_plan_id' => $plan->id]);

        $this->assertFalse($eventner->canAccessFeature('tickets'), 'Tidak dicentang di paket.');
        $this->assertTrue($eventner->canAccessFeature('certificate'));

        // Halaman log aktivitas pernah melempar pengguna berbayar ke /upgrade
        // karena cabang paket memeriksa daftar feature_key untuk SEMUA kunci,
        // termasuk yang tak pernah ada di config.
        $this->assertTrue($eventner->canAccessFeature('activity_log'));
        $this->assertTrue($eventner->canAccessFeature('scoring'));
    }

    /** Paket gratis terkunci di fitur config, bukan di seluruh aplikasi. */
    public function test_paket_gratis_terkunci_hanya_di_fitur_config()
    {
        config(['eventner_features' => ['tickets' => ['label' => 'Tiket', 'locked_free' => true]]]);

        $plan = $this->buatPaket([], isFree: true);
        $eventner = Eventner::factory()->paketGratis()->create(['saas_plan_id' => $plan->id]);

        $this->assertFalse($eventner->canAccessFeature('tickets'));
        $this->assertTrue($eventner->canAccessFeature('activity_log'));
        $this->assertTrue($eventner->canAccessFeature('scoring'));
        $this->assertArrayHasKey('tickets', $eventner->lockedFeatures());
    }

    /**
     * Baris fitur terselip di paket gratis diabaikan.
     *
     * Admin bisa mengubah paket berbayar menjadi gratis tanpa membersihkan
     * centang fiturnya. Membaca baris sisa itu akan membuat paket gratis
     * membuka fitur premium — kebalikan dari artinya.
     */
    public function test_paket_gratis_mengabaikan_baris_fitur_terselip()
    {
        config(['eventner_features' => ['tickets' => ['label' => 'Tiket', 'locked_free' => true]]]);

        $plan = $this->buatPaket([], isFree: true);
        $plan->features()->create(['feature_key' => 'tickets']);

        $eventner = Eventner::factory()->paketGratis()->create(['saas_plan_id' => $plan->id]);

        $this->assertFalse($eventner->canAccessFeature('tickets'), 'Identitas paket gratis = is_free, bukan isi fiturnya.');
        $this->assertSame([], $eventner->fiturPaket());
    }

    /** Paket gratis tak boleh terbaca sebagai trial yang masih berjalan. */
    public function test_paket_gratis_bukan_trial()
    {
        $plan = $this->buatPaket([], isFree: true);
        $eventner = Eventner::factory()->paketGratis()->create(['saas_plan_id' => $plan->id]);

        $this->assertFalse($eventner->isOnTrial());
        $this->assertFalse($eventner->isTrialExpired());
        $this->assertSame(0, $eventner->trialDaysLeft());
        $this->assertTrue($eventner->hasActivePlan());
    }

    /** Paket yang dihapus admin (id yatim) → aturan legacy, bukan error. */
    public function test_paket_yatim_jatuh_ke_aturan_legacy()
    {
        config(['eventner_features' => ['tickets' => ['label' => 'Tiket', 'locked_free' => true]]]);

        $plan = $this->buatPaket(['certificate']);
        $eventner = Eventner::factory()->paid()->create(['saas_plan_id' => $plan->id]);

        // Relasi nullOnDelete: paketnya hilang, kolom saas_plan_id tertinggal.
        SaasPlan::where('id', $plan->id)->delete();
        $eventner->refresh();

        $this->assertNull($eventner->saasPlan);
        $this->assertFalse($eventner->punyaPaketDb());
        $this->assertTrue($eventner->canAccessFeature('tickets'), 'Legacy paid tetap akses penuh.');
    }

    /**
     * Fitur config yang memang tak dikunci tetap terbuka di paket gratis.
     *
     * Inilah bedanya jalur paket dan jalur trial-habis: keduanya mengunci
     * fitur ber-locked_free, jadi hasilnya baru berbeda di sini.
     */
    public function test_paket_gratis_tidak_mengunci_fitur_config_yang_terbuka()
    {
        config(['eventner_features' => [
            'tickets' => ['label' => 'Tiket', 'locked_free' => true],
            'laporan' => ['label' => 'Laporan', 'locked_free' => false],
        ]]);

        $plan = $this->buatPaket([], isFree: true);
        $eventner = Eventner::factory()->paketGratis()->create(['saas_plan_id' => $plan->id]);

        $this->assertTrue($eventner->canAccessFeature('laporan'));
        $this->assertArrayNotHasKey('laporan', $eventner->lockedFeatures());
    }

    private function buatPaket(array $features, bool $isFree = false): SaasPlan
    {
        $plan = SaasPlan::create([
            'name' => $isFree ? 'Gratis' : 'Event Penuh',
            'slug' => Str::slug(($isFree ? 'gratis' : 'penuh') . '-' . Str::random(6)),
            'price' => $isFree ? 0 : 150000,
            'registration_fee' => 0,
            'is_active' => true,
            'is_free' => $isFree,
            'is_contact' => false,
            'sort_order' => 0,
        ]);

        $plan->features()->createMany(
            collect($features)->map(fn ($key) => ['feature_key' => $key])->all()
        );

        return $plan;
    }

    public function test_public_url_with_subdomain()
    {
        config(['app.url' => 'http://berbaris.test']);
        $eventner = Eventner::factory()->create([
            'subdomain' => 'kejurcab',
            'slug' => 'kejurcab-abc12',
        ]);

        $url = $eventner->publicUrl('detail');

        $this->assertStringContainsString('kejurcab.berbaris.test', $url);
    }

    public function test_public_url_without_subdomain()
    {
        config(['app.url' => 'http://berbaris.test']);
        $eventner = Eventner::factory()->create([
            'subdomain' => null,
            'slug' => 'event-xyz99',
        ]);

        $url = $eventner->publicUrl('participant');

        $this->assertStringContainsString('event-xyz99', $url);
        $this->assertStringContainsString('/event/', $url);
    }
}

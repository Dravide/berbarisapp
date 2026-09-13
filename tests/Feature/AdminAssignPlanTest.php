<?php

namespace Tests\Feature;

use App\Livewire\Admin\Eventner\Index;
use App\Livewire\Admin\Eventner\Show;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAssignPlanTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(string $name, bool $isFree, array $features = [], array $attrs = []): SaasPlan
    {
        $plan = SaasPlan::create(array_merge([
            'name' => $name,
            // Migrasi seed sudah membuat slug 'gratis'/'event-penuh', jadi acak.
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)),
            'price' => $isFree ? 0 : 150000,
            'registration_fee' => $isFree ? 0 : 50000,
            'is_active' => true,
            'is_free' => $isFree,
            'is_contact' => false,
            'sort_order' => 1,
        ], $attrs));

        // Paket gratis tidak menyimpan feature_key (lihat PricingSettings::savePlan).
        if (!$isFree) {
            $plan->features()->createMany(
                collect($features)->map(fn($key) => ['feature_key' => $key])->all()
            );
        }

        return $plan;
    }

    private function formData(array $override = []): array
    {
        return array_merge([
            'nama_event' => 'Lomba Baris Berbaris 2026',
            'diselenggarakan_oleh' => 'Panitia PBB',
            'lokasi' => 'Jakarta',
            'tanggal' => '2026-10-01',
            'username' => 'panitiapbb',
            'email' => 'panitia@example.test',
        ], $override);
    }

    // ────────────────────────────────────────────────
    // Membuat eventner dengan paket
    // ────────────────────────────────────────────────

    public function test_admin_membuat_eventner_dengan_paket_berbayar()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $plan = $this->makePlan('Event Penuh', false, ['tickets', 'certificate']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set($this->formData(['saas_plan_id' => $plan->id]))
            ->call('save')
            ->assertHasNoErrors();

        $eventner = Eventner::where('nama_event', 'Lomba Baris Berbaris 2026')->firstOrFail();

        $this->assertSame('paid', $eventner->plan);
        $this->assertSame($plan->id, $eventner->saas_plan_id);
        $this->assertNotNull($eventner->registration_paid_at);
        $this->assertNull($eventner->trial_ends_at);
        $this->assertSame('admin', $eventner->registration_source);

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertTrue($eventner->canAccessFeature('certificate'));
        $this->assertFalse($eventner->canAccessFeature('livestream'));
    }

    /**
     * Paket gratis tidak menyimpan satu pun feature_key. Kalau dipasang sebagai
     * plan='paid', canAccessFeature() membaca daftar fitur yang kosong dan
     * mengunci SEMUA fitur — bukan membukanya. Tes ini yang menangkap itu.
     */
    public function test_paket_gratis_tidak_mengunci_semua_fitur()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $plan = $this->makePlan('Gratis', true);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set($this->formData(['saas_plan_id' => $plan->id]))
            ->call('save')
            ->assertHasNoErrors();

        $eventner = Eventner::where('nama_event', 'Lomba Baris Berbaris 2026')->firstOrFail();

        $this->assertSame('free', $eventner->plan, 'Paket gratis harus plan=free, bukan paid.');
        $this->assertSame($plan->id, $eventner->saas_plan_id);
        $this->assertNull($eventner->trial_ends_at, 'Paket gratis berlaku langsung, bukan trial.');
        $this->assertFalse($eventner->isOnTrial());

        // Fitur premium terkunci: benar untuk paket gratis.
        $this->assertFalse($eventner->canAccessFeature('tickets'));

        // Fitur di luar config tetap terbuka. Inilah yang rusak kalau paket
        // gratis dipasang sebagai plan='paid': cabang paid+saas_plan_id membaca
        // daftar feature_key paket yang kosong, sehingga kunci ini ikut tertutup.
        $this->assertTrue($eventner->canAccessFeature('scoring'));
        $this->assertTrue($eventner->hasActivePlan());
    }

    public function test_paket_wajib_dipilih_saat_membuat()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $this->makePlan('Event Penuh', false, ['tickets']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set($this->formData())
            ->call('save')
            ->assertHasErrors(['saas_plan_id']);

        $this->assertDatabaseMissing('eventners', ['nama_event' => 'Lomba Baris Berbaris 2026']);
    }

    public function test_paket_khusus_hubungi_admin_tidak_bisa_dipasang()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $contact = $this->makePlan('Paket Khusus', false, [], [
            'is_contact' => true,
            'contact_url' => 'https://wa.me/6281234567890',
        ]);

        // Tidak muncul di daftar paket.
        $this->assertFalse(
            (new Index())->plans->contains('id', $contact->id),
            'Paket is_contact tidak boleh muncul sebagai pilihan admin.'
        );

        // Dan tetap ditolak validasi kalau id-nya dipaksa lewat.
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set($this->formData(['saas_plan_id' => $contact->id]))
            ->call('save')
            ->assertHasErrors(['saas_plan_id']);
    }

    // ────────────────────────────────────────────────
    // Mengubah paket dari halaman detail
    // ────────────────────────────────────────────────

    public function test_admin_mengubah_paket_dari_halaman_detail()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $lama = $this->makePlan('Event Penuh', false, ['tickets', 'certificate']);
        $baru = $this->makePlan('Paket Vote', false, ['vote_settings', 'vote_results'], ['sort_order' => 2]);

        $eventner = Eventner::factory()->paid()->create([
            'saas_plan_id' => $lama->id,
            'registration_paid_at' => now(),
        ]);

        $this->assertTrue($eventner->canAccessFeature('tickets'));

        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $eventner->id])
            ->set('planId', $baru->id)
            ->call('savePlan')
            ->assertHasNoErrors();

        $eventner->refresh();

        $this->assertSame($baru->id, $eventner->saas_plan_id);
        $this->assertTrue($eventner->canAccessFeature('vote_settings'));
        $this->assertFalse($eventner->canAccessFeature('tickets'), 'Fitur paket lama harus tercabut.');
    }

    public function test_mengubah_paket_tidak_menimpa_tanggal_aktivasi()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $lama = $this->makePlan('Event Penuh', false, ['tickets']);
        $baru = $this->makePlan('Paket Lain', false, ['certificate'], ['sort_order' => 2]);

        $dibayar = now()->subDays(10);
        $eventner = Eventner::factory()->paid()->create([
            'saas_plan_id' => $lama->id,
            'registration_paid_at' => $dibayar,
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $eventner->id])
            ->set('planId', $baru->id)
            ->call('savePlan')
            ->assertHasNoErrors();

        $this->assertSame(
            $dibayar->toDateTimeString(),
            $eventner->refresh()->registration_paid_at->toDateTimeString()
        );
    }

    /**
     * Paket gratis menghasilkan plan='free' + trial_ends_at=null. Badge yang
     * bercabang pada plan === 'paid' akan menjatuhkannya ke cabang trial dan
     * menampilkan "Trial Berakhir" merah padahal paketnya baru dipasang admin.
     */
    public function test_paket_gratis_ditampilkan_aktif_bukan_trial_berakhir()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $plan = $this->makePlan('Gratis', true);

        $eventner = Eventner::factory()->create(['saas_plan_id' => null, 'plan' => 'free']);
        $eventner->assignPlan($plan, 'admin');

        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $eventner->id])
            ->assertSee('Aktif')
            ->assertDontSee('Trial Berakhir')
            ->assertSee('Paket gratis');
    }

    /**
     * Paket yang sudah terpasang tapi kemudian dinonaktifkan harus tetap
     * muncul sebagai opsi. Kalau hilang, select tidak punya nilai yang cocok
     * dan admin terkunci — menyimpan berarti memindahkan event ke paket lain.
     */
    public function test_paket_terpasang_yang_nonaktif_tetap_muncul_sebagai_opsi()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $dipakai = $this->makePlan('Paket Lama', false, ['tickets']);
        $lain = $this->makePlan('Paket Baru', false, ['certificate'], ['sort_order' => 2]);

        $eventner = Eventner::factory()->paid()->create([
            'saas_plan_id' => $dipakai->id,
            'registration_paid_at' => now(),
        ]);

        $dipakai->update(['is_active' => false]);

        // Paket nonaktif lain tidak ikut muncul.
        $lain->update(['is_active' => false]);

        $component = new Show();
        $component->eventnerId = $eventner->id;
        $component->loadData();

        $this->assertTrue($component->plans->contains('id', $dipakai->id), 'Paket terpasang tetap ditawarkan.');
        $this->assertFalse($component->plans->contains('id', $lain->id), 'Paket nonaktif yang tidak dipakai tidak ditawarkan.');

        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $eventner->id])
            ->assertSee('tidak ditawarkan lagi');
    }

    public function test_ubah_paket_tanpa_memilih_ditolak()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $plan = $this->makePlan('Event Penuh', false, ['tickets']);
        $eventner = Eventner::factory()->paid()->create(['saas_plan_id' => $plan->id]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $eventner->id])
            ->set('planId', '')
            ->call('savePlan')
            ->assertHasErrors(['planId']);

        $this->assertSame($plan->id, $eventner->refresh()->saas_plan_id);
    }

    // ────────────────────────────────────────────────
    // Eventner lama tetap apa adanya
    // ────────────────────────────────────────────────

    public function test_eventner_legacy_tetap_akses_penuh_sampai_diubah()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $this->makePlan('Event Penuh', false, ['tickets']);

        $legacy = Eventner::factory()->paid()->create(['saas_plan_id' => null]);

        $this->assertTrue($legacy->canAccessFeature('tickets'));
        $this->assertTrue($legacy->canAccessFeature('livestream'));
        $this->assertNull($legacy->saas_plan_id);

        // Halaman detail menawarkan opsi "Akses Penuh (legacy)".
        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $legacy->id])
            ->assertSee('Akses Penuh (legacy)')
            ->assertSet('planId', '');

        $this->assertNull($legacy->refresh()->saas_plan_id, 'Membuka halaman tidak mengubah apa pun.');
    }

    public function test_opsi_legacy_tidak_muncul_untuk_eventner_berpaket()
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $plan = $this->makePlan('Event Penuh', false, ['tickets']);
        $eventner = Eventner::factory()->paid()->create(['saas_plan_id' => $plan->id]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['id' => $eventner->id])
            ->assertDontSee('Akses Penuh (legacy)');
    }

    // ────────────────────────────────────────────────
    // Hak akses
    // ────────────────────────────────────────────────

    public function test_halaman_admin_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.eventner.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.eventner.show', 1))->assertForbidden();
    }
}

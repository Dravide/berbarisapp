<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\RevenueDashboard;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Konversi trial → berbayar di panel Pendapatan Platform.
 *
 * Yang dijaga di sini bukan rumus aritmetikanya, tapi SATU hal yang paling
 * mahal kalau salah: `plan` dan `registration_paid_at` masing-masing ambigu,
 * dan panel pendapatan yang membacanya sendirian akan melaporkan uang yang
 * tidak pernah masuk kas.
 *
 *   - `plan='paid'` juga dipakai pendaftar yang QRIS-nya belum lunas.
 *   - `registration_paid_at` juga diisi assignPlan() untuk paket GRATIS
 *     hibah admin (lihat komentar di Eventner::assignPlan()).
 *
 * Jadi dua predikat model — pernahTrial() dan isPembeli() — yang menentukan,
 * dan tesnya menjaga keduanya sekaligus menjaga corongnya benar-benar
 * membagi habis tanpa tumpang tindih.
 */
class TrialConversionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    private function tarifPaket(): int
    {
        return (int) \App\Models\Setting::get('eventner_plan_price', 150000);
    }

    /** Paket berbayar dari DB — dipakai untuk kasus "diberikan admin". */
    private function paketBerbayar(): SaasPlan
    {
        return SaasPlan::create([
            'name' => 'Event Penuh Uji',
            'slug' => 'event-penuh-uji-' . Str::lower(Str::random(6)),
            'price' => $this->tarifPaket(),
            'registration_fee' => 50000,
            'is_active' => true,
            'is_free' => false,
            'is_contact' => false,
            'sort_order' => 2,
        ]);
    }

    private function paketGratis(): SaasPlan
    {
        return SaasPlan::create([
            'name' => 'Gratis Uji',
            'slug' => 'gratis-uji-' . Str::lower(Str::random(6)),
            'price' => 0,
            'registration_fee' => 0,
            'is_active' => true,
            'is_free' => true,
            'is_contact' => false,
            'sort_order' => 1,
        ]);
    }

    /**
     * Pembeli sungguhan: daftar mandiri, paket berbayar, pembayaran tercatat.
     *
     * `registration_source => 'self'` yang membedakannya dari hibah admin —
     * hanya pendaftar mandiri yang melewati QRIS.
     */
    private function pembeli(array $atribut = []): Eventner
    {
        return Eventner::factory()->paid()->create(array_merge([
            'registration_source' => 'self',
            'registration_paid_at' => now(),
            'trial_ends_at' => null,
        ], $atribut));
    }

    /** Pendaftar mandiri paket gratis — inilah yang dapat masa uji 3 hari. */
    private function pendaftarTrial(array $atribut = []): Eventner
    {
        return Eventner::factory()->create(array_merge([
            'plan' => 'free',
            'registration_source' => 'self',
            'trial_ends_at' => now()->addDays(3),
        ], $atribut));
    }

    // ────────────────────────────────────────────────
    // Predikat model
    // ────────────────────────────────────────────────

    public function test_pembeli_butuh_pembayaran_tercatat()
    {
        // Ber-plan 'paid' sejak mendaftar, QRIS belum lunas: bukan pembeli.
        $belum = Eventner::factory()->paid()->create([
            'registration_source' => 'self',
            'registration_paid_at' => null,
        ]);

        $this->assertFalse($belum->isPembeli());
    }

    public function test_pembeli_hibah_admin_bukan_pembeli()
    {
        $eventner = Eventner::factory()->create(['plan' => 'free']);
        $eventner->assignPlan($this->paketBerbayar(), 'admin');

        // assignPlan() menstempel registration_paid_at untuk paket ini juga,
        // jadi kolom itu sendirian tak boleh dijadikan bukti pembayaran.
        $this->assertNotNull($eventner->fresh()->registration_paid_at);
        $this->assertFalse($eventner->fresh()->isPembeli());
    }

    public function test_pernah_trial_kosong_untuk_paket_gratis_terpasang()
    {
        $eventner = Eventner::factory()->create(['plan' => 'free']);
        $eventner->assignPlan($this->paketGratis(), 'admin');

        // assignPlan() menolkan trial_ends_at: paket terpasang langsung
        // berlaku, dan tak boleh terbaca sebagai trial.
        $this->assertFalse($eventner->fresh()->pernahTrial());
    }

    public function test_pernah_trial_bertahan_setelah_upgrade()
    {
        // Bentuk paling mudah salah dibaca: pendaftar trial yang kemudian
        // membeli. plan menjadi 'paid', trial_ends_at tetap terisi karena
        // jalur upgrade nyata (webhook & Upgrade poll) hanya menulis plan,
        // status, approved_at, dan registration_paid_at.
        $eventner = $this->pendaftarTrial([
            'plan' => 'paid',
            'trial_ends_at' => now()->subDay(),
            'registration_paid_at' => now(),
        ]);

        $this->assertTrue($eventner->pernahTrial(), 'Jejak trial harus bertahan setelah upgrade.');
        $this->assertTrue($eventner->isPembeli());
        $this->assertFalse($eventner->isOnTrial(), 'Sudah berbayar, bukan lagi masa uji.');
        $this->assertFalse($eventner->isTrialExpired(), 'Yang sudah membeli bukan prospek.');
    }

    // ────────────────────────────────────────────────
    // Corong membagi habis
    // ────────────────────────────────────────────────

    public function test_corong_membagi_habis_tanpa_tumpang_tindih()
    {
        // Satu eventner untuk tiap bentuk, termasuk dua yang paling mudah
        // saling menutupi: paket berbayar hibah admin dan pendaftar yang
        // QRIS-nya belum lunas — keduanya ber-plan 'paid'.
        $this->pendaftarTrial();
        $this->pendaftarTrial(['trial_ends_at' => now()->subDay()]);
        $this->pendaftarTrial([
            'plan' => 'paid',
            'trial_ends_at' => now()->subDay(),
            'registration_paid_at' => now(),
        ]);
        $this->pembeli();
        $this->paketGratisHibah();
        $this->paketBerbayarHibah();
        $this->belumLunas();
        Eventner::factory()->create(['plan' => 'free', 'trial_ends_at' => null]);

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('trialAktif', 1)
            ->assertSet('trialHabis', 1)
            ->assertSet('trialConverted', 1)
            ->assertSet('paidLangsung', 1)
            ->assertSet('diberikanAdmin', 2)
            ->assertSet('percobaanBerbayar', 1)
            ->assertSet('belumPernahTrial', 1);
    }

    private function paketGratisHibah(): Eventner
    {
        $eventner = Eventner::factory()->create(['plan' => 'free']);
        $eventner->assignPlan($this->paketGratis(), 'admin');

        return $eventner;
    }

    private function paketBerbayarHibah(): Eventner
    {
        $eventner = Eventner::factory()->create(['plan' => 'free']);
        $eventner->assignPlan($this->paketBerbayar(), 'admin');

        return $eventner;
    }

    private function belumLunas(): Eventner
    {
        return Eventner::factory()->paid()->create([
            'registration_source' => 'self',
            'registration_paid_at' => null,
            'trial_ends_at' => null,
        ]);
    }

    // ────────────────────────────────────────────────
    // Yang tidak boleh masuk pendapatan
    // ────────────────────────────────────────────────

    public function test_paket_gratis_hibah_admin_bukan_pendapatan()
    {
        // Bug yang dijaga: assignPlan() menstempel registration_paid_at, dan
        // versi lama menghitung pendapatan dari kolom itu saja — tanpa melihat
        // plan. Paket gratis yang diberikan admin ikut terhitung sebagai
        // penjualan paket.
        $this->paketGratisHibah();

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('saasRevenue', 0.0)
            ->assertSet('paidEventners', 0)
            ->assertSet('diberikanAdmin', 1);
    }

    public function test_paket_berbayar_hibah_admin_bukan_pendapatan()
    {
        $this->paketBerbayarHibah();

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('saasRevenue', 0.0)
            ->assertSet('diberikanAdmin', 1)
            ->assertSet('percobaanBerbayar', 0, 'Hibah admin sudah lunas, bukan percobaan berbayar.');
    }

    public function test_pendaftar_belum_lunas_bukan_pendapatan()
    {
        $this->belumLunas();

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('saasRevenue', 0.0)
            ->assertSet('paidEventners', 0)
            ->assertSet('percobaanBerbayar', 1);
    }

    public function test_trial_aktif_bukan_pendapatan()
    {
        $this->pendaftarTrial();

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('saasRevenue', 0.0)
            ->assertSet('trialAktif', 1);
    }

    public function test_pembeli_sungguhan_dihitung_pendapatan()
    {
        $this->pembeli();

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('saasRevenue', (float) $this->tarifPaket())
            ->assertSet('paidEventners', 1);
    }

    // ────────────────────────────────────────────────
    // Angka konversi
    // ────────────────────────────────────────────────

    public function test_konversi_memakai_penyebut_yang_sudah_diputuskan()
    {
        // 3 dari 4 trial yang sudah selesai berujung beli.
        $this->pendaftarTrial(['plan' => 'paid', 'trial_ends_at' => now()->subDay(), 'registration_paid_at' => now()]);
        $this->pendaftarTrial(['plan' => 'paid', 'trial_ends_at' => now()->subDay(), 'registration_paid_at' => now()]);
        $this->pendaftarTrial(['plan' => 'paid', 'trial_ends_at' => now()->subDay(), 'registration_paid_at' => now()]);
        $this->pendaftarTrial(['trial_ends_at' => now()->subDay()]);

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('conversionRate', 75.0);
    }

    public function test_trial_berjalan_tak_menurunkan_angka_konversi()
    {
        // 1 beli, 1 gagal, 5 masih berjalan. Yang berjalan belum menjawab
        // apa pun, jadi angkanya tetap 50% — bukan 1/7.
        $this->pendaftarTrial(['plan' => 'paid', 'trial_ends_at' => now()->subDay(), 'registration_paid_at' => now()]);
        $this->pendaftarTrial(['trial_ends_at' => now()->subDay()]);

        for ($i = 0; $i < 5; $i++) {
            $this->pendaftarTrial();
        }

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('conversionRate', 50.0)
            ->assertSet('trialAktif', 5);
    }

    public function test_konversi_nol_tanpa_trial_yang_selesai()
    {
        $this->pembeli();

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('conversionRate', 0);
    }

    // ────────────────────────────────────────────────
    // Tren bulanan
    // ────────────────────────────────────────────────

    public function test_tren_bulanan_hanya_dari_pembeli_mandiri()
    {
        $this->pembeli();
        $this->paketGratisHibah();
        $this->paketBerbayarHibah();

        $bulanIni = collect(Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->get('monthlyData'))->last();

        $this->assertSame($this->tarifPaket(), $bulanIni['saas'],
            'Hibah admin tak boleh menaikkan tren pendapatan SaaS.');
    }

    public function test_tren_dashboard_admin_hanya_dari_pembeli_mandiri()
    {
        $this->pembeli();
        $this->paketGratisHibah();

        $tren = collect(Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->get('revenueTrend'));

        $this->assertSame($this->tarifPaket(), $tren->last()['total'],
            'Dashboard dan panel pendapatan harus memakai saringan yang sama.');
    }

    // ────────────────────────────────────────────────
    // Badge daftar teratas
    // ────────────────────────────────────────────────

    /** Top Eventner hanya memuat eventner yang punya transaksi tiket/vote. */
    private function denganTiket(Eventner $eventner): Eventner
    {
        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli Uji',
            'buyer_email' => 'beli@example.test',
            'quantity' => 1,
            'price_per_ticket' => 25000,
            'total_amount' => 25000,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        return $eventner;
    }

    public function test_badge_teratas_membaca_fakta_pembayaran()
    {
        $this->denganTiket($this->pembeli(['nama_event' => 'Event Lunas']));

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('topEventners.0.paket_label', 'Berbayar')
            ->assertSet('topEventners.0.paket_class', 'bg-success');
    }

    public function test_badge_teratas_menandai_yang_belum_lunas()
    {
        // Ber-plan 'paid' tapi QRIS belum lunas: badge dari `plan` akan
        // menandainya hijau, padahal uangnya belum masuk.
        $this->denganTiket($this->belumLunas()->fresh());

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('topEventners.0.paket_label', 'Belum bayar')
            ->assertSet('topEventners.0.paket_class', 'bg-warning');
    }

    public function test_badge_teratas_menandai_hibah_admin()
    {
        $this->denganTiket($this->paketBerbayarHibah());

        Livewire::actingAs($this->admin())
            ->test(RevenueDashboard::class)
            ->assertSet('topEventners.0.paket_label', 'Diberikan admin')
            ->assertSet('topEventners.0.paket_class', 'bg-secondary');
    }
}

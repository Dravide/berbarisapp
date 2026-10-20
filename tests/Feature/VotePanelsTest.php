<?php

namespace Tests\Feature;

use App\Livewire\Eventner\VoteBooster\Index as VoteBoosterIndex;
use App\Livewire\Eventner\VoteResults\Index as VoteResultsIndex;
use App\Livewire\Eventner\VoteResults\Show as VoteResultsShow;
use App\Livewire\Eventner\VoteSettings\Index as VoteSettingsIndex;
use App\Livewire\Eventner\VoteTransaction\Index as VoteTransactionIndex;
use App\Models\CompetitionCategory;
use App\Models\DeviceToken;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use App\Models\VoteBooster;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panel vote milik eventner — enam layar yang menagih, menghitung, dan
 * melaporkan uang, dan sebelumnya tidak punya tes langsung sama sekali.
 *
 * Yang diuji di sini bukan tata letaknya, melainkan tiga hal yang paling mahal
 * kalau salah: batas yang menentukan nominal QRIS (VoteSettings, VoteBooster),
 * angka yang dilaporkan ke penyelenggara (VoteResults, VoteTransaction), dan
 * sekat antar-event — tiap panel dibuka oleh eventner yang login, jadi tiap
 * kueri harus dibatasi `eventner_id` miliknya, bukan sekadar id barisnya.
 */
class VotePanelsTest extends TestCase
{
    use RefreshDatabase;

    /** Eventner berbayar — fitur vote terkunci untuk paket gratis. */
    private function panitia(array $atribut = []): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->voteAktif()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'paid',
            'trial_ends_at' => null,
            'vote_price' => 1000,
        ], $atribut));

        return [$user, $eventner];
    }

    /**
     * Kategori anak milik event — dibuat sekali, supaya dua kontingen bisa
     * ditaruh di kategori yang sama (klasemen dihitung per kategori).
     */
    private function kategori(Eventner $eventner): CompetitionCategory
    {
        $induk = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);

        return CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $induk->id,
        ]);
    }

    /** Kategori anak + satu kontingen di dalamnya. */
    private function kontingen(
        Eventner $eventner,
        string $nama = 'SMP Satu',
        ?CompetitionCategory $kategori = null
    ): Registration {
        return Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => ($kategori ?? $this->kategori($eventner))->id,
            'nama_sekolah' => $nama,
        ]);
    }

    private function transaksi(
        Eventner $eventner,
        Registration $reg,
        int $amount,
        int $votes,
        string $status,
        string $trxId,
        array $tambahan = []
    ): VoteTransaction {
        return VoteTransaction::create(array_merge([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => $trxId,
            'qr_url' => 'https://example.test/qr/' . $trxId,
            'amount' => $amount,
            'votes_earned' => $votes,
            'voter_name' => 'Pembeli',
            'voter_email' => strtolower($trxId) . '@mail.com',
            'status' => $status,
            'paid_at' => $status === 'PAID' ? now() : null,
        ], $tambahan));
    }

    // ────────────────────────────────────────────────
    // VoteSettings — batas yang menentukan nominal QRIS
    // ────────────────────────────────────────────────

    /**
     * Batas atas jumlah vote per transaksi adalah satu-satunya hal yang
     * menghalangi pengunjung menentukan sendiri nominal QRIS-nya. Kalau kolom
     * ini boleh kosong, halaman vote publik kembali tanpa batas.
     */
    public function test_batas_vote_per_transaksi_tidak_boleh_kosong_atau_nol()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->set('vote_max_per_order', '')
            ->call('save')
            ->assertHasErrors(['vote_max_per_order' => 'required']);

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->set('vote_max_per_order', 0)
            ->call('save')
            ->assertHasErrors(['vote_max_per_order' => 'min']);
    }

    public function test_batas_vote_per_transaksi_dibatasi_seratus_ribu()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->set('vote_max_per_order', 100001)
            ->call('save')
            ->assertHasErrors(['vote_max_per_order' => 'max']);
    }

    /** Harga vote boleh nol (vote gratis), tapi tidak boleh negatif. */
    public function test_harga_vote_negatif_ditolak()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->set('vote_price', -1)
            ->call('save')
            ->assertHasErrors(['vote_price' => 'min']);
    }

    public function test_pengaturan_vote_tersimpan_ke_eventner_milik_user()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->set('vote_active', true)
            ->set('vote_price', 2500)
            ->set('vote_max_per_order', 250)
            ->set('vote_start', '2026-11-01T08:00')
            ->set('vote_end', '2026-11-02T20:00')
            ->call('save')
            ->assertHasNoErrors();

        $eventner->refresh();
        // `vote_active` tidak punya cast boolean di model — dibaca apa adanya
        // sebagai 1/0, jadi dibandingkan sebagai bool, bukan sebagai tipe.
        $this->assertTrue((bool) $eventner->vote_active);
        $this->assertSame(2500, (int) $eventner->vote_price);
        $this->assertSame(250, (int) $eventner->vote_max_per_order);
        $this->assertSame('2026-11-01 08:00', $eventner->vote_start->format('Y-m-d H:i'));
    }

    /** Kolom batas yang dikosongkan lewat form tidak boleh tersimpan kosong. */
    public function test_batas_tidak_pernah_tersimpan_kosong()
    {
        [$user, $eventner] = $this->panitia(['vote_max_per_order' => 500]);

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->set('vote_max_per_order', '')
            ->call('save')
            ->assertHasErrors('vote_max_per_order');

        $this->assertSame(500, (int) $eventner->fresh()->vote_max_per_order);
    }

    public function test_paket_gratis_dialihkan_ke_upgrade()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->expiredTrial()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        Livewire::actingAs($user)->test(VoteSettingsIndex::class)
            ->assertRedirect(route('eventner.billing.upgrade'));

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    // ────────────────────────────────────────────────
    // VoteBooster — pengali vote yang mengubah jumlah vote
    // ────────────────────────────────────────────────

    public function test_booster_dengan_jadwal_terbalik_ditolak()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->set('starts_at', '2026-11-05T10:00')
            ->set('ends_at', '2026-11-01T10:00')
            ->set('vote_multiplier', 3)
            ->call('save')
            ->assertHasErrors(['ends_at' => 'after']);

        $this->assertSame(0, VoteBooster::count());
    }

    public function test_pengali_booster_paling_kecil_dua()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->set('starts_at', now()->toDateTimeString())
            ->set('ends_at', now()->addDay()->toDateTimeString())
            ->set('vote_multiplier', 1)
            ->call('save')
            ->assertHasErrors(['vote_multiplier' => 'min']);
    }

    public function test_booster_tersimpan_untuk_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->set('starts_at', '2026-11-01T10:00')
            ->set('ends_at', '2026-11-02T10:00')
            ->set('vote_multiplier', 5)
            ->call('save')
            ->assertHasNoErrors();

        $booster = VoteBooster::firstOrFail();
        $this->assertSame($eventner->id, $booster->eventner_id);
        $this->assertSame(5, $booster->vote_multiplier);
        $this->assertTrue($booster->is_active);
    }

    /**
     * Pengali yang berjalan diambil dari jadwal — bukan dari baris terakhir
     * yang dibuat. Booster yang jadwalnya sudah lewat tidak boleh menang.
     */
    public function test_booster_berjalan_dipilih_dari_jadwal_dan_pengali_terbesar()
    {
        [$user, $eventner] = $this->panitia();

        VoteBooster::create([
            'eventner_id' => $eventner->id,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
            'vote_multiplier' => 50,
            'is_active' => true,
        ]);
        VoteBooster::create([
            'eventner_id' => $eventner->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'vote_multiplier' => 3,
            'is_active' => true,
        ]);
        VoteBooster::create([
            'eventner_id' => $eventner->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'vote_multiplier' => 7,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->assertViewHas('activeNow', fn ($b) => $b !== null && $b->vote_multiplier === 7);
    }

    public function test_booster_yang_dinonaktifkan_tidak_dianggap_berjalan()
    {
        [$user, $eventner] = $this->panitia();

        $booster = VoteBooster::create([
            'eventner_id' => $eventner->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'vote_multiplier' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->call('toggleActive', $booster->id);

        $this->assertFalse($booster->fresh()->is_active);

        Livewire::actingAs($user)->test(VoteBoosterIndex::class)
            ->assertViewHas('activeNow', fn ($b) => $b === null);
    }

    /** Booster event lain tidak boleh diubah maupun dihapus. */
    public function test_booster_event_lain_tidak_bisa_diubah()
    {
        [$user] = $this->panitia();
        $asing = VoteBooster::create([
            'eventner_id' => Eventner::factory()->create()->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'vote_multiplier' => 9,
            'is_active' => true,
        ]);

        try {
            Livewire::actingAs($user)->test(VoteBoosterIndex::class)
                ->call('toggleActive', $asing->id);
            $this->fail('Booster milik event lain seharusnya tidak ditemukan.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // benar — kueri dibatasi eventner_id milik user
        }

        $this->assertTrue($asing->fresh()->is_active, 'Booster event lain tidak boleh berubah.');
    }

    public function test_booster_event_lain_tidak_bisa_dihapus()
    {
        [$user] = $this->panitia();
        $asing = VoteBooster::create([
            'eventner_id' => Eventner::factory()->create()->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'vote_multiplier' => 9,
            'is_active' => true,
        ]);

        try {
            Livewire::actingAs($user)->test(VoteBoosterIndex::class)->call('delete', $asing->id);
            $this->fail('Booster milik event lain seharusnya tidak ditemukan.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // benar
        }

        $this->assertDatabaseHas('vote_boosters', ['id' => $asing->id]);
    }

    // ────────────────────────────────────────────────
    // VoteResults — angka yang dilaporkan ke penyelenggara
    // ────────────────────────────────────────────────

    /**
     * Peringkat hanya menghitung transaksi PAID, dan pendapatannya diambil dari
     * nominal yang benar-benar dibayar — bukan votes_earned dikali harga, karena
     * votes_earned sudah termasuk pengali booster.
     */
    public function test_klasemen_hanya_menghitung_transaksi_paid()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);

        $this->transaksi($eventner, $reg, 10000, 10, 'PAID', 'AGP-PAID');
        $this->transaksi($eventner, $reg, 90000, 90, 'PENDING', 'AGP-PENDING');
        $this->transaksi($eventner, $reg, 90000, 90, 'EXPIRED', 'AGP-EXPIRED');

        Livewire::actingAs($user)->test(VoteResultsIndex::class)
            ->assertViewHas('summary', function ($s) {
                $this->assertSame(1, (int) $s->trx_count);
                $this->assertSame(10, (int) $s->total_votes);
                $this->assertSame(10000, (int) $s->total_amount);

                return true;
            });
    }

    public function test_pendapatan_klasemen_memakai_nominal_bukan_vote_kali_harga()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);

        // 5 vote dengan pengali booster 10 = 50 votes_earned, tapi yang dibayar 5.000.
        $this->transaksi($eventner, $reg, 5000, 50, 'PAID', 'AGP-BOOST');

        Livewire::actingAs($user)->test(VoteResultsIndex::class)
            ->assertViewHas('results', function ($results) {
                $baris = $results->first();
                $this->assertSame(50, (int) $baris->total_votes);
                $this->assertSame(5000, (int) $baris->total_amount, 'Pendapatan harus nominal transaksi, bukan votes × harga.');

                return true;
            });
    }

    public function test_klasemen_diurutkan_dari_vote_terbanyak()
    {
        [$user, $eventner] = $this->panitia();

        // Klasemen dihitung per kategori yang sedang dibuka, jadi kedua
        // kontingen harus berada di kategori yang sama.
        $kategori = $this->kategori($eventner);
        $kecil = $this->kontingen($eventner, 'SMP Kecil', $kategori);
        $besar = $this->kontingen($eventner, 'SMP Besar', $kategori);

        $this->transaksi($eventner, $kecil, 1000, 1, 'PAID', 'AGP-KECIL');
        $this->transaksi($eventner, $besar, 5000, 5, 'PAID', 'AGP-BESAR');

        Livewire::actingAs($user)->test(VoteResultsIndex::class)
            ->assertViewHas('results', function ($results) use ($besar, $kecil) {
                $this->assertSame(
                    [$besar->id, $kecil->id],
                    $results->pluck('id')->all(),
                    'Klasemen harus urut dari vote terbanyak.'
                );

                return true;
            });
    }

    /** Klasemen tidak boleh memuat kontingen event lain. */
    public function test_klasemen_tidak_memuat_kontingen_event_lain()
    {
        [$user, $eventner] = $this->panitia();
        $this->kontingen($eventner, 'SMP Saya');

        $lain = Eventner::factory()->voteAktif()->create(['status' => 'approved']);
        $asing = $this->kontingen($lain, 'SMP Event Lain');

        Livewire::actingAs($user)->test(VoteResultsIndex::class)
            ->assertViewHas('results', function ($results) use ($asing) {
                $this->assertNotContains($asing->id, $results->pluck('id')->all());

                return true;
            });
    }

    /**
     * Kontingen event lain tidak bisa dibuka lewat rutenya.
     *
     * Dijaga di mount() komponennya, jadi satu GET sudah menguji jalan yang
     * sama dengan yang dipakai pengguna.
     */
    public function test_detail_voter_tidak_bisa_dibuka_lintas_event()
    {
        [$user, $eventner] = $this->panitia();
        $this->kontingen($eventner);

        $lain = Eventner::factory()->voteAktif()->create(['status' => 'approved']);
        $asing = $this->kontingen($lain, 'SMP Event Lain');

        $this->actingAs($user)
            ->get(route('eventner.vote-results.show', $asing->id))
            ->assertForbidden();
    }

    public function test_detail_voter_menampilkan_pembeli_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);

        VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-DETAIL',
            'qr_url' => 'https://example.test/qr.png',
            'amount' => 2000,
            'votes_earned' => 2,
            'voter_name' => 'Rina Pembeli',
            'voter_email' => 'rina@mail.com',
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)->test(VoteResultsShow::class, ['registration' => $reg])
            ->assertSee('Rina Pembeli')
            ->assertViewHas('summary', fn ($s) => (int) $s->total_votes === 2);
    }

    // ────────────────────────────────────────────────
    // VoteTransaction — sinkron & konfirmasi manual
    // ────────────────────────────────────────────────

    public function test_sinkron_menandai_transaksi_settlement_jadi_paid()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);
        $tx = $this->transaksi($eventner, $reg, 3000, 3, 'PENDING', 'AGP-SETTLE');

        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-SETTLE', 'transaction_status' => 'settlement'],
            ], 200),
        ]);

        Livewire::actingAs($user)->test(VoteTransactionIndex::class)->call('syncPending');

        $this->assertSame('PAID', $tx->fresh()->status);
        $this->assertNotNull($tx->fresh()->paid_at);
    }

    /** Kedaluwarsa dipetakan ke EXPIRED, batal ke FAILED — bukan dua-duanya sama. */
    public function test_sinkron_memetakan_expire_dan_cancel_ke_status_berbeda()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);
        $expire = $this->transaksi($eventner, $reg, 3000, 3, 'PENDING', 'AGP-EXPIRE');
        $cancel = $this->transaksi($eventner, $reg, 3000, 3, 'PENDING', 'AGP-CANCEL');

        // Dua transaksi memakai endpoint yang sama, jadi yang membedakan
        // jawabannya adalah isi permintaan — bukan URL-nya.
        Http::fake(function ($request) {
            $id = $request['transaction_id'] ?? '';
            $status = $id === 'AGP-EXPIRE' ? 'expire' : 'cancel';

            return Http::response([
                'success' => true,
                'data' => ['transaction_id' => $id, 'transaction_status' => $status],
            ], 200);
        });

        Livewire::actingAs($user)->test(VoteTransactionIndex::class)->call('syncPending');

        $this->assertSame('EXPIRED', $expire->fresh()->status);
        $this->assertSame('FAILED', $cancel->fresh()->status);
    }

    public function test_konfirmasi_manual_menandai_transaksi_pending_jadi_paid()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);
        $tx = $this->transaksi($eventner, $reg, 4000, 4, 'PENDING', 'AGP-MANUAL');

        Livewire::actingAs($user)->test(VoteTransactionIndex::class)
            ->call('markAsPaid', $tx->id);

        $this->assertSame('PAID', $tx->fresh()->status);
    }

    /** Transaksi yang sudah kedaluwarsa tidak boleh "dikonfirmasi" jadi lunas. */
    public function test_konfirmasi_manual_menolak_transaksi_bukan_pending()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);
        $tx = $this->transaksi($eventner, $reg, 4000, 4, 'EXPIRED', 'AGP-EXPIRED');

        Livewire::actingAs($user)->test(VoteTransactionIndex::class)
            ->call('markAsPaid', $tx->id);

        $this->assertSame('EXPIRED', $tx->fresh()->status, 'Hanya PENDING yang boleh dikonfirmasi manual.');
    }

    public function test_transaksi_event_lain_tidak_bisa_dikonfirmasi()
    {
        [$user] = $this->panitia();

        $lain = Eventner::factory()->voteAktif()->create(['status' => 'approved']);
        $regAsing = $this->kontingen($lain, 'SMP Event Lain');
        $asing = $this->transaksi($lain, $regAsing, 4000, 4, 'PENDING', 'AGP-ASING');

        try {
            Livewire::actingAs($user)->test(VoteTransactionIndex::class)->call('markAsPaid', $asing->id);
            $this->fail('Transaksi milik event lain seharusnya tidak ditemukan.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // benar
        }

        $this->assertSame('PENDING', $asing->fresh()->status);
    }

    public function test_ringkasan_transaksi_hanya_menghitung_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);
        $this->transaksi($eventner, $reg, 1000, 1, 'PAID', 'AGP-SAYA');

        $lain = Eventner::factory()->voteAktif()->create(['status' => 'approved']);
        $regLain = $this->kontingen($lain);
        $this->transaksi($lain, $regLain, 999000, 999, 'PAID', 'AGP-LAIN');

        Livewire::actingAs($user)->test(VoteTransactionIndex::class)
            ->assertViewHas('summaryPaid', fn ($s) => (int) $s->total_amount === 1000)
            ->assertViewHas('totalTransactionsCount', fn ($n) => $n === 1);
    }

    // ────────────────────────────────────────────────
    // Ekspor CSV — sekat yang sama, jalur yang berbeda
    // ────────────────────────────────────────────────

    public function test_csv_transaksi_hanya_memuat_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);
        $this->transaksi($eventner, $reg, 1000, 1, 'PAID', 'AGP-SAYA', ['voter_name' => 'Pembeli Saya']);

        $lain = Eventner::factory()->voteAktif()->create(['status' => 'approved']);
        $regLain = $this->kontingen($lain);
        $this->transaksi($lain, $regLain, 9000, 9, 'PAID', 'AGP-LAIN', ['voter_name' => 'Pembeli Lain']);

        $csv = $this->actingAs($user)
            ->get(route('eventner.vote-transactions.csv'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('AGP-SAYA', $csv);
        $this->assertStringNotContainsString('AGP-LAIN', $csv, 'Ekspor tidak boleh membocorkan transaksi event lain.');
    }

    public function test_csv_komentar_hanya_memuat_komentar_paid()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);

        $this->transaksi($eventner, $reg, 1000, 1, 'PAID', 'AGP-KOMEN', ['comment' => 'Semangat terus']);
        $this->transaksi($eventner, $reg, 1000, 1, 'PENDING', 'AGP-BELUM', ['comment' => 'Belum dibayar']);

        $csv = $this->actingAs($user)
            ->get(route('eventner.vote-comments.csv'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Semangat terus', $csv);
        $this->assertStringNotContainsString('Belum dibayar', $csv, 'Komentar transaksi yang belum lunas tidak boleh masuk laporan.');
    }

    // ────────────────────────────────────────────────
    // Notifikasi — satu layar yang menyentuh semua peserta
    // ────────────────────────────────────────────────

    public function test_notifikasi_broadcast_terkirim_ke_device_peserta_event()
    {
        [$user, $eventner] = $this->panitia();
        $reg = $this->kontingen($eventner);

        DeviceToken::create(['registration_id' => $reg->id, 'token' => 'TOKEN-A', 'platform' => 'android']);
        DeviceToken::create(['registration_id' => $reg->id, 'token' => 'TOKEN-B', 'platform' => 'ios']);

        $fcm = $this->mock(\App\Services\FcmService::class);
        $fcm->shouldReceive('sendToEvent')->once()->andReturn(2);

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Notification\Index::class)
            ->set('title', 'Technical Meeting')
            ->set('body', 'Hadir pukul 08.00 di aula.')
            ->set('target', 'broadcast')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('title', '');
    }

    public function test_notifikasi_terarah_wajib_memilih_kontingen()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Notification\Index::class)
            ->set('title', 'Halo')
            ->set('body', 'Pesan')
            ->set('target', 'registration')
            ->set('registrationId', null)
            ->call('send')
            ->assertHasErrors('registrationId');
    }

    /**
     * Validasi `exists` saja tidak cukup: id kontingen event LAIN memang ada di
     * tabel, jadi sekatnya harus dari kueri yang dibatasi eventner_id.
     */
    public function test_notifikasi_ke_kontingen_event_lain_tidak_terkirim()
    {
        [$user, $eventner] = $this->panitia();
        $this->kontingen($eventner);

        $lain = Eventner::factory()->voteAktif()->create(['status' => 'approved']);
        $asing = $this->kontingen($lain, 'SMP Event Lain');

        $fcm = $this->mock(\App\Services\FcmService::class);
        $fcm->shouldNotReceive('sendToModel');

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Notification\Index::class)
            ->set('title', 'Tes sekat')
            ->set('body', 'Tidak boleh terkirim.')
            ->set('target', 'registration')
            ->set('registrationId', $asing->id)
            ->call('send');
    }

    public function test_daftar_tujuan_hanya_peserta_yang_punya_device_token()
    {
        [$user, $eventner] = $this->panitia();
        $punyaToken = $this->kontingen($eventner, 'SMP Punya Token');
        $tanpaToken = $this->kontingen($eventner, 'SMP Tanpa Token');

        DeviceToken::create(['registration_id' => $punyaToken->id, 'token' => 'TOKEN-X']);

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Notification\Index::class)
            ->assertViewHas('registrations', function ($regs) use ($punyaToken, $tanpaToken) {
                $ids = $regs->pluck('id')->all();

                $this->assertContains($punyaToken->id, $ids);
                $this->assertNotContains($tanpaToken->id, $ids, 'Kontingen tanpa device tidak ada gunanya sebagai tujuan.');

                return true;
            });
    }

    public function test_judul_dan_isi_notifikasi_wajib_diisi()
    {
        [$user] = $this->panitia();

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Notification\Index::class)
            ->set('title', '')
            ->set('body', '')
            ->call('send')
            ->assertHasErrors(['title' => 'required', 'body' => 'required']);
    }
}

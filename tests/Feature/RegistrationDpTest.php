<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FinanceDashboard;
use App\Livewire\Public\MagicLink\Registration as Portal;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationPayment;
use App\Models\User;
use App\Services\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Uang muka (DP) biaya pendaftaran: peserta menyetor sebagian dulu.
 *
 * Yang dijaga di berkas ini adalah aturan yang paling mudah rusak diam-diam:
 *
 *  - DP membuka PENGISIAN, tidak membuka FINALISASI (dua gerbang terpisah);
 *  - uang yang masuk dihitung dari baris pembayaran berstatus verified, tidak
 *    pernah dari status pendaftaran;
 *  - tagihan snapshot (`total_fee`) tidak pernah ikut berubah karena DP;
 *  - DP yang sudah diterima tidak hilang saat bukti berikutnya ditolak.
 */
class RegistrationDpTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private User $user;
    private CompetitionCategory $induk;
    private CompetitionCategory $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
            'slug' => 'dp-uji',
            'subdomain' => null,
            // Booking: TM masih di depan, jadi gerbang isian benar-benar tertutup
            // tanpa pembayaran.
            'tanggal_pendaftaran' => now()->addDays(30)->format('Y-m-d'),
            'technical_meeting' => now()->addDays(5)->format('Y-m-d H:i:s'),
        ]);

        $this->induk = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->tingkat = CompetitionCategory::factory()->child($this->induk)
            ->for($this->eventner, 'eventner')
            ->create([
                'registration_fee' => 500000,
                'dp_amount' => 150000,
                'dp_days' => 14,
            ]);

        RegistrationField::ensureDefaults($this->eventner);
    }

    private function pendaftaran(array $ganti = []): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create(array_merge([
            'competition_category_id' => $this->tingkat->id,
            'status_berkas' => 'booking',
            'payment_status' => 'unpaid',
            'total_fee' => 500000,
            'amount_paid' => 0,
            'magic_token' => 'TOKEN-DP-0001',
        ], $ganti));
    }

    /** Baris pembayaran yang sudah diterima panitia, tanpa lewat UI. */
    private function terima(Registration $reg, float $nominal, string $jenis = RegistrationPayment::KIND_DP): RegistrationPayment
    {
        $bayar = $reg->registrationPayments()->create([
            'kind' => $jenis,
            'amount' => $nominal,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        app(PaymentRecorder::class)->terima($bayar, $this->user);

        return $bayar->fresh();
    }

    // ── Model: nominal DP ────────────────────────────────────────────────

    public function test_nominal_dp_dipakai_bila_tingkat_menetapkan_nominal_tetap()
    {
        $this->assertTrue($this->tingkat->punyaDp());
        $this->assertSame(150000.0, $this->tingkat->nominalDp(500000));
    }

    public function test_nominal_dp_dihitung_dari_persentase_bila_hanya_persen_yang_diisi()
    {
        $this->tingkat->update(['dp_amount' => null, 'dp_percent' => 30]);

        $this->assertSame(150000.0, $this->tingkat->nominalDp(500000));
    }

    /** DP tidak boleh menutup seluruh tagihan — itu bukan DP. */
    public function test_nominal_dp_dipotong_ke_total_tagihan()
    {
        $this->tingkat->update(['dp_amount' => 900000]);

        $this->assertSame(500000.0, $this->tingkat->nominalDp(500000));
        $this->assertNotNull($this->tingkat->alasanDpTidakSah(500000));
    }

    public function test_tingkat_tanpa_dp_tidak_menawarkan_uang_muka()
    {
        $this->tingkat->update(['dp_amount' => null, 'dp_percent' => null, 'dp_days' => null]);

        $this->assertFalse($this->tingkat->fresh()->punyaDp());
        $this->assertNull($this->tingkat->fresh()->nominalDp(500000));
    }

    public function test_pengaturan_dp_tidak_sah_ditolak_server()
    {
        $this->tingkat->update(['dp_amount' => 150000, 'dp_percent' => 30]);
        $this->assertStringContainsString('jangan keduanya', (string) $this->tingkat->alasanDpTidakSah(500000));

        $this->tingkat->update(['dp_amount' => null, 'dp_percent' => 100]);
        $this->assertNotNull($this->tingkat->alasanDpTidakSah(500000));

        $this->tingkat->update(['dp_percent' => 30, 'dp_days' => 0]);
        $this->assertStringContainsString('minimal 1 hari', (string) $this->tingkat->alasanDpTidakSah(500000));
    }

    // ── Ledger: uang yang masuk ──────────────────────────────────────────

    public function test_dp_terverifikasi_menjadikan_status_dp_paid_dan_menghitung_sisa()
    {
        $reg = $this->pendaftaran();

        $this->terima($reg, 150000);
        $reg->refresh();

        $this->assertSame('dp_paid', $reg->payment_status);
        $this->assertSame(150000.0, (float) $reg->amount_paid);
        $this->assertSame(350000.0, $reg->sisaTagihan());
        $this->assertTrue($reg->sudahDp());
        // Belum lunas: kolom verifikasi lama tidak boleh terisi, kalau tidak
        // invoice gabungan dan laporan akan mencetak "LUNAS" untuk DP.
        $this->assertNull($reg->payment_verified_at);
        $this->assertNotNull($reg->dp_deadline);
    }

    public function test_pelunasan_menutup_tagihan_dan_menghapus_tenggat()
    {
        $reg = $this->pendaftaran();
        $this->terima($reg, 150000);
        $this->terima($reg, 350000, RegistrationPayment::KIND_PELUNASAN);

        $reg->refresh();

        $this->assertSame('paid', $reg->payment_status);
        $this->assertSame(0.0, $reg->sisaTagihan());
        $this->assertNotNull($reg->payment_verified_at);
        $this->assertNull($reg->dp_deadline);
    }

    /** `total_fee` adalah snapshot harga saat booking — DP tidak boleh mengubahnya. */
    public function test_dp_tidak_mengubah_total_fee_snapshot()
    {
        $reg = $this->pendaftaran();
        $this->terima($reg, 150000);

        $this->assertSame(500000.0, (float) $reg->fresh()->total_fee);
    }

    /**
     * Menolak bukti pelunasan tidak boleh menghapus DP yang sudah diterima.
     *
     * Penjumlahan ulang dari baris verified membuat ini otomatis benar; yang
     * dijaga tes ini adalah bahwa tidak ada jalur yang menimpa amount_paid
     * secara inkremental.
     */
    public function test_menolak_bukti_pelunasan_tidak_menghapus_dp_yang_sudah_diterima()
    {
        $reg = $this->pendaftaran();
        $this->terima($reg, 150000);

        $pelunasan = $reg->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_PELUNASAN,
            'amount' => 350000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        app(PaymentRecorder::class)->tolak($pelunasan, $this->user, 'Nominal tidak sesuai mutasi');

        $reg->refresh();

        $this->assertSame('dp_paid', $reg->payment_status);
        $this->assertSame(150000.0, (float) $reg->amount_paid);
        $this->assertSame('rejected', $pelunasan->fresh()->status);
        $this->assertSame('Nominal tidak sesuai mutasi', $pelunasan->fresh()->rejection_reason);
    }

    /** Nominal yang dicatat adalah yang dibaca panitia, bukan klaim peserta. */
    public function test_nominal_diterima_panitia_menimpa_nominal_klaim_peserta()
    {
        $reg = $this->pendaftaran();

        $bayar = $reg->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_DP,
            'amount' => 500000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        app(PaymentRecorder::class)->terima($bayar, $this->user, 150000);

        $reg->refresh();

        $this->assertSame(150000.0, (float) $bayar->fresh()->amount);
        $this->assertSame(150000.0, (float) $reg->amount_paid);
        $this->assertSame('dp_paid', $reg->payment_status);
    }

    public function test_tenggat_dihitung_sekali_dari_dp_hari_pertama_diterima()
    {
        $reg = $this->pendaftaran();
        $this->terima($reg, 100000);

        $tenggat = $reg->fresh()->dp_deadline;
        $this->assertNotNull($tenggat);
        $this->assertSame(now()->addDays(14)->toDateString(), $tenggat->toDateString());

        // Pembayaran berikutnya yang masih kurang tidak memperpanjang tenggat.
        $this->travel(3)->days();
        $this->terima($reg->fresh(), 100000);

        $this->assertSame(
            $tenggat->toDateString(),
            $reg->fresh()->dp_deadline->toDateString()
        );
    }

    public function test_pendaftaran_gratis_tidak_disentuh_payment_recorder()
    {
        $reg = $this->pendaftaran(['payment_status' => 'free', 'total_fee' => 0]);

        app(PaymentRecorder::class)->syncStatus($reg->fresh());

        $this->assertSame('free', $reg->fresh()->payment_status);
    }

    // ── Gerbang portal: DP membuka isian, bukan finalisasi ───────────────

    public function test_dp_membuka_pengisian_tapi_tidak_membuka_finalisasi()
    {
        $reg = $this->pendaftaran();
        $this->terima($reg, 150000);
        $reg->refresh();

        $this->assertTrue($reg->sudahBolehMengisi());
        $this->assertFalse($reg->bolehFinalisasi());
        $this->assertTrue($reg->formulirTerbuka());
    }

    public function test_bukti_dp_yang_belum_diverifikasi_tidak_membuka_apa_pun()
    {
        $reg = $this->pendaftaran();

        $reg->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_DP,
            'amount' => 150000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        $reg->refresh();

        $this->assertSame(0.0, (float) $reg->amount_paid);
        $this->assertFalse($reg->sudahBolehMengisi());
        $this->assertFalse($reg->formulirTerbuka());
    }

    public function test_portal_menampilkan_pilihan_dp_untuk_tingkat_berdp()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->assertSee('Bayar DP dulu')
            ->assertSee('150.000')
            ->assertSee('350.000');
    }

    public function test_portal_tidak_menawarkan_dp_untuk_tingkat_tanpa_dp()
    {
        $this->tingkat->update(['dp_amount' => null, 'dp_percent' => null, 'dp_days' => null]);
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->assertDontSee('Bayar DP dulu');
    }

    /**
     * Pilihan "bayar DP" yang dikirim ke server untuk tingkat tanpa DP ditolak
     * — gerbangnya dihitung ulang dari model, bukan dipercaya dari UI.
     */
    public function test_kiriman_dp_ditolak_bila_tingkat_tidak_menawarkan_dp()
    {
        $this->tingkat->update(['dp_amount' => null, 'dp_percent' => null, 'dp_days' => null]);
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'dp')
            ->set('paymentNominal', 150000)
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->call('submitPaymentProof')
            ->assertHasNoErrors();

        $this->assertSame(0, $reg->fresh()->registrationPayments()->count());
    }

    public function test_portal_menolak_nominal_di_atas_sisa_tagihan()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'dp')
            ->set('paymentNominal', 600000)
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->call('submitPaymentProof');

        $this->assertSame(0, $reg->fresh()->registrationPayments()->count());
    }

    /**
     * Nominal TIDAK boleh terisi otomatis dari pilihan DP/penuh.
     *
     * Peserta harus mengetik angka yang benar-benar ia transfer. Nominal yang
     * diisikan otomatis membuat bukti transfer yang salah ketik tetap lolos
     * validasi, dan panitia baru tahu belakangan bahwa klaimnya tidak cocok
     * dengan mutasi rekening.
     */
    public function test_portal_menolak_bukti_tanpa_nominal()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'penuh')
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->call('submitPaymentProof')
            ->assertHasErrors(['paymentNominal' => 'required']);

        $this->assertSame(0, $reg->fresh()->registrationPayments()->count());
    }

    public function test_portal_menolak_nominal_nol()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'penuh')
            ->set('paymentNominal', 0)
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->call('submitPaymentProof')
            ->assertHasErrors(['paymentNominal']);

        $this->assertSame(0, $reg->fresh()->registrationPayments()->count());
    }

    /** Setelah terkirim, kolom nominal dikosongkan lagi untuk kiriman berikutnya. */
    public function test_nominal_dikosongkan_setelah_bukti_terkirim()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'penuh')
            ->set('paymentNominal', 150000)
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->call('submitPaymentProof')
            ->assertSet('paymentNominal', null);
    }

    /**
     * Nominal yang sudah diketik TIDAK boleh hilang saat bukti diunggah.
     *
     * Regresi: kolom nominal dulu diikat ke state Alpine lewat
     * `@entangle('paymentNominal').defer`. Mengunggah bukti memicu request
     * Livewire tersendiri yang merender ulang blok pembayaran, dan karena
     * `.defer` belum mengirim nilainya, kolomnya kembali kosong — peserta
     * melihat "Nominal yang ditransfer wajib diisi" padahal sudah mengisinya.
     * Sekarang kolomnya `wire:model.blur`, jadi nilainya ada di server lebih
     * dulu. Tes ini mengunci urutan itu: isi nominal → unggah → kirim.
     */
    public function test_nominal_tetap_ada_setelah_bukti_diunggah()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'penuh')
            ->set('paymentNominal', 150000)
            // Unggah bukti = request Livewire tersendiri, persis seperti FilePond.
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->assertSet('paymentNominal', 150000)
            ->call('submitPaymentProof')
            ->assertHasNoErrors()
            ->assertSet('paymentNominal', null);

        $this->assertSame(1, $reg->fresh()->registrationPayments()->count());
    }

    /** Kartu pembayaran tidak lagi bergantung pada state Alpine. */
    public function test_kartu_pembayaran_diikat_langsung_ke_livewire()
    {
        $reg = $this->pendaftaran();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->assertDontSee("entangle('paymentNominal')", false)
            ->assertDontSee("entangle('modeBayar')", false)
            ->assertSee('wire:model.blur="paymentNominal"', false)
            ->assertSee('wire:model.live="modeBayar"', false);
    }

    /**
     * Bukti yang dikirim peserta membuat status `pending_verification`, dan
     * DP yang SUDAH diterima tidak hilang karena itu.
     */
    public function test_mengirim_bukti_pelunasan_tidak_menghapus_status_dp()
    {
        $reg = $this->pendaftaran();
        $this->terima($reg, 150000);
        $reg->refresh();

        Livewire::test(Portal::class, ['token' => $reg->magic_token])
            ->set('modeBayar', 'penuh')
            ->set('paymentNominal', 350000)
            ->set('paymentProof', \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'))
            ->call('submitPaymentProof');

        $reg->refresh();

        $this->assertSame(150000.0, (float) $reg->amount_paid);
        $this->assertSame('dp_paid', $reg->payment_status);
        $this->assertSame(1, $reg->registrationPayments()->where('status', 'pending')->count());
    }

    // ── Dokumen ──────────────────────────────────────────────────────────

    public function test_kuitansi_dp_terbit_dan_bertanda_dibayar_sebagian()
    {
        $reg = $this->pendaftaran(['status_berkas' => 'confirmed']);
        $this->terima($reg, 150000);
        $reg->refresh();

        $this->get(route('magic.link.invoice', $reg->magic_token))->assertOk();

        $html = view('eventner.participant.pdf_invoice', [
            'eventner' => $this->eventner,
            'registrations' => collect([$reg->fresh(['competitionCategory', 'registrationPayments', 'fieldValues'])]),
        ])->render();

        $this->assertStringContainsString('DIBAYAR SEBAGIAN', $html);
        $this->assertStringNotContainsString('LUNAS', $html);
        $this->assertStringContainsString('350.000', $html);
    }

    public function test_kuitansi_lunas_tetap_bertanda_lunas_setelah_pelunasan()
    {
        $reg = $this->pendaftaran(['status_berkas' => 'confirmed']);
        $this->terima($reg, 150000);
        $this->terima($reg, 350000, RegistrationPayment::KIND_PELUNASAN);
        $reg->refresh();

        $html = view('eventner.participant.pdf_invoice', [
            'eventner' => $this->eventner,
            'registrations' => collect([$reg->fresh(['competitionCategory', 'registrationPayments', 'fieldValues'])]),
        ])->render();

        $this->assertStringContainsString('LUNAS', $html);
        $this->assertStringNotContainsString('DIBAYAR SEBAGIAN', $html);
    }

    public function test_kuitansi_tetap_tertutup_selama_bukti_belum_diverifikasi()
    {
        $reg = $this->pendaftaran(['status_berkas' => 'confirmed']);

        $reg->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_DP,
            'amount' => 150000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        $this->get(route('magic.link.invoice', $reg->fresh()->magic_token))->assertForbidden();
    }

    // ── Uang masuk di layar panitia ──────────────────────────────────────

    public function test_dashboard_keuangan_menghitung_uang_masuk_bukan_tagihan()
    {
        $dp = $this->pendaftaran(['magic_token' => 'TOKEN-DP-0002']);
        $this->terima($dp, 150000);

        $lunas = $this->pendaftaran(['magic_token' => 'TOKEN-DP-0003']);
        $this->terima($lunas, 500000, RegistrationPayment::KIND_PELUNASAN);

        $this->pendaftaran(['magic_token' => 'TOKEN-DP-0004']);

        $komponen = Livewire::actingAs($this->user)->test(FinanceDashboard::class);

        // 150.000 (DP) + 500.000 (lunas) — bukan 500.000 + 500.000.
        $this->assertSame(650000.0, (float) $komponen->instance()->feeRevenue);
        // Piutang: 1.500.000 tagihan − 650.000 masuk.
        $this->assertSame(850000.0, (float) $komponen->instance()->piutang);
    }

    public function test_antrean_verifikasi_menghitung_bukti_bukan_pendaftaran()
    {
        $reg = $this->pendaftaran();

        $reg->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_DP,
            'amount' => 150000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        $komponen = Livewire::actingAs($this->user)->test(FinanceDashboard::class);

        $this->assertSame(1, $komponen->instance()->pendingVerificationCount);
        $this->assertSame($reg->id, $komponen->instance()->pendingPayments->first()->registration->id);
    }

    public function test_panitia_menerima_dp_dari_dashboard()
    {
        $reg = $this->pendaftaran();

        $bayar = $reg->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_DP,
            'amount' => 150000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->user)
            ->test(FinanceDashboard::class)
            ->call('openPaymentModal', $bayar->id)
            ->set('nominalDiterima', 150000)
            ->call('verifyPayment', $bayar->id);

        $this->assertSame('dp_paid', $reg->fresh()->payment_status);
        $this->assertSame(350000.0, $reg->fresh()->sisaTagihan());
    }

    /** Bukti milik event lain tidak boleh diverifikasi dari dashboard ini. */
    public function test_panitia_tidak_bisa_memverifikasi_bukti_event_lain()
    {
        $eventLain = Eventner::factory()->create(['status' => 'approved']);
        $regLain = Registration::factory()->for($eventLain, 'eventner')->create([
            'total_fee' => 500000,
            'payment_status' => 'pending_verification',
        ]);

        $bayar = $regLain->registrationPayments()->create([
            'kind' => RegistrationPayment::KIND_DP,
            'amount' => 150000,
            'status' => RegistrationPayment::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->user)
            ->test(FinanceDashboard::class)
            ->call('verifyPayment', $bayar->id);

        $this->assertSame('pending', $bayar->fresh()->status);
        $this->assertSame('pending_verification', $regLain->fresh()->payment_status);
    }

    // ── Pengaturan DP oleh panitia ───────────────────────────────────────

    public function test_panitia_bisa_menyalakan_dan_mematikan_dp()
    {
        $komponen = Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\CompetitionCategory\Show::class, ['category' => $this->tingkat->id]);

        $komponen->set('dpAktif', false)->call('simpanDp');
        $this->assertFalse($this->tingkat->fresh()->punyaDp());

        $komponen->set('dpAktif', true)
            ->set('dpMode', 'persen')
            ->set('dpPercent', '25')
            ->set('dpDays', '7')
            ->call('simpanDp');

        $segar = $this->tingkat->fresh();
        $this->assertSame(25, $segar->dp_percent);
        $this->assertNull($segar->dp_amount);
        $this->assertSame(7, $segar->dp_days);
    }

    public function test_panitia_tidak_bisa_menyimpan_dp_lebih_besar_dari_tagihan()
    {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\CompetitionCategory\Show::class, ['category' => $this->tingkat->id])
            ->set('dpAktif', true)
            ->set('dpMode', 'nominal')
            ->set('dpAmount', '900000')
            ->call('simpanDp');

        // Nilai lama tidak tertimpa.
        $this->assertSame(150000.0, (float) $this->tingkat->fresh()->dp_amount);
    }
}

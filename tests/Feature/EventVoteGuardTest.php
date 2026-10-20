<?php

namespace Tests\Feature;

use App\Livewire\Public\EventVote;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

/**
 * Penjagaan jalur tulis publik pada halaman vote.
 *
 * Halaman vote anonim dan publik, jadi dua hal harus dipegang server, bukan
 * formulirnya: berapa banyak vote yang boleh dibeli SEKALI transaksi (nominal
 * QRIS-nya ditentukan angka itu), dan berapa kali satu orang boleh menekan
 * "Bayar". Dulu `voteCount` hanya `min:1` tanpa batas atas, dan pembatas lajunya
 * hanya per IP — dua-duanya diuji di sini.
 *
 * Batas atasnya dibaca dari `eventners.vote_max_per_order` supaya
 * penyelenggara bisa mengaturnya; angkanya bukan konstanta di kode.
 */
class EventVoteGuardTest extends TestCase
{
    use RefreshDatabase;

    private int $nomorTrx = 0;

    /** Gateway palsu; setiap panggilan dapat nomor transaksi berbeda (unik di DB). */
    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => function () {
                $this->nomorTrx++;
                $trxId = 'AGP-GUARD-' . str_pad((string) $this->nomorTrx, 3, '0', STR_PAD_LEFT);

                return Http::response([
                    'success' => true,
                    'data' => [
                        'transaction_id' => $trxId,
                        'order_id' => 'ORD-' . $this->nomorTrx,
                        'amount' => 50000,
                        'transaction_status' => 'pending',
                        'qr_string' => '000201010212',
                        'qr_url' => 'https://api.autogopay.id/qr/' . $trxId . '.png',
                        'transaction_time' => now()->toIso8601String(),
                        'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                    ],
                ], 200);
            },
            '*/qris/cancel' => Http::response([
                'success' => true,
                'data' => ['transaction_status' => 'cancel'],
            ], 200),
        ]);
    }

    private function eventVote(int $maxPerOrder = 1000): array
    {
        $eventner = Eventner::factory()->voteAktif()->create([
            'status' => 'approved',
            'vote_price' => 1000,
            'vote_max_per_order' => $maxPerOrder,
        ]);

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'nama_sekolah' => 'SMP Berbatas',
        ]);

        return [$eventner, $reg];
    }

    /** Form vote yang sudah terisi, siap di-submit. */
    private function form(Eventner $eventner, Registration $reg, string $email, int $jumlah = 10): Testable
    {
        return Livewire::test(EventVote::class, ['slug' => $eventner->slug])
            ->set('selectedCategoryId', $reg->competition_category_id)
            ->set('selectedRegistrationId', $reg->id)
            ->set('voterName', 'Pembeli')
            ->set('voterEmail', $email)
            ->set('voteCount', $jumlah);
    }

    private function batasPerEmail(): int
    {
        return (new ReflectionClass(EventVote::class))->getConstant('MAX_ATTEMPTS_PER_EMAIL');
    }

    // ────────────────────────────────────────────────
    // Batas atas jumlah vote per transaksi
    // ────────────────────────────────────────────────

    /**
     * Angka di atas batas event ditolak SEBELUM QRIS dibuat — kalau tidak,
     * nominal QRIS ditentukan pengunjung, bukan penyelenggara.
     */
    public function test_vote_di_atas_batas_ditolak_dan_tidak_ada_qris_dibuat()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote(maxPerOrder: 50);

        $komponen = $this->form($eventner, $reg, 'pembeli@example.com', 51)->call('submitVote');

        $komponen->assertHasErrors('voteCount');
        Http::assertNothingSent();
        $this->assertSame(0, VoteTransaction::where('eventner_id', $eventner->id)->count());
        $this->assertNotSame('payment', $komponen->get('view'));
    }

    /** Pesan galatnya menyebut batas yang benar-benar berlaku, bukan kata "max". */
    public function test_pesan_galat_menyebut_batas_event()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote(maxPerOrder: 50);

        $this->form($eventner, $reg, 'pembeli@example.com', 51)
            ->call('submitVote')
            ->assertHasErrors(['voteCount' => 'Jumlah vote per transaksi maksimal 50.']);
    }

    /** Tepat di batas masih boleh — batasnya inklusif. */
    public function test_vote_tepat_di_batas_tetap_diterima()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote(maxPerOrder: 50);

        $komponen = $this->form($eventner, $reg, 'pembeli@example.com', 50)->call('submitVote');

        $komponen->assertHasNoErrors();
        $this->assertSame('payment', $komponen->get('view'));
        $this->assertSame(50000, (int) $komponen->get('paymentAmount'));
    }

    /**
     * Tombol "+" tidak ikut melewati batas: menaikkan angka sampai mustahil
     * dibayar hanya membuat pembeli menabrak galat yang sama.
     */
    public function test_tombol_tambah_berhenti_di_batas_event()
    {
        [$eventner, $reg] = $this->eventVote(maxPerOrder: 3);

        $komponen = $this->form($eventner, $reg, 'pembeli@example.com', 3);
        $komponen->call('incrementVote');

        $this->assertSame(3, (int) $komponen->get('voteCount'));
    }

    /** Tombol cepat di atas batas event tidak dirender — bukan pilihan, jebakan. */
    public function test_tombol_cepat_di_atas_batas_tidak_dirender()
    {
        [$eventner, $reg] = $this->eventVote(maxPerOrder: 100);

        $this->form($eventner, $reg, 'pembeli@example.com')
            ->assertSee('Maksimal 100 vote per transaksi.')
            ->assertSee('$wire.voteCount = 100')
            ->assertDontSee('$wire.voteCount = 500')
            ->assertDontSee('$wire.voteCount = 1000');
    }

    /**
     * Event yang tidak pernah menyentuh pengaturan tetap berperilaku seperti
     * dulu: default kolomnya 1000, angka terbesar yang sudah ditawarkan
     * formulir vote sebelum batas ini ada.
     */
    public function test_event_tanpa_pengaturan_memakai_batas_bawaan()
    {
        $this->fakeAutoGoPay();

        // Tanpa `vote_max_per_order` eksplisit — nilai kolomnya yang dipakai.
        $eventner = Eventner::factory()->voteAktif()->create([
            'status' => 'approved',
            'vote_price' => 1000,
        ]);

        $reg = Registration::factory()->for($eventner, 'eventner')->create();

        $this->assertSame(1000, (int) $eventner->fresh()->vote_max_per_order);

        $komponen = $this->form($eventner, $reg, 'pembeli@example.com', 1000)->call('submitVote');

        $komponen->assertHasNoErrors();
        $this->assertSame('payment', $komponen->get('view'));
    }

    // ────────────────────────────────────────────────
    // Pembatasan laju: per IP DAN per email
    // ────────────────────────────────────────────────

    /**
     * Satu orang di satu jaringan tidak boleh menahan seluruh jaringan.
     *
     * Anggaran email (kecil) dan anggaran IP (longgar) sengaja berbeda: IP
     * dipakai bersama semua orang di sekolah/kantor/warnet yang sama, jadi
     * menyamakan keduanya berarti satu pembeli cerewet memblokir tetangganya.
     */
    public function test_email_habis_tidak_memblokir_email_lain_dari_ip_yang_sama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        // Habiskan anggaran email pertama.
        for ($i = 0; $i < $this->batasPerEmail(); $i++) {
            $this->form($eventner, $reg, 'cerewet@example.com')->call('submitVote');
        }

        $habis = $this->form($eventner, $reg, 'cerewet@example.com')->call('submitVote');
        $this->assertNull($habis->get('currentTransactionId'));

        // Tetangga di IP yang sama tetap bisa membayar.
        $tetangga = $this->form($eventner, $reg, 'tetangga@example.com')->call('submitVote');

        $this->assertNotNull($tetangga->get('currentTransactionId'));
        $this->assertSame('payment', $tetangga->get('view'));
    }

    /**
     * Kunci IP tetap punya anggaran sendiri — tanpa itu satu jaringan bisa
     * dibanjiri percobaan dengan email yang selalu baru.
     */
    public function test_anggaran_ip_habis_memblokir_email_baru()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $kunciIp = 'vote-submit:' . request()->ip();
        $batasIp = (new ReflectionClass(EventVote::class))->getConstant('MAX_ATTEMPTS_PER_IP');

        for ($i = 0; $i < $batasIp; $i++) {
            RateLimiter::hit($kunciIp, 60);
        }

        $komponen = $this->form($eventner, $reg, 'baru@example.com')->call('submitVote');

        Http::assertNothingSent();
        $this->assertNull($komponen->get('currentTransactionId'));
    }

    /** Huruf besar/kecil satu email tetap satu anggaran — bukan celah. */
    public function test_email_besar_kecil_memakai_anggaran_yang_sama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        for ($i = 0; $i < $this->batasPerEmail(); $i++) {
            $this->form($eventner, $reg, 'budi@email.com')->call('submitVote');
        }

        $komponen = $this->form($eventner, $reg, 'BUDI@Email.COM')->call('submitVote');

        $this->assertNull($komponen->get('currentTransactionId'));
    }

    // ────────────────────────────────────────────────
    // Satu QR hidup per email
    // ────────────────────────────────────────────────

    /**
     * Percobaan berulang untuk email yang sama tetap menyisakan SATU QRIS
     * PENDING — AutoGoPay bisa memblokir akun merchant kalau QR menumpuk.
     */
    public function test_qris_kedua_untuk_email_yang_sama_tidak_dibuat()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        for ($i = 0; $i < 3; $i++) {
            $this->form($eventner, $reg, 'pembeli@example.com')->call('submitVote');
        }

        $this->assertSame(1, VoteTransaction::where('eventner_id', $eventner->id)
            ->where('status', 'PENDING')
            ->where('voter_email', 'pembeli@example.com')
            ->count());

        Http::assertSentCount(1);
    }

    // ────────────────────────────────────────────────
    // API v1 — klien Flutter
    // ────────────────────────────────────────────────

    /** Jalur API memakai batas event yang sama, bukan batas pada kode. */
    public function test_api_menolak_vote_di_atas_batas_event()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote(maxPerOrder: 50);

        $this->postJson('/api/v1/vote/calculate', [
            'event_slug' => $eventner->slug,
            'registration_id' => $reg->id,
            'vote_count' => 51,
            'voter_name' => 'Pembeli',
            'voter_email' => 'pembeli@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Jumlah vote per transaksi maksimal 50.');

        Http::assertNothingSent();
    }

    /** Batas yang sama juga dikirim ke klien supaya bisa menahan sebelum menabrak 422. */
    public function test_api_mengirim_batas_vote_event()
    {
        [$eventner] = $this->eventVote(maxPerOrder: 250);

        $this->getJson('/api/v1/events/' . $eventner->slug)
            ->assertOk()
            ->assertJsonPath('data.vote_max_per_order', 250);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Temuan audit #15 dan #18 — uang vote dan tiket.
 *
 * #15: pendapatan per kontingen harus berasal dari nominal transaksi yang
 * benar-benar dibayar. votes_earned sudah termasuk multiplier booster, jadi
 * mengalikannya dengan harga per vote memberi angka yang lebih besar dari
 * uang yang masuk.
 *
 * #18: pembayaran yang masuk setelah QR dinyatakan kedaluwarsa harus tetap
 * dikreditkan. Dulu tidak ada jalur EXPIRED → PAID sama sekali.
 */
class VoteRevenueAndExpiryTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->voteAktif()->create([
            'status' => 'approved',
            'vote_price' => 1000,
        ]);

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'nama_sekolah' => 'SMP Revenue',
        ]);
    }

    private function transaksi(int $amount, int $votes, string $status, string $trxId): VoteTransaction
    {
        return VoteTransaction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'autogopay_transaction_id' => $trxId,
            'qr_url' => 'https://example.test/qr/' . $trxId,
            'amount' => $amount,
            'votes_earned' => $votes,
            'voter_name' => 'Pembeli',
            'status' => $status,
            'paid_at' => $status === 'PAID' ? now() : null,
        ]);
    }

    /**
     * #15 — pendapatan mengikuti nominal, bukan jumlah vote × harga.
     * Booster memberi vote ekstra tanpa tambahan uang, jadi mengalikan
     * vote dengan harga melebih-lebihkan pendapatan.
     */
    public function test_pendapatan_mengikuti_nominal_transaksi_bukan_jumlah_vote()
    {
        // 10 vote dibayar Rp 10.000, tapi booster menggandakan jadi 30 vote.
        // Kalau dikalikan harga, tampil Rp 30.000 — padahal uangnya 10.000.
        $this->transaksi(10000, 30, 'PAID', 'TRX-BOOSTER');

        $this->actingAs($this->eventner->user);

        \Livewire\Livewire::test(\App\Livewire\Eventner\VoteResults\Index::class)
            ->set('activeTab', $this->reg->competition_category_id)
            ->assertViewHas('results', function ($results) {
                $baris = $results->firstWhere('id', $this->reg->id);

                return (int) $baris->total_votes === 30
                    && (float) $baris->total_amount === 10000.0;
            });
    }

    /** Transaksi belum lunas tidak dihitung sebagai pendapatan. */
    public function test_transaksi_belum_lunas_tidak_dihitung()
    {
        $this->transaksi(10000, 10, 'PAID', 'TRX-LUNAS');
        $this->transaksi(50000, 50, 'PENDING', 'TRX-PENDING');
        $this->transaksi(70000, 70, 'EXPIRED', 'TRX-KEDALUWARSA');

        $this->actingAs($this->eventner->user);

        \Livewire\Livewire::test(\App\Livewire\Eventner\VoteResults\Index::class)
            ->set('activeTab', $this->reg->competition_category_id)
            ->assertViewHas('results', function ($results) {
                $baris = $results->firstWhere('id', $this->reg->id);

                return (int) $baris->total_votes === 10
                    && (float) $baris->total_amount === 10000.0;
            });
    }

    /**
     * #18 — transaksi EXPIRED bisa naik ke PAID. QRIS kedaluwarsa di sisi
     * gateway sementara pembeli tetap menyelesaikan pembayarannya; tanpa
     * jalur ini uangnya nyangkut selamanya.
     */
    public function test_vote_kedaluwarsa_bisa_diklaim_paid()
    {
        $tx = $this->transaksi(10000, 10, 'EXPIRED', 'TRX-EXP-VOTE');

        $this->assertTrue($tx->claimPaid());
        $this->assertSame('PAID', $tx->fresh()->status);
        $this->assertNotNull($tx->fresh()->paid_at);
    }

    /** Transaksi yang sudah PAID tidak bisa diklaim dua kali. */
    public function test_vote_paid_tidak_bisa_diklaim_ulang()
    {
        $tx = $this->transaksi(10000, 10, 'PAID', 'TRX-SUDAH-PAID');

        $this->assertFalse($tx->claimPaid());
    }

    /** Transaksi FAILED tidak boleh naik jadi PAID — itu pembayaran yang batal. */
    public function test_vote_gagal_tidak_bisa_diklaim_paid()
    {
        $tx = $this->transaksi(10000, 10, 'FAILED', 'TRX-GAGAL');

        $this->assertFalse($tx->claimPaid());
        $this->assertSame('FAILED', $tx->fresh()->status);
    }

    /** Tiket juga: EXPIRED → PAID, sekaligus terbit QR masuknya. */
    public function test_tiket_kedaluwarsa_bisa_diklaim_paid_dengan_qr()
    {
        $ticket = Ticket::create([
            'eventner_id' => $this->eventner->id,
            'order_code' => 'TKT-EXPIRED01',
            'buyer_name' => 'Pembeli',
            'buyer_email' => 'pembeli@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'TRX-EXP-TIKET',
            'status' => 'EXPIRED',
        ]);

        $this->assertTrue($ticket->claimPaid());

        $ticket->refresh();
        $this->assertSame('PAID', $ticket->status);
        $this->assertNotNull($ticket->paid_at);
        $this->assertNotNull($ticket->qr_code_path);
    }
}

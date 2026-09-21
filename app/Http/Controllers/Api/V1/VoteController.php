<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteBooster;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
use App\Services\PendingPaymentGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VoteController extends Controller
{
    public function calculate(Request $request)
    {
        $request->validate([
            'event_slug' => 'required|string',
            'registration_id' => 'required|integer',
            'vote_count' => 'required|integer|min:1',
            'voter_name' => 'required|string|max:255',
            'voter_email' => 'required|email|max:255',
            'comment' => 'nullable|string|max:500',
        ]);

        if (RateLimiter::tooManyAttempts('api-vote:' . $request->ip(), 5)) {
            return response()->json(['message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.'], 429);
        }
        RateLimiter::hit('api-vote:' . $request->ip(), 60);

        $event = Eventner::approved()->where('slug', $request->event_slug)->firstOrFail();

        // Pastikan registration_id milik event ini, bukan registrasi tenant lain.
        $registration = Registration::where('eventner_id', $event->id)
            ->find($request->registration_id);

        if (!$registration) {
            return response()->json(['message' => 'Peserta tidak ditemukan pada event ini.'], 422);
        }

        if (!$event->vote_active) {
            return response()->json(['message' => 'Fitur Vote sudah ditutup.'], 400);
        }

        if ($event->vote_end && now()->gt($event->vote_end)) {
            return response()->json(['message' => 'Masa voting sudah berakhir.'], 400);
        }

        $basePrice = $event->vote_price ?? 1000;
        $multiplier = 1;

        $activeBooster = VoteBooster::where('eventner_id', $event->id)
            ->active()
            ->orderByDesc('vote_multiplier')
            ->first();

        if ($activeBooster) {
            $multiplier = $activeBooster->vote_multiplier;
        }

        $totalVotes = $request->vote_count * $multiplier;
        $amount = $request->vote_count * $basePrice;

        // Sudah punya QR hidup untuk email ini? Balas QR yang sama.
        //
        // Sengaja HTTP 200 dengan amplop `data` yang sama plus penanda
        // `reused`, bukan 4xx: yang diminta klien adalah QR yang bisa dibayar,
        // dan itu yang dikirim. Status galat akan memaksa rilis aplikasi
        // Flutter lebih dulu hanya untuk menampilkan QR yang sudah ada.
        $lama = PendingPaymentGuard::find(VoteTransaction::class, $event->id, $request->voter_email);

        if ($lama) {
            return response()->json([
                'data' => [
                    'transaction_id' => $lama->id,
                    'autogopay_transaction_id' => $lama->autogopay_transaction_id,
                    'qr_url' => $lama->qr_url,
                    // qr_string sengaja tidak dikirim: nilainya tidak disimpan
                    // di DB, dan mengarang ulang isinya akan salah.
                    'qr_string' => null,
                    'expiry_time' => PendingPaymentGuard::expiryForDisplay($lama)->toIso8601String(),
                    'amount' => (int) $lama->amount,
                    'votes_earned' => (int) $lama->votes_earned,
                    'vote_multiplier' => $multiplier,
                    'reused' => true,
                ],
            ]);
        }

        try {
            $service = new AutoGoPay();
            $result = $service->generateQris($amount);

            if (!($result['success'] ?? false)) {
                return response()->json(['message' => 'Gagal membuat QR pembayaran.'], 500);
            }

            $data = $result['data'];

            $transaction = VoteTransaction::create([
                'eventner_id' => $event->id,
                'registration_id' => $request->registration_id,
                'autogopay_transaction_id' => $data['transaction_id'],
                'qr_url' => $data['qr_url'],
                'amount' => $amount,
                'votes_earned' => $totalVotes,
                'voter_name' => $request->voter_name,
                'voter_email' => PendingPaymentGuard::normalizeEmail($request->voter_email),
                'comment' => strip_tags($request->comment ?? ''),
                'status' => 'PENDING',
                'expires_at' => PendingPaymentGuard::deadlineFrom($data['expiry_time'] ?? null),
                'payable_until' => PendingPaymentGuard::payableUntil($data['expiry_time'] ?? null),
            ]);

            return response()->json([
                'data' => [
                    'transaction_id' => $transaction->id,
                    'autogopay_transaction_id' => $data['transaction_id'],
                    'qr_url' => $data['qr_url'],
                    'qr_string' => $data['qr_string'] ?? null,
                    'expiry_time' => $data['expiry_time'],
                    'amount' => $amount,
                    'votes_earned' => $totalVotes,
                    'vote_multiplier' => $multiplier,
                    'reused' => false,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Vote QRIS generation failed', [
                'eventner_id' => $event->id,
                'registration_id' => $request->registration_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal memproses vote: ' . $e->getMessage()], 500);
        }
    }

    public function status(Request $request, $transactionId)
    {
        // Scope by event_slug — cegah baca status transaksi event lain.
        $event = Eventner::approved()->where('slug', $request->query('event_slug', ''))->firstOrFail();

        $transaction = VoteTransaction::where('eventner_id', $event->id)
            ->findOrFail($transactionId);

        return response()->json([
            'data' => [
                'status' => $transaction->status,
                'paid_at' => $transaction->paid_at,
                'votes_earned' => $transaction->votes_earned,
            ],
        ]);
    }

    public function comments(Request $request)
    {
        $request->validate(['event_slug' => 'required|string']);

        $event = Eventner::approved()->where('slug', $request->event_slug)->firstOrFail();

        $comments = VoteTransaction::with('registration:id,nama_sekolah,label_pasukan')
            ->where('eventner_id', $event->id)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get()
            ->map(fn ($tx) => [
                'nama_sekolah' => $tx->registration?->nama_sekolah,
                'comment' => $tx->comment,
                'time' => $tx->paid_at?->diffForHumans(),
            ]);

        return response()->json(['data' => $comments]);
    }
}

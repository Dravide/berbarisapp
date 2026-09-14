<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RegistrationResource;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class QrController extends Controller
{
    public function scan(Request $request)
    {
        // Throttle brute-force QR token (8 char uppercase, ruang kunci kecil).
        if (RateLimiter::tooManyAttempts('api-qr-scan:' . $request->ip(), 10)) {
            return response()->json(['message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'], 429);
        }
        RateLimiter::hit('api-qr-scan:' . $request->ip(), 60);

        $request->validate([
            'qr_token' => 'required|string|size:8',
        ]);

        $registration = Registration::where('qr_token', $request->qr_token)
            ->with([
                'eventner',
                'competitionCategory',
                'participants',
                'paymentBankAccount',
                'voteTransactions' => function ($q) {
                    $q->where('status', 'PAID');
                },
            ])
            ->first();

        if (!$registration) {
            return response()->json(['message' => 'QR tidak valid.'], 404);
        }

        // Scan = awal sesi aplikasi. Dulu createToken() dipanggil setiap
        // scan tanpa mencabut yang lama, jadi tabel personal_access_tokens
        // tumbuh satu baris per scan dan SEMUA token lama tetap sah
        // selamanya — satu QR yang pernah dipindai belasan kali meninggalkan
        // belasan kunci abadi. Sekarang token lama dicabut dulu, dan yang
        // baru punya masa berlaku.
        $registration->tokens()->delete();

        $token = $registration->createToken('mobile-app', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'data' => new RegistrationResource($registration),
        ]);
    }
}

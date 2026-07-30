<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RegistrationResource;
use App\Models\Registration;
use Illuminate\Http\Request;

class QrController extends Controller
{
    public function scan(Request $request)
    {
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

        // Buat atau ambil token
        $token = $registration->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'data' => new RegistrationResource($registration),
        ]);
    }
}

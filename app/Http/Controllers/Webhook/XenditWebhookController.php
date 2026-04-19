<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\VoteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $callbackToken = $request->header('x-callback-token');

        if ($callbackToken !== config('services.xendit.webhook_token')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid callback token'
            ], 401);
        }

        $payload = $request->all();
        
        Log::info('Xendit Webhook received:', $payload);

        $invoiceId = $payload['id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$invoiceId || !$status) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        $transaction = VoteTransaction::where('xendit_invoice_id', $invoiceId)->first();

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        if ($status === 'PAID') {
            $transaction->update([
                'status' => 'PAID',
                'paid_at' => now(),
            ]);
        } elseif ($status === 'EXPIRED') {
            $transaction->update(['status' => 'EXPIRED']);
        }

        return response()->json(['status' => 'success']);
    }
}

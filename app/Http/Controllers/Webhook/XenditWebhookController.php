<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use chillerlan\QRCode\QRCode;

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
        $externalId = $payload['external_id'] ?? '';

        if (!$invoiceId || !$status) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        // Handle Vote Transaction
        $transaction = VoteTransaction::where('xendit_invoice_id', $invoiceId)->first();
        if ($transaction) {
            if ($status === 'PAID') {
                $transaction->update(['status' => 'PAID', 'paid_at' => now()]);
            } elseif ($status === 'EXPIRED') {
                $transaction->update(['status' => 'EXPIRED']);
            }
            return response()->json(['status' => 'success']);
        }

        // Handle Ticket Transaction
        $ticket = Ticket::where('xendit_invoice_id', $invoiceId)->first();
        if ($ticket) {
            if ($status === 'PAID') {
                // Generate QR code
                $qrData = $ticket->order_code;
                $qrImage = (new QRCode)->render($qrData);
                $qrPath = 'tickets/' . $ticket->order_code . '.png';
                Storage::put('public/' . $qrPath, base64_decode(explode(',', $qrImage, 2)[1] ?? ''));

                $ticket->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                    'qr_code_path' => $qrPath,
                ]);
            } elseif ($status === 'EXPIRED') {
                $ticket->update(['status' => 'EXPIRED']);
            }
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
    }
}

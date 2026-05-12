<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AutoGoPayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $service = new AutoGoPay();
        $payload = $request->getContent();
        $signature = $request->header('X-Signature', '');

        // 1. Verifikasi signature (WAJIB)
        if (!$service->verifySignature($payload, $signature)) {
            Log::warning('AutoGoPay webhook: Invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();

        Log::info('AutoGoPay webhook received', $data);

        // 2. Handle verification challenge
        if (($data['event'] ?? '') === 'verification.challenge') {
            return response()->json(['success' => true]);
        }

        // 3. Handle payment settlement
        if (($data['event'] ?? '') === 'transaction.received') {
            $transaction = $data['transaction'] ?? [];
            $transactionId = $transaction['id'] ?? null;
            $status = $transaction['status'] ?? null;

            if (!$transactionId) {
                return response()->json(['error' => 'Missing transaction id'], 400);
            }

            if ($status === 'settlement') {
                $this->handleSettlement($transactionId);
            } elseif ($status === 'expire') {
                $this->handleExpired($transactionId);
            }
        }

        return response()->json(['success' => true]);
    }

    private function handleSettlement(string $transactionId): void
    {
        // Cek Vote Transaction (idempotent)
        $vote = VoteTransaction::where('autogopay_transaction_id', $transactionId)->first();
        if ($vote && $vote->status !== 'PAID') {
            $vote->update([
                'status' => 'PAID',
                'paid_at' => now(),
            ]);
            Log::info('Vote payment confirmed via webhook', ['transaction_id' => $transactionId, 'vote_id' => $vote->id]);

            return;
        }

        // Cek Ticket (idempotent)
        $ticket = Ticket::where('autogopay_transaction_id', $transactionId)->first();
        if ($ticket && $ticket->status !== 'PAID') {
            // Generate QR tiket masuk
            $qrData = $ticket->order_code;
            $qrImage = (new QRCode)->render($qrData);
            $qrPath = 'tickets/' . $ticket->order_code . '.png';
            Storage::put('public/' . $qrPath, base64_decode(explode(',', $qrImage, 2)[1] ?? ''));

            $ticket->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'qr_code_path' => $qrPath,
            ]);
            Log::info('Ticket payment confirmed via webhook', ['transaction_id' => $transactionId, 'ticket_id' => $ticket->id]);
        }
    }

    private function handleExpired(string $transactionId): void
    {
        VoteTransaction::where('autogopay_transaction_id', $transactionId)
            ->where('status', 'PENDING')
            ->update(['status' => 'EXPIRED']);

        Ticket::where('autogopay_transaction_id', $transactionId)
            ->where('status', 'PENDING')
            ->update(['status' => 'EXPIRED']);
    }
}

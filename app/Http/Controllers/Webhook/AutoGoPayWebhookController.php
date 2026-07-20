<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use App\Models\Eventner;
use App\Services\AutoGoPay;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
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
                $this->handleSettlement($transactionId, $transaction);
                $this->handleEventnerSettlement($transactionId);
            } elseif ($status === 'expire') {
                $this->handleExpired($transactionId);
                $this->handleEventnerExpired($transactionId);
            }
        }

        return response()->json(['success' => true]);
    }

    private function handleSettlement(string $transactionId, array $transactionData = []): void
    {
        // Cek Vote Transaction (idempotent)
        $vote = VoteTransaction::where('autogopay_transaction_id', $transactionId)->first();
        if ($vote && $vote->status !== 'PAID') {
            // Verifikasi amount: pastikan nominal dibayar sesuai database
            $paidAmount = $transactionData['amount'] ?? null;
            if ($paidAmount !== null && (int)$paidAmount !== (int)$vote->amount) {
                Log::warning('Vote webhook: amount mismatch', [
                    'transaction_id' => $transactionId,
                    'vote_id' => $vote->id,
                    'expected' => (int)$vote->amount,
                    'paid' => (int)$paidAmount,
                ]);
                return;
            }

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
            // Verifikasi amount: pastikan nominal dibayar sesuai database
            $paidAmount = $transactionData['amount'] ?? null;
            if ($paidAmount !== null && (int)$paidAmount !== (int)$ticket->total_amount) {
                Log::warning('Ticket webhook: amount mismatch', [
                    'transaction_id' => $transactionId,
                    'ticket_id' => $ticket->id,
                    'expected' => (int)$ticket->total_amount,
                    'paid' => (int)$paidAmount,
                ]);
                return;
            }

            // Generate QR tiket masuk (binary PNG)
            $options = new QROptions;
            $options->outputInterface = QRGdImagePNG::class;
            $options->outputBase64 = false;
            $options->scale = 6;

            $qrPath = 'tickets/' . $ticket->order_code . '.png';
            $qrImage = (new QRCode($options))->render($ticket->order_code);
            Storage::disk('public')->put($qrPath, $qrImage);

            $ticket->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'qr_code_path' => $qrPath,
            ]);
            Log::info('Ticket payment confirmed via webhook', ['transaction_id' => $transactionId, 'ticket_id' => $ticket->id]);

            // Kirim email notifikasi ke buyer
            try {
                app(\App\Services\MailyService::class)->sendTicketConfirmation($ticket->fresh());
            } catch (\Exception $e) {
                Log::warning('Maily.id: sendTicketConfirmation failed (webhook)', [
                    'error' => $e->getMessage(),
                    'order' => $ticket->order_code,
                ]);
            }
        }
    }

    private function handleEventnerSettlement(string $transactionId): void
    {
        $eventner = Eventner::where('autogopay_transaction_id', $transactionId)->first();
        if (!$eventner || $eventner->status === 'approved') {
            return;
        }

        $eventner->update([
            'status' => 'approved',
            'approved_at' => now(),
            'registration_paid_at' => now(),
        ]);

        $eventner->user->update(['is_active' => true]);

        Log::info('Eventner registration auto-approved via webhook', [
            'transaction_id' => $transactionId,
            'eventner_id' => $eventner->id,
        ]);

        // Kirim email notifikasi
        try {
            app(\App\Services\MailyService::class)->sendEventnerApproved(
                $eventner->user->email,
                $eventner->user->name,
                $eventner->nama_event
            );
        } catch (\Exception $e) {
            Log::warning('Maily.id: sendEventnerApproved failed (webhook)', [
                'error' => $e->getMessage(),
                'eventner_id' => $eventner->id,
            ]);
        }
    }

    private function handleEventnerExpired(string $transactionId): void
    {
        Eventner::where('autogopay_transaction_id', $transactionId)
            ->where('status', 'pending')
            ->update(['status' => 'rejected', 'rejection_reason' => 'Pembayaran kadaluarsa']);
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

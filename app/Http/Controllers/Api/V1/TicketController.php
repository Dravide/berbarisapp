<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\Ticket;
use App\Services\AutoGoPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TicketController extends Controller
{
    public function purchase(Request $request)
    {
        $request->validate([
            'event_slug' => 'required|string',
            'buyer_name' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        if (RateLimiter::tooManyAttempts('api-ticket:' . $request->ip(), 5)) {
            return response()->json(['message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.'], 429);
        }
        RateLimiter::hit('api-ticket:' . $request->ip(), 60);

        $event = Eventner::where('slug', $request->event_slug)->firstOrFail();

        if (!$event->ticket_active) {
            return response()->json(['message' => 'Fitur tiket sedang tidak aktif.'], 400);
        }
        if ($event->ticket_end && now()->gt($event->ticket_end)) {
            return response()->json(['message' => 'Masa pembelian tiket sudah berakhir.'], 400);
        }
        if ($event->ticket_start && now()->lt($event->ticket_start)) {
            return response()->json(['message' => 'Pembelian tiket belum dibuka.'], 400);
        }

        $price = $event->ticket_price ?? 0;
        $totalAmount = $price * $request->quantity;

        // Cek max per order
        $maxPerOrder = $event->ticket_max_per_order ?? 10;
        if ($request->quantity > $maxPerOrder) {
            return response()->json(['message' => "Maksimal {$maxPerOrder} tiket per pemesanan."], 400);
        }

        // Generate order code
        $orderCode = 'TCK-' . strtoupper(\Illuminate\Support\Str::random(10));

        if ($totalAmount <= 0) {
            // Tiket gratis — langsung aktif
            $ticket = Ticket::create([
                'eventner_id' => $event->id,
                'order_code' => $orderCode,
                'buyer_name' => $request->buyer_name,
                'buyer_email' => $request->buyer_email,
                'buyer_phone' => $request->buyer_phone,
                'quantity' => $request->quantity,
                'price_per_ticket' => $price,
                'total_amount' => 0,
                'status' => 'ACTIVE',
                'paid_at' => now(),
            ]);

            return response()->json([
                'data' => [
                    'order_code' => $orderCode,
                    'quantity' => $request->quantity,
                    'total_amount' => 0,
                    'status' => 'ACTIVE',
                    'ticket_id' => $ticket->id,
                ],
            ]);
        }

        // Berbayar — generate QRIS
        try {
            $service = new AutoGoPay();
            $result = $service->generateQris($totalAmount);

            if (!($result['success'] ?? false)) {
                return response()->json(['message' => 'Gagal membuat QR pembayaran.'], 500);
            }

            $data = $result['data'];

            $ticket = Ticket::create([
                'eventner_id' => $event->id,
                'order_code' => $orderCode,
                'buyer_name' => $request->buyer_name,
                'buyer_email' => $request->buyer_email,
                'buyer_phone' => $request->buyer_phone,
                'quantity' => $request->quantity,
                'price_per_ticket' => $price,
                'total_amount' => $totalAmount,
                'autogopay_transaction_id' => $data['transaction_id'],
                'qr_url' => $data['qr_url'],
                'status' => 'PENDING',
            ]);

            return response()->json([
                'data' => [
                    'order_code' => $orderCode,
                    'quantity' => $request->quantity,
                    'total_amount' => $totalAmount,
                    'qr_url' => $data['qr_url'],
                    'qr_string' => $data['qr_string'] ?? null,
                    'expiry_time' => $data['expiry_time'],
                    'autogopay_transaction_id' => $data['transaction_id'],
                    'ticket_id' => $ticket->id,
                    'status' => 'PENDING',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Ticket purchase QRIS failed', [
                'eventner_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Gagal memproses pembayaran: ' . $e->getMessage()], 500);
        }
    }

    public function status($orderCode)
    {
        $ticket = Ticket::where('order_code', $orderCode)->firstOrFail();

        return response()->json([
            'data' => [
                'order_code' => $ticket->order_code,
                'status' => $ticket->status,
                'total_amount' => $ticket->total_amount,
                'quantity' => $ticket->quantity,
                'paid_at' => $ticket->paid_at,
                'buyer_name' => $ticket->buyer_name,
            ],
        ]);
    }
}

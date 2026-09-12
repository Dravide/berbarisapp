<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Exceptions\TicketQuotaExceededException;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use App\Models\Ticket;
use App\Services\AutoGoPay;
use App\Services\TicketQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

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
            'venue_id' => 'nullable|integer',
        ]);

        if (RateLimiter::tooManyAttempts('api-ticket:' . $request->ip(), 5)) {
            return response()->json(['message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.'], 429);
        }
        RateLimiter::hit('api-ticket:' . $request->ip(), 60);

        $event = Eventner::approved()->where('slug', $request->event_slug)->firstOrFail();

        if (!$event->ticket_active) {
            return response()->json(['message' => 'Fitur tiket sedang tidak aktif.'], 400);
        }
        if ($event->ticket_end && now()->gt($event->ticket_end)) {
            return response()->json(['message' => 'Masa pembelian tiket sudah berakhir.'], 400);
        }
        if ($event->ticket_start && now()->lt($event->ticket_start)) {
            return response()->json(['message' => 'Pembelian tiket belum dibuka.'], 400);
        }

        // Tempat: wajib bila event menjual tiket per tempat. Klien lama yang
        // belum mengirim venue_id ditolak dengan pesan jelas — bukan diam-diam
        // dimasukkan ke tempat pertama, karena tiketnya jadi salah gerbang.
        $perVenue = $event->sellsTicketPerVenue();

        if ($perVenue) {
            if (!$request->filled('venue_id')) {
                return response()->json([
                    'message' => 'Event ini menjual tiket per tempat. Pilih tempat pelaksanaan terlebih dahulu.',
                    'venues' => $event->ticketVenues()->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                        'alamat' => $v->alamat,
                        'ticket_price' => $v->effectiveTicketPrice((int) $event->ticket_price),
                        'remaining' => $v->remainingTicketSlots(),
                    ])->values(),
                ], 422);
            }

            $validated = validator($request->only('venue_id'), [
                'venue_id' => [
                    'required',
                    Rule::exists('eventner_venues', 'id')
                        ->where('eventner_id', $event->id)
                        ->where('is_active', true),
                ],
            ], ['venue_id.required' => 'Tempat pelaksanaan wajib dipilih.']);

            if ($validated->fails()) {
                return response()->json(['message' => $validated->errors()->first()], 422);
            }
        }

        $venue = $request->filled('venue_id')
            ? $event->ticketVenues()->firstWhere('id', (int) $request->venue_id)
            : $event->ticketVenues()->first();

        $price = $venue ? $venue->effectiveTicketPrice((int) $event->ticket_price) : ($event->ticket_price ?? 0);
        $totalAmount = $price * $request->quantity;

        // Cek max per order — juga dibatasi sisa kuota tempat.
        $maxPerOrder = $event->ticket_max_per_order ?? 10;
        if ($venue) {
            $remaining = $venue->remainingTicketSlots();
            if ($remaining !== null) {
                if ($remaining <= 0) {
                    return response()->json(['message' => 'Tiket untuk ' . $venue->name . ' sudah habis.'], 400);
                }
                $maxPerOrder = min($maxPerOrder, $remaining);
            }
        }
        if ($request->quantity > $maxPerOrder) {
            return response()->json(['message' => "Maksimal {$maxPerOrder} tiket per pemesanan."], 400);
        }

        // Generate order code
        $orderCode = 'TCK-' . strtoupper(\Illuminate\Support\Str::random(10));

        // Tiket dibuat di dalam transaksi berkunci supaya dua pemesan terakhir
        // tidak lolos bersamaan (lihat TicketQuota). $extra diisi tepat sebelum
        // dipakai (status ACTIVE untuk gratis, PENDING + QRIS untuk berbayar).
        $extra = [];
        $create = function (?EventnerVenue $locked) use ($event, $request, $orderCode, $price, $totalAmount, &$extra) {
            return Ticket::create(array_merge([
                'eventner_id' => $event->id,
                'venue_id' => $locked?->id,
                'order_code' => $orderCode,
                'buyer_name' => $request->buyer_name,
                'buyer_email' => $request->buyer_email,
                'buyer_phone' => $request->buyer_phone,
                'quantity' => $request->quantity,
                'price_per_ticket' => $price,
                'total_amount' => $totalAmount,
            ], $extra));
        };

        if ($totalAmount <= 0) {
            // Tiket gratis — langsung aktif. Tetap lewat penjaga kuota: status
            // ACTIVE juga menahan slot tempat.
            $extra = ['status' => 'ACTIVE', 'paid_at' => now()];

            try {
                $ticket = $venue
                    ? TicketQuota::reserve($venue, (int) $request->quantity, $create)
                    : $create(null);
            } catch (TicketQuotaExceededException $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

            return response()->json([
                'data' => [
                    'order_code' => $orderCode,
                    'quantity' => $request->quantity,
                    'total_amount' => 0,
                    'status' => 'ACTIVE',
                    'venue_id' => $ticket->venue_id,
                    'venue_name' => $venue?->name,
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

            $extra = [
                'autogopay_transaction_id' => $data['transaction_id'],
                'qr_url' => $data['qr_url'],
                'status' => 'PENDING',
            ];

            $extra = [
                'autogopay_transaction_id' => $data['transaction_id'],
                'qr_url' => $data['qr_url'],
                'status' => 'PENDING',
            ];

            try {
                $ticket = $venue
                    ? TicketQuota::reserve($venue, (int) $request->quantity, $create)
                    : $create(null);
            } catch (TicketQuotaExceededException $e) {
                // Kuota habis setelah QRIS dibuat — QR-nya tidak dipakai.
                return response()->json(['message' => $e->getMessage()], 400);
            }

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
                    'venue_id' => $ticket->venue_id,
                    'venue_name' => $venue?->name,
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

    public function status(Request $request, $orderCode)
    {
        // Scope by event_slug — cegah enumerasi order/buyer event lain.
        $event = Eventner::approved()->where('slug', $request->query('event_slug', ''))->firstOrFail();

        $ticket = Ticket::with('venue')->where('eventner_id', $event->id)
            ->where('order_code', $orderCode)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'order_code' => $ticket->order_code,
                'venue_id' => $ticket->venue_id,
                'venue_name' => $ticket->venue?->name,
                'status' => $ticket->status,
                'total_amount' => $ticket->total_amount,
                'quantity' => $ticket->quantity,
                'paid_at' => $ticket->paid_at,
                'checked_in_at' => $ticket->checked_in_at,
                'buyer_name' => $ticket->buyer_name,
            ],
        ]);
    }
}

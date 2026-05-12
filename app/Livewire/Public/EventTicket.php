<?php

namespace App\Livewire\Public;

use App\Models\Eventner;
use App\Models\Ticket;
use App\Services\AutoGoPay;
use chillerlan\QRCode\QRCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventTicket extends Component
{
    public $eventner;
    public $view = 'form'; // 'form', 'payment', 'confirmation'
    public $buyerName;
    public $buyerEmail;
    public $buyerPhone;
    public $quantity = 1;
    public $confirmOrder;

    // Payment state
    public $qrImageUrl;
    public $expiryTime;
    public $currentTicketId;
    public $autoGoPayTransactionId;
    public $paymentAmount;
    public $paymentConfirmed = false;

    protected $queryString = [
        'confirmOrder' => ['except' => ''],
    ];

    protected $rules = [
        'buyerName' => 'required|string|max:255',
        'buyerEmail' => 'required|email|max:255',
        'buyerPhone' => 'required|string|max:20',
        'quantity' => 'required|integer|min:1',
    ];

    public function mount($slug)
    {
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();

        if (!$this->eventner->ticket_active || !$this->eventner->ticket_price) {
            abort(404, 'Tiket tidak tersedia untuk event ini.');
        }

        if ($this->confirmOrder) {
            $this->view = 'confirmation';
        }
    }

    public function updatedQuantity()
    {
        $max = $this->eventner->ticket_max_per_order ?? 10;
        if ($this->quantity > $max) {
            $this->quantity = $max;
        }
        if ($this->quantity < 1) {
            $this->quantity = 1;
        }
    }

    public function getTotalProperty()
    {
        return $this->quantity * $this->eventner->ticket_price;
    }

    public function submitTicket()
    {
        if (RateLimiter::tooManyAttempts('ticket-submit:' . request()->ip(), 5)) {
            session()->flash('error', 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.');
            return;
        }

        RateLimiter::hit('ticket-submit:' . request()->ip(), 60);

        $this->validate();

        $max = $this->eventner->ticket_max_per_order ?? 10;
        $this->validate(['quantity' => "required|integer|min:1|max:{$max}"]);

        $totalAmount = $this->total;

        try {
            // Generate QRIS via AutoGoPay
            $service = new AutoGoPay();
            $result = $service->generateQris($totalAmount);

            if (!($result['success'] ?? false)) {
                session()->flash('error', 'Gagal membuat QRIS. Silakan coba lagi.');
                return;
            }

            $data = $result['data'];

            // Simpan ticket PENDING
            $ticket = Ticket::create([
                'eventner_id' => $this->eventner->id,
                'buyer_name' => $this->buyerName,
                'buyer_email' => $this->buyerEmail,
                'buyer_phone' => $this->buyerPhone,
                'quantity' => $this->quantity,
                'price_per_ticket' => $this->eventner->ticket_price,
                'total_amount' => $totalAmount,
                'autogopay_transaction_id' => $data['transaction_id'],
                'qr_url' => $data['qr_url'],
                'status' => 'PENDING',
            ]);

            // Tampilkan QR code
            $this->qrImageUrl = $data['qr_url'];
            $this->expiryTime = $data['expiry_time'];
            $this->currentTicketId = $ticket->id;
            $this->autoGoPayTransactionId = $data['transaction_id'];
            $this->paymentAmount = $totalAmount;
            $this->paymentConfirmed = false;
            $this->view = 'payment';

        } catch (\Exception $e) {
            Log::error('AutoGoPay QRIS generation failed (ticket)', [
                'eventner_id' => $this->eventner->id,
                'amount' => $totalAmount,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Gagal membuat QRIS: ' . $e->getMessage());
        }
    }

    /**
     * Polling untuk cek status pembayaran.
     */
    public function checkPaymentStatus()
    {
        if (!$this->autoGoPayTransactionId || $this->paymentConfirmed) {
            return;
        }

        // Cek dari database dulu (webhook mungkin sudah masuk)
        $ticket = Ticket::find($this->currentTicketId);
        if ($ticket && $ticket->status === 'PAID') {
            $this->paymentConfirmed = true;
            $this->confirmOrder = $ticket->order_code;
            $this->view = 'confirmation';
            return;
        }

        if ($ticket && $ticket->status === 'EXPIRED') {
            $this->view = 'form';
            session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
            return;
        }

        // Fallback: cek langsung ke AutoGoPay API
        try {
            $service = new AutoGoPay();
            $result = $service->checkStatus($this->autoGoPayTransactionId);

            $status = $result['data']['transaction_status'] ?? 'pending';

            if ($status === 'settlement') {
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
                }
                $this->paymentConfirmed = true;
                $this->confirmOrder = $ticket->order_code;
                $this->view = 'confirmation';
            } elseif ($status === 'expire') {
                if ($ticket && $ticket->status !== 'EXPIRED') {
                    $ticket->update(['status' => 'EXPIRED']);
                }
                $this->view = 'form';
                session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
            }
        } catch (\Exception $e) {
            Log::warning('AutoGoPay status check failed (ticket)', ['error' => $e->getMessage()]);
        }
    }

    public function resetPayment()
    {
        $this->qrImageUrl = null;
        $this->expiryTime = null;
        $this->currentTicketId = null;
        $this->autoGoPayTransactionId = null;
        $this->paymentAmount = null;
        $this->paymentConfirmed = false;
        $this->view = 'form';
    }

    public function render()
    {
        $paidTicket = null;
        if ($this->confirmOrder) {
            $paidTicket = Ticket::where('order_code', $this->confirmOrder)
                ->where('eventner_id', $this->eventner->id)
                ->whereIn('status', ['PAID', 'CHECKED_IN'])
                ->first();
        }

        return view('livewire.public.event-ticket', [
            'paidTicket' => $paidTicket,
        ])->title('Beli Tiket - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}

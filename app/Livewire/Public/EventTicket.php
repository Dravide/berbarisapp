<?php

namespace App\Livewire\Public;

use App\Models\Eventner;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

#[Layout('layouts.frontend')]
class EventTicket extends Component
{
    public $eventner;
    public $view = 'form'; // 'form' or 'confirmation'
    public $buyerName;
    public $buyerEmail;
    public $buyerPhone;
    public $quantity = 1;
    public $confirmOrder;

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

        Configuration::setApiKey(config('services.xendit.key'));
        $apiInstance = new InvoiceApi();

        $externalId = 'TKT-' . time() . '-' . $this->eventner->id;

        $customer = new \Xendit\Invoice\CustomerObject([
            'given_names' => $this->buyerName,
            'email' => $this->buyerEmail,
            'mobile_number' => $this->buyerPhone,
        ]);

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => (float) $this->total,
            'payer_email' => $this->buyerEmail,
            'description' => "Tiket " . $this->quantity . "x untuk " . $this->eventner->nama_event,
            'customer' => $customer,
            'success_redirect_url' => route('event.ticket', ['slug' => $this->eventner->slug]),
            'failure_redirect_url' => route('event.ticket', ['slug' => $this->eventner->slug]),
            'currency' => 'IDR',
            'reminder_time' => 1,
        ]);

        try {
            $invoice = $apiInstance->createInvoice($createInvoiceRequest);

            Ticket::create([
                'eventner_id' => $this->eventner->id,
                'buyer_name' => $this->buyerName,
                'buyer_email' => $this->buyerEmail,
                'buyer_phone' => $this->buyerPhone,
                'quantity' => $this->quantity,
                'price_per_ticket' => $this->eventner->ticket_price,
                'total_amount' => $this->total,
                'xendit_invoice_id' => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
                'status' => 'PENDING',
            ]);

            return redirect()->away($invoice->getInvoiceUrl());

        } catch (\Exception $e) {
            Log::error('Xendit ticket invoice failed', [
                'external_id' => $externalId,
                'eventner_id' => $this->eventner->id,
                'amount' => $this->total,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Gagal membuat invoice: ' . $e->getMessage());
        }
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

<?php

namespace App\Livewire\Eventner\Ticket;

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $search = '';
    public $filterStatus = '';
    public $checkInCode = '';
    public $showCheckIn = false;
    public $checkInResult = null;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCheckIn()
    {
        $this->showCheckIn = true;
        $this->checkInCode = '';
        $this->checkInResult = null;
    }

    public function closeCheckIn()
    {
        $this->showCheckIn = false;
        $this->checkInCode = '';
        $this->checkInResult = null;
    }

    public function lookupTicket()
    {
        $this->validate(['checkInCode' => 'required|string']);

        $ticket = Ticket::where('eventner_id', $this->eventner->id)
            ->where('order_code', strtoupper(trim($this->checkInCode)))
            ->first();

        if (!$ticket) {
            $this->checkInResult = ['found' => false, 'message' => 'Tiket tidak ditemukan.'];
            return;
        }

        if ($ticket->status === 'PENDING') {
            $this->checkInResult = ['found' => true, 'ticket' => $ticket, 'message' => 'Tiket belum dibayar.'];
            return;
        }

        if ($ticket->status === 'EXPIRED') {
            $this->checkInResult = ['found' => true, 'ticket' => $ticket, 'message' => 'Tiket sudah expired.'];
            return;
        }

        if ($ticket->status === 'CHECKED_IN') {
            $this->checkInResult = ['found' => true, 'ticket' => $ticket, 'message' => 'Tiket sudah check-in pada ' . $ticket->checked_in_at->translatedFormat('d M Y H:i') . '.'];
            return;
        }

        // PAID - ready for check-in
        $this->checkInResult = ['found' => true, 'ticket' => $ticket, 'message' => null, 'ready' => true];
    }

    public function confirmCheckIn($ticketId)
    {
        $ticket = Ticket::where('eventner_id', $this->eventner->id)->findOrFail($ticketId);

        if ($ticket->status !== 'PAID') {
            session()->flash('error', 'Tiket tidak bisa di-check-in.');
            return;
        }

        $ticket->update([
            'status' => 'CHECKED_IN',
            'checked_in_at' => now(),
            'checked_in_by' => Auth::id(),
        ]);

        $this->checkInResult = ['found' => true, 'ticket' => $ticket->fresh(), 'message' => 'Berhasil check-in!'];
        session()->flash('success', 'Tiket ' . $ticket->order_code . ' berhasil check-in.');
    }

    public function render()
    {
        $query = Ticket::where('eventner_id', $this->eventner->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_code', 'like', '%' . $this->search . '%')
                    ->orWhere('buyer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('buyer_email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $tickets = $query->orderByDesc('created_at')->get();

        // Stats
        $stats = [
            'total' => Ticket::where('eventner_id', $this->eventner->id)->count(),
            'paid' => Ticket::where('eventner_id', $this->eventner->id)->where('status', 'PAID')->count(),
            'checked_in' => Ticket::where('eventner_id', $this->eventner->id)->where('status', 'CHECKED_IN')->count(),
            'revenue' => Ticket::where('eventner_id', $this->eventner->id)->whereIn('status', ['PAID', 'CHECKED_IN'])->sum('total_amount'),
            'pending' => Ticket::where('eventner_id', $this->eventner->id)->where('status', 'PENDING')->count(),
        ];

        return view('livewire.eventner.ticket.index', [
            'tickets' => $tickets,
            'stats' => $stats,
        ])->title('Tiket - ' . $this->eventner->nama_event);
    }
}

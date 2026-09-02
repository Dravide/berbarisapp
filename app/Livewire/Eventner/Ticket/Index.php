<?php

namespace App\Livewire\Eventner\Ticket;

use App\Models\Ticket;
use App\Services\AutoGoPay;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Tiket - BARIS APP')]
class Index extends Component
{
    use WithPagination;
    use FeatureGatedComponent;

    protected string $requiredFeature = 'tickets';

    protected string $paginationTheme = 'bootstrap';

    public $eventner;
    public $search = '';
    public $filterStatus = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Check-in state
    public $checkInCode = '';
    public $showCheckIn = false;
    public $checkInResult = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function syncPending()
    {
        $pendingTickets = Ticket::where('eventner_id', $this->eventner->id)
            ->where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->get();

        $service = new AutoGoPay();
        $synced = 0;
        $errors = 0;

        foreach ($pendingTickets as $ticket) {
            try {
                $result = $service->checkStatus($ticket->autogopay_transaction_id);
                $status = $result['data']['transaction_status'] ?? 'pending';

                if ($status === 'settlement') {
                    $claimed = Ticket::where('id', $ticket->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => 'PAID', 'paid_at' => now()]);

                    if ($claimed) {
                        $synced++;
                    }
                } elseif (in_array($status, ['expire', 'cancel'])) {
                    $claimed = Ticket::where('id', $ticket->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => strtoupper($status)]);

                    if ($claimed) {
                        $synced++;
                    }
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        session()->flash('success', "Sinkron selesai: {$synced} tiket diperbarui" . ($errors > 0 ? ", {$errors} gagal." : "."));
    }

    public function markAsPaid($id)
    {
        // Scope ke eventner milik user (cegah akses lintas tenant)
        $ticket = Ticket::where('eventner_id', $this->eventner->id)->findOrFail($id);

        if ($ticket->status !== 'PENDING') {
            session()->flash('error', 'Tiket ini tidak dapat dikonfirmasi (status: ' . $ticket->status . '). Hanya tiket PENDING yang bisa dikonfirmasi manual.');
            return;
        }

        $ticket->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        session()->flash('success', 'Tiket ' . $ticket->order_code . ' berhasil dikonfirmasi sebagai PAID.');
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
        $eventnerId = $this->eventner->id;

        $query = Ticket::where('eventner_id', $eventnerId);

        // Filter search (kode order / nama / email pembeli)
        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('order_code', 'like', $needle)
                    ->orWhere('buyer_name', 'like', $needle)
                    ->orWhere('buyer_email', 'like', $needle)
                    ->orWhere('autogopay_transaction_id', 'like', $needle);
            });
        }

        // Filter status
        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        // Filter rentang tanggal (berdasarkan dibuat)
        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $tickets = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        // Summary keseluruhan
        $summaryPaid = Ticket::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        $checkedIn = Ticket::where('eventner_id', $eventnerId)
            ->where('status', 'CHECKED_IN')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        $totalTicketsCount = Ticket::where('eventner_id', $eventnerId)->count();

        $statusCounts = Ticket::where('eventner_id', $eventnerId)
            ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('livewire.eventner.ticket.index', [
            'tickets' => $tickets,
            'summaryPaid' => $summaryPaid,
            'checkedIn' => $checkedIn,
            'totalTicketsCount' => $totalTicketsCount,
            'statusCounts' => $statusCounts,
        ])->title('Tiket - ' . $this->eventner->nama_event);
    }
}

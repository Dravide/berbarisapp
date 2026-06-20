<?php

namespace App\Livewire\Eventner\VoteTransaction;

use App\Models\Registration;
use App\Models\VoteTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Transaksi Voting - BARIS APP')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $filterStatus = '';
    public string $filterRegistration = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterRegistration' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterRegistration()
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
        $this->reset(['search', 'filterStatus', 'filterRegistration', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $eventner = auth()->user()->eventner;
        abort_unless($eventner, 403, 'Anda belum memiliki data Event terdaftar.');

        $eventnerId = $eventner->id;

        // Ambil daftar kontingen/sekolah untuk dropdown filter
        $registrations = Registration::where('eventner_id', $eventnerId)
            ->orderBy('nama_sekolah')
            ->get(['id', 'nama_sekolah', 'npsn']);

        // Query transaksi vote
        $query = VoteTransaction::query()
            ->where('eventner_id', $eventnerId)
            ->with(['registration:id,nama_sekolah,npsn,competition_category_id', 'registration.competitionCategory:id,name']);

        // Filter search (nama pemilih, email pemilih, id transaksi)
        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('voter_name', 'like', $needle)
                    ->orWhere('voter_email', 'like', $needle)
                    ->orWhere('autogopay_transaction_id', 'like', $needle);
            });
        }

        // Filter status
        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        // Filter kontingen/sekolah
        if ($this->filterRegistration !== '') {
            $query->where('registration_id', $this->filterRegistration);
        }

        // Filter rentang tanggal (berdasarkan created_at)
        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Paginate data transaksi
        $transactions = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        // Hitung Summary secara global untuk eventner ini
        // 1. Total Transaksi PAID, Total Vote, Total Pendapatan
        $summaryPaid = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(votes_earned), 0) as total_votes, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        // 2. Total Transaksi (seluruh status)
        $totalTransactionsCount = VoteTransaction::where('eventner_id', $eventnerId)->count();

        // 3. Breakdown jumlah transaksi per status (PENDING, PAID, EXPIRED, FAILED)
        $statusCounts = VoteTransaction::where('eventner_id', $eventnerId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('livewire.eventner.vote-transaction.index', [
            'transactions' => $transactions,
            'registrations' => $registrations,
            'summaryPaid' => $summaryPaid,
            'totalTransactionsCount' => $totalTransactionsCount,
            'statusCounts' => $statusCounts,
        ])->title('Transaksi Voting - ' . $eventner->nama_event);
    }
}

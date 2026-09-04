<?php

namespace App\Livewire\Eventner\VoteTransaction;

use App\Models\Registration;
use App\Traits\FeatureGatedComponent;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
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
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_transactions';

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

    public function mount()
    {
        $this->bootFeatureGate();
    }

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

    public function syncPending()
    {
        $eventner = auth()->user()->eventner;
        abort_unless($eventner, 403);

        $pending = VoteTransaction::where('eventner_id', $eventner->id)
            ->where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->get();

        $pendingTickets = Ticket::where('eventner_id', $eventner->id)
            ->where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->get();

        $total = $pending->count() + $pendingTickets->count();
        if ($total === 0) {
            session()->flash('success', 'Tidak ada transaksi PENDING untuk disinkronkan.');
            return;
        }

        // Batch: 25 transaksi per klik (sisanya klik lagi) — jaga request tidak kepanjangan
        $batch = 25;
        $voteBatch = $pending->take($batch);
        $voteLeft = $pending->count() - $voteBatch->count();
        $ticketBatch = $pendingTickets->take(max(0, $batch - $voteBatch->count()));
        $ticketLeft = $pendingTickets->count() - $ticketBatch->count();

        // Cek status semua transaksi batch secara paralel (HTTP pool)
        $service = new AutoGoPay();
        $txnIds = $voteBatch->pluck('autogopay_transaction_id', 'autogopay_transaction_id')
            ->merge($ticketBatch->pluck('autogopay_transaction_id', 'autogopay_transaction_id'))
            ->all();

        $statuses = $service->checkStatusMany($txnIds);

        $synced = 0;
        $errors = 0;

        // Terapkan status vote transactions (atomic claim: cegah race dgn webhook)
        foreach ($voteBatch as $tx) {
            $status = $statuses[$tx->autogopay_transaction_id] ?? null;

            if ($status === null) {
                $errors++;
                continue;
            }

            if ($status === 'settlement') {
                $claimed = VoteTransaction::where('id', $tx->id)
                    ->where('status', 'PENDING')
                    ->update(['status' => 'PAID', 'paid_at' => now()]);

                if ($claimed) {
                    $synced++;
                }
            } elseif (in_array($status, ['expire', 'cancel'])) {
                $claimed = VoteTransaction::where('id', $tx->id)
                    ->where('status', 'PENDING')
                    ->update(['status' => AutoGoPay::mapStatus($status)]);

                if ($claimed) {
                    $synced++;
                }
            }
        }

        // Terapkan status ticket transactions
        foreach ($ticketBatch as $ticket) {
            $status = $statuses[$ticket->autogopay_transaction_id] ?? null;

            if ($status === null) {
                $errors++;
                continue;
            }

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
                    ->update(['status' => AutoGoPay::mapStatus($status)]);

                if ($claimed) {
                    $synced++;
                }
            }
        }

        $remaining = $voteLeft + $ticketLeft;
        $message = "Sinkron selesai: {$synced} transaksi diperbarui";
        if ($errors > 0) {
            $message .= ", {$errors} gagal";
        }
        if ($remaining > 0) {
            $message .= ". Masih ada {$remaining} transaksi PENDING — klik Sinkron lagi untuk lanjut";
        }
        $message .= '.';

        session()->flash('success', $message);
    }

    public function markAsPaid($id)
    {
        $eventner = auth()->user()->eventner;
        abort_unless($eventner, 403, 'Anda belum memiliki data Event terdaftar.');

        // Scope ke eventner milik user (cegah akses lintas tenant)
        $transaction = VoteTransaction::where('eventner_id', $eventner->id)->findOrFail($id);

        if ($transaction->status !== 'PENDING') {
            session()->flash('error', 'Transaksi ini tidak dapat dikonfirmasi (status: ' . $transaction->status . '). Hanya transaksi PENDING yang bisa dikonfirmasi manual.');
            return;
        }

        $transaction->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        session()->flash('success', 'Transaksi voting berhasil dikonfirmasi sebagai PAID. Vote telah dihitung.');
    }

    public function render()
    {
        $eventner = auth()->user()->eventner;
        abort_unless($eventner, 403, 'Anda belum memiliki data Event terdaftar.');

        $eventnerId = $eventner->id;

        // Ambil daftar kontingen/sekolah untuk dropdown filter
        $registrations = Registration::where('eventner_id', $eventnerId)
            ->orderBy('nama_sekolah')
            ->with('competitionCategory:id,name,parent_id')
            ->get(['id', 'nama_sekolah', 'label_pasukan', 'npsn', 'competition_category_id']);

        // Query transaksi vote
        $query = VoteTransaction::query()
            ->where('eventner_id', $eventnerId)
            ->with(['registration:id,nama_sekolah,label_pasukan,npsn,competition_category_id', 'registration.competitionCategory:id,name']);

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

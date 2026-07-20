<?php

namespace App\Livewire\Eventner\VoteResults;

use App\Models\Registration;
use App\Traits\FeatureGatedComponent;
use App\Models\VoteTransaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Detail Voter - BARIS APP')]
class Show extends Component
{
    use WithPagination;
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_results';

    protected string $paginationTheme = 'bootstrap';

    public Registration $registration;
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(Registration $registration)
    {
        $this->bootFeatureGate();
        $eventner = auth()->user()->eventner;
        abort_unless($eventner, 403, 'Anda belum memiliki data Event terdaftar.');

        abort_unless(
            $registration->eventner_id === $eventner->id,
            403,
            'Kontingen ini bukan milik event Anda.'
        );

        $this->registration = $registration->load(['competitionCategory', 'eventner']);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $voters = VoteTransaction::query()
            ->where('registration_id', $this->registration->id)
            ->where('status', 'PAID')
            ->when($this->search !== '', function ($q) {
                $needle = '%' . $this->search . '%';
                $q->where(function ($w) use ($needle) {
                    $w->where('voter_name', 'like', $needle)
                        ->orWhere('voter_email', 'like', $needle)
                        ->orWhere('autogopay_transaction_id', 'like', $needle);
                });
            })
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(20);

        $summary = VoteTransaction::where('registration_id', $this->registration->id)
            ->where('status', 'PAID')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(votes_earned), 0) as total_votes, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        return view('livewire.eventner.vote-results.show', [
            'voters' => $voters,
            'summary' => $summary,
        ])->title('Detail Voter - ' . $this->registration->nama_sekolah);
    }
}

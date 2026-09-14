<?php

namespace App\Livewire\Eventner\VoteResults;

use App\Models\CompetitionCategory;
use App\Traits\FeatureGatedComponent;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_results';

    public $activeTab = '';
    public $categories = [];

    public function mount()
    {
        $this->bootFeatureGate();
        $eventner = auth()->user()->eventner;
        if ($eventner) {
            $this->categories = $eventner->competitionCategories()
                ->whereNotNull('parent_id')
                ->with('parent')
                ->get();
        }

        if ($this->categories->count() > 0) {
            $this->activeTab = $this->categories[0]->id;
        }
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
    }

    public function render()
    {
        $eventner = auth()->user()->eventner;

        $results = [];
        $summary = null;

        if ($eventner) {
            // Calculate grand summary across all competition categories
            $summary = VoteTransaction::where('eventner_id', $eventner->id)
                ->where('status', 'PAID')
                ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(votes_earned), 0) as total_votes, COALESCE(SUM(amount), 0) as total_amount')
                ->first();

            if ($this->activeTab) {
                $results = Registration::where('eventner_id', $eventner->id)
                    ->where('competition_category_id', $this->activeTab)
                    ->withSum(['voteTransactions as total_votes' => function ($query) {
                        $query->where('status', 'PAID');
                    }], 'votes_earned')
                    // Pendapatan per kontingen diambil dari nominal transaksi
                    // yang benar-benar dibayar, bukan dikalikan harga per vote.
                    // votes_earned sudah termasuk multiplier booster, jadi
                    // mengalikannya dengan harga memberi angka yang lebih besar
                    // dari uang yang masuk.
                    ->withSum(['voteTransactions as total_amount' => function ($query) {
                        $query->where('status', 'PAID');
                    }], 'amount')
                    ->orderByDesc('total_votes')
                    ->get();
            }
        }

        return view('livewire.eventner.vote-results.index', [
            'results' => $results,
            'summary' => $summary,
            // Harga per vote yang berlaku — dipakai label konversi, bukan
            // angka 1.000 yang di-hardcode. Eventner boleh memasang harga lain.
            'votePrice' => $eventner?->vote_price ?: 1000,
        ])->title('Hasil Voting - ' . app_name());
    }
}

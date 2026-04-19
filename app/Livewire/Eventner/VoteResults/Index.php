<?php

namespace App\Livewire\Eventner\VoteResults;

use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Hasil Voting - BARIS APP')]
class Index extends Component
{
    public $activeTab = '';
    public $categories = [];

    public function mount()
    {
        $eventner = auth()->user()->eventner;
        if ($eventner) {
            $this->categories = $eventner->competitionCategories()->get();
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
        if ($eventner && $this->activeTab) {
            $results = Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->withSum(['voteTransactions as total_votes' => function ($query) {
                    $query->where('status', 'PAID');
                }], 'votes_earned')
                ->orderByDesc('total_votes')
                ->get();
        }

        return view('livewire.eventner.vote-results.index', [
            'results' => $results
        ]);
    }
}

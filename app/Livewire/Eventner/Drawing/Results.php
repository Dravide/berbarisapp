<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Registration;

use App\Models\Eventner;

#[Layout('layouts.frontend')]
#[Title('Hasil Pengundian - BARIS APP')]
class Results extends Component
{
    public $slug;
    public $eventnerId;
    public $activeTab = '';
    public $categories = [];

    public function mount($slug)
    {
        $this->slug = $slug;
        $eventner = Eventner::where('slug', $slug)->firstOrFail();
        $this->eventnerId = $eventner->id;

        if ($eventner) {
            $this->categories = $eventner->competitionCategories()->get()->toArray();
        }

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
    }

    public function render()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);

        $results = Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();

        $category = \App\Models\CompetitionCategory::find($this->activeTab);
        $totalSchools = $category->kuota ?? Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->count();

        return view('livewire.eventner.drawing.results', [
            'results' => $results,
            'totalSchools' => $totalSchools,
            'eventner' => $eventner,
        ])->layoutData(['eventner' => $eventner]);
    }
}

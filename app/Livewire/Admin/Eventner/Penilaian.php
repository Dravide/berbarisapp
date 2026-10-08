<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\AssessmentCategory;
use App\Models\DeductionCategory;
use App\Models\Eventner;

#[Layout('layouts.admin')]
class Penilaian extends Component
{
    public $eventnerId;
    public $eventner;

    public function mount($id)
    {
        $this->eventnerId = $id;
        $this->eventner = Eventner::findOrFail($id);
    }

    /**
     * Seluruh rubrik milik event, tanpa saringan tab/babak/seri — admin
     * melihat definisinya mentah.
     *
     * Eager load meniru Builder::categories() termasuk competitionCategory.parent:
     * full_name butuh induknya, dan membiarkannya lazy-load di sini = satu query
     * ekstra per kategori (event terbesar punya 196 kriteria).
     */
    public function getCategoriesProperty()
    {
        return AssessmentCategory::query()
            ->with([
                'subCategories.criterias',
                'deductionCategories.criterias',
                'competitionCategory.parent',
                'competitionSeries',
                'competitionRound',
            ])
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('competition_category_id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Pengurangan global — sekali query, dikumpulkan per tingkat di PHP.
     *
     * Melakukannya per tingkat (global()->forLevel($id) di loop) berarti N
     * query; kategori tanpa tingkat (competition_category_id NULL) jadi seksi
     * "Berlaku Semua Tingkat".
     */
    public function getGlobalDeductionsProperty()
    {
        return DeductionCategory::query()
            ->global()
            ->where('eventner_id', $this->eventnerId)
            ->with('criterias')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('competition_category_id');
    }

    public function render()
    {
        return view('livewire.admin.eventner.penilaian')
            ->title('Format Penilaian: ' . $this->eventner->nama_event . ' - ' . app_name());
    }
}

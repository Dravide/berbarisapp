<?php

namespace App\Livewire\Eventner\ActivityLog;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterEvent = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterEvent()
    {
        $this->resetPage();
    }

    public function render()
    {
        $eventner = Auth::user()->eventner;

        if (!$eventner) {
            abort(403);
        }

        $eventnerId = $eventner->id;

        // Get IDs of all models belonging to this eventner
        $categoryIds = \App\Models\AssessmentCategory::where('eventner_id', $eventnerId)->pluck('id');
        $subCategoryIds = \App\Models\AssessmentSubCategory::whereIn('assessment_category_id', $categoryIds)->pluck('id');
        $criteriaIds = \App\Models\AssessmentCriteria::whereIn('assessment_sub_category_id', $subCategoryIds)->pluck('id');
        $judgeIds = \App\Models\Judge::where('eventner_id', $eventnerId)->pluck('id');
        $registrationIds = \App\Models\Registration::where('eventner_id', $eventnerId)->pluck('id');
        $compCategoryIds = \App\Models\CompetitionCategory::where('eventner_id', $eventnerId)->pluck('id');
        $championIds = \App\Models\ChampionCategory::where('eventner_id', $eventnerId)->pluck('id');

        $query = Activity::where(function ($q) use (
            $categoryIds, $subCategoryIds, $criteriaIds,
            $judgeIds, $registrationIds, $compCategoryIds, $championIds
        ) {
            $q->where(function ($q) use ($categoryIds) {
                $q->where('subject_type', \App\Models\AssessmentCategory::class)
                    ->whereIn('subject_id', $categoryIds);
            })->orWhere(function ($q) use ($subCategoryIds) {
                $q->where('subject_type', \App\Models\AssessmentSubCategory::class)
                    ->whereIn('subject_id', $subCategoryIds);
            })->orWhere(function ($q) use ($criteriaIds) {
                $q->where('subject_type', \App\Models\AssessmentCriteria::class)
                    ->whereIn('subject_id', $criteriaIds);
            })->orWhere(function ($q) use ($judgeIds) {
                $q->where('subject_type', \App\Models\Judge::class)
                    ->whereIn('subject_id', $judgeIds);
            })->orWhere(function ($q) use ($registrationIds) {
                $q->where('subject_type', \App\Models\Registration::class)
                    ->whereIn('subject_id', $registrationIds);
            })->orWhere(function ($q) use ($compCategoryIds) {
                $q->where('subject_type', \App\Models\CompetitionCategory::class)
                    ->whereIn('subject_id', $compCategoryIds);
            })->orWhere(function ($q) use ($championIds) {
                $q->where('subject_type', \App\Models\ChampionCategory::class)
                    ->whereIn('subject_id', $championIds);
            });
        });

        if ($this->search) {
            $query->where('description', 'like', "%{$this->search}%");
        }

        if ($this->filterEvent) {
            $query->where('event', $this->filterEvent);
        }

        $activities = $query->latest()->paginate(20);

        return view('livewire.eventner.activity-log.index', [
            'activities' => $activities,
        ])->title('Activity Log - ' . $eventner->nama_event);
    }
}

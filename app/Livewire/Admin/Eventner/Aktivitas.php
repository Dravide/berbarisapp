<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use App\Models\ChampionCategory;
use App\Models\AssessmentCategory;
use App\Models\AssessmentSubCategory;
use App\Models\AssessmentCriteria;
use App\Models\Judge;
use Spatie\Activitylog\Models\Activity;

/**
 * Riwayat aktivitas satu event: login/logout akun panitia (listener di
 * AppServiceProvider) + perubahan datanya (spatie activitylog). Halaman
 * sendiri — di Show cukup ringkasannya, jadi detail event tak memanjang.
 */
#[Layout('layouts.admin')]
class Aktivitas extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $eventnerId;
    public $eventner;

    public $search = '';
    public $filterJenis = '';

    public function mount($id)
    {
        $this->eventnerId = $id;
        $this->eventner = Eventner::with('user')->findOrFail($id);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterJenis()
    {
        $this->resetPage();
    }

    public function getActivitiesProperty()
    {
        $owner = $this->eventner->user;
        $eventnerId = $this->eventnerId;

        $query = Activity::with('causer')
            ->where(function ($q) use ($owner, $eventnerId) {
                // Perubahan data model yang langsung punya eventner_id
                foreach ([
                    Registration::class,
                    CompetitionCategory::class,
                    ChampionCategory::class,
                    AssessmentCategory::class,
                    Judge::class,
                ] as $type) {
                    $q->orWhere(function ($q) use ($type, $eventnerId) {
                        $q->where('subject_type', $type)
                            ->whereIn('subject_id', $type::where('eventner_id', $eventnerId)->pluck('id'));
                    });
                }

                // AssessmentSubCategory/Criteria tak punya eventner_id —
                // dilacak lewat rantai relasinya ke AssessmentCategory.
                $kategoriIds = AssessmentCategory::where('eventner_id', $eventnerId)->pluck('id');
                $subIds = AssessmentSubCategory::whereIn('assessment_category_id', $kategoriIds)->pluck('id');

                $q->orWhere(function ($q) use ($subIds) {
                    $q->where('subject_type', AssessmentSubCategory::class)
                        ->whereIn('subject_id', $subIds);
                })->orWhere(function ($q) use ($subIds) {
                    $q->where('subject_type', AssessmentCriteria::class)
                        ->whereIn('subject_id', AssessmentCriteria::whereIn('assessment_sub_category_id', $subIds)->pluck('id'));
                });

                // Aktivitas sesi akun panitia (login/logout)
                if ($owner) {
                    $q->orWhere(function ($q) use ($owner) {
                        $q->where('causer_type', $owner::class)
                            ->where('causer_id', $owner->id)
                            ->whereIn('event', ['login', 'logout']);
                    });

                    // Aksi pada akun panitia itu sendiri (mis. approval/suspend)
                    $q->orWhere(function ($q) use ($owner) {
                        $q->where('subject_type', $owner::class)
                            ->where('subject_id', $owner->id);
                    });
                }
            });

        if ($this->search) {
            $query->where('description', 'like', "%{$this->search}%");
        }

        if ($this->filterJenis !== '') {
            $query->where('event', $this->filterJenis);
        }

        return $query->latest()->paginate(25);
    }

    public function render()
    {
        return view('livewire.admin.eventner.aktivitas')
            ->title('Aktivitas: ' . $this->eventner->nama_event . ' - ' . app_name());
    }
}

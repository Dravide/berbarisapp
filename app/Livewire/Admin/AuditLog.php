<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit log tingkat platform: semua aktivitas yang dicatat spatie
 * activitylog — termasuk yang dicatat admin lewat activity()->performedOn()
 * (preseden Admin\School\Show) dan model yang pakai trait LogsActivity.
 */
#[Layout('layouts.admin')]
class AuditLog extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterLog = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterLog()
    {
        $this->resetPage();
    }

    public function getLogNamesProperty()
    {
        return Activity::query()
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');
    }

    public function render()
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('description', 'like', $needle)
                    ->orWhere('subject_type', 'like', $needle)
                    ->orWhereHas('causer', function ($c) use ($needle) {
                        $c->where('name', 'like', $needle)
                            ->orWhere('username', 'like', $needle)
                            ->orWhere('email', 'like', $needle);
                    });
            });
        }

        if ($this->filterLog !== '') {
            $query->where('log_name', $this->filterLog);
        }

        $activities = $query->paginate(25);

        return view('livewire.admin.audit-log', ['activities' => $activities])
            ->title('Audit Log - ' . app_name());
    }
}

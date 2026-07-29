<?php

namespace App\Livewire\Admin\School;

use Livewire\Component;
use App\Models\School;
use App\Models\Registration;
use App\Models\Eventner;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Data Sekolah - BARIS APP')]
class Index extends Component
{
    public $schools;
    public $search = '';
    public $filterEvent = '';
    public $events;

    public function mount()
    {
        $this->events = Eventner::orderBy('nama_event')->get(['id', 'nama_event']);
        $this->loadSchools();
    }

    public function loadSchools()
    {
        $query = School::query()
            ->select('npsn', 'nama_sekolah', 'logo_sekolah')
            ->selectSub(
                Registration::whereColumn('npsn', 'schools.npsn')
                    ->selectRaw('COUNT(*)'),
                'total_registrations'
            )
            ->selectSub(
                Registration::whereColumn('npsn', 'schools.npsn')
                    ->where('status_berkas', 'Terverifikasi')
                    ->selectRaw('COUNT(*)'),
                'verified_count'
            );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('npsn', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_sekolah', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterEvent) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('registrations')
                    ->whereColumn('registrations.npsn', 'schools.npsn')
                    ->where('registrations.eventner_id', $this->filterEvent);
            });
        }

        $this->schools = $query->orderBy('nama_sekolah')->get();

        // Load participant counts per school
        $npsns = $this->schools->pluck('npsn')->toArray();

        if (!empty($npsns)) {
            $participantCounts = DB::table('registrations')
                ->join('participants', 'registrations.id', '=', 'participants.registration_id')
                ->whereIn('registrations.npsn', $npsns)
                ->select('registrations.npsn', DB::raw('COUNT(participants.id) as total_participants'))
                ->groupBy('registrations.npsn')
                ->pluck('total_participants', 'npsn');

            $this->schools->each(function ($school) use ($participantCounts) {
                $school->total_participants = $participantCounts[$school->npsn] ?? 0;
            });
        }
    }

    public function updatedSearch()
    {
        $this->loadSchools();
    }

    public function updatedFilterEvent()
    {
        $this->loadSchools();
    }

    public function render()
    {
        return view('livewire.admin.school.index');
    }
}

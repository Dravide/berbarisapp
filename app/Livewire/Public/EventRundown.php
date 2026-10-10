<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Eventner;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventRundown extends Component
{
    public $eventner;

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
            $this->eventner->loadMissing('eventRundowns.sourceCategory.parent', 'eventSchedules.category.parent', 'eventSchedules.venue');
        } else {
            $this->eventner = Eventner::with('eventRundowns.sourceCategory.parent', 'eventSchedules.category.parent', 'eventSchedules.venue')
                ->approved()->where('slug', $slug)->firstOrFail();
        }
    }

    /**
     * Jadwal pertandingan terkelompok tanggal → venue. Kosong untuk event
     * lama — section jadwal tidak dirender supaya tampilannya tak berubah.
     */
    public function getScheduleDaysProperty()
    {
        return $this->eventner->eventSchedules
            ->groupBy(fn ($s) => $s->tanggal?->format('Y-m-d') ?? optional($this->eventner->tanggal)->format('Y-m-d'))
            ->map(function ($items, $tanggal) {
                return [
                    'tanggal' => $tanggal ? \Carbon\Carbon::parse($tanggal) : null,
                    'venues' => $items
                        ->groupBy(fn ($s) => $s->eventner_venue_id ?? 0)
                        ->map(function ($rows, $venueId) {
                            $first = $rows->first();

                            return [
                                'venue' => $venueId ? $first->venue?->name : null,
                                'items' => $rows->sortBy('sort_order')->values(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->sortBy(fn ($day) => $day['tanggal']?->format('Y-m-d') ?? '9999')
            ->values();
    }

    public function render()
    {
        return view('livewire.public.event-rundown', [
            'scheduleDays' => $this->scheduleDays,
        ])
            ->title('Rundown Acara - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}

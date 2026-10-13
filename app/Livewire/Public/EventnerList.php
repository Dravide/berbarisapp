<?php

namespace App\Livewire\Public;

use App\Models\Eventner;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman daftar semua penyelenggara event yang sudah disetujui — tujuan
 * tautan "Lihat Semua Penyelenggara" di landing. Landing hanya menampilkan
 * enam kartu terdekat tanggalnya; daftar lengkapnya hidup di sini.
 */
#[Layout('layouts.landing')]
class EventnerList extends Component
{
    use WithPagination;

    public $logoPath = null;

    public $favicon = null;

    public $filter = 'all';

    protected $queryString = [
        'filter' => ['except' => 'all'],
    ];

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'verified'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function mount()
    {
        $this->logoPath = Setting::get('logo_dark')
            ? Storage::disk('public')->url(Setting::get('logo_dark'))
            : null;

        $faviconSetting = Setting::get('favicon');
        $this->favicon = $faviconSetting
            ? Storage::disk('public')->url($faviconSetting)
            : null;
    }

    public function render()
    {
        // Paling baru di atas: penyelenggara yang eventnya paling dekat
        // tampil di depan. Hanya yang approved: kartu pending akan menautkan
        // ke 404 (scopeApproved() dipakai semua halaman publik lain dengan
        // alasan yang sama).
        $counts = [
            'all' => Eventner::approved()->count(),
            'verified' => Eventner::approved()->verified()->count(),
        ];

        $eventners = Eventner::approved()
            ->when($this->filter === 'verified', fn ($q) => $q->verified())
            ->withCount('registrations')
            ->orderByDesc('tanggal')
            ->paginate(16);

        return view('livewire.public.eventner-list', [
            'eventners' => $eventners,
            'counts' => $counts,
        ])
            ->layout('layouts.landing', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
            ])
            ->title('Penyelenggara Event - ' . app_name());
    }
}

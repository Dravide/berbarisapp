<?php

namespace App\Livewire\Admin;

use App\Models\ErrorLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Log error tingkat platform: semua baris error_logs (kode ER-XXXXXX yang
 * diberikan ke user saat 500). Admin bisa mencari kode, menandai selesai,
 * dan membaca trace untuk diagnosis. Nama kelas ErrorLogIndex (bukan
 * ErrorLog) supaya tidak bentrok dengan model App\Models\ErrorLog.
 */
#[Layout('layouts.admin')]
class ErrorLogIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterStatus = '';   // '' | open | resolved
    public $filterStatusHttp = ''; // '' | 500 | 503 | ...

    /** ErrorLog yang sedang dibuka di modal detail (null = modal tertutup). */
    public ?int $detailId = null;

    public function showDetail(int $id)
    {
        $this->detailId = $id;
    }

    public function closeDetail()
    {
        $this->detailId = null;
    }

    public function tandaiSelesai(int $id)
    {
        $log = ErrorLog::findOrFail($id);

        if ($log->resolved_at === null) {
            $log->update([
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);
        }

        // Modal mengambil baris terbaru pada render berikutnya.
        $this->detailId = $id;
    }

    public function bukaKembali(int $id)
    {
        $log = ErrorLog::findOrFail($id);

        if ($log->resolved_at !== null) {
            $log->update([
                'resolved_at' => null,
                'resolved_by' => null,
            ]);
        }

        $this->detailId = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterStatusHttp()
    {
        $this->resetPage();
    }

    public function getHttpStatusOptionsProperty()
    {
        return ErrorLog::query()
            ->select('http_status')
            ->whereNotNull('http_status')
            ->distinct()
            ->orderBy('http_status')
            ->pluck('http_status');
    }

    public function render()
    {
        $query = ErrorLog::query()
            ->with(['user', 'eventner', 'resolver'])
            ->latest();

        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('code', 'like', $needle)
                    ->orWhere('message', 'like', $needle)
                    ->orWhere('url', 'like', $needle)
                    ->orWhere('exception_class', 'like', $needle);
            });
        }

        if ($this->filterStatus === 'open') {
            $query->whereNull('resolved_at');
        } elseif ($this->filterStatus === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        if ($this->filterStatusHttp !== '') {
            $query->where('http_status', (int) $this->filterStatusHttp);
        }

        $logs = $query->paginate(20);

        return view('livewire.admin.error-log', [
            'logs' => $logs,
            // eager load supaya modal tidak N+1
            'detail' => $this->detailId
                ? ErrorLog::with(['user', 'eventner', 'resolver'])->find($this->detailId)
                : null,
        ])
            ->title('Log Error - ' . app_name());
    }
}

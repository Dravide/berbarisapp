<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\User;
use App\Services\MailyService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Persetujuan Eventner - BARIS APP')]
class Pending extends Component
{
    public $pendingEventners;
    public $selectedEventnerId = null;
    public $rejectionReason = '';
    public $showRejectModal = false;

    public function mount()
    {
        $this->loadPending();
    }

    public function loadPending()
    {
        $this->pendingEventners = Eventner::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function approve($eventnerId)
    {
        $eventner = Eventner::with('user')->findOrFail($eventnerId);

        if ($eventner->status !== 'pending') {
            session()->flash('error', 'Eventner ini sudah diproses.');
            $this->loadPending();
            return;
        }

        $eventner->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $eventner->user->update([
            'is_active' => true,
        ]);

        // Send approval email
        try {
            $mailService = app(MailyService::class);
            $mailService->sendEventnerApproved(
                $eventner->user->email,
                $eventner->user->name,
                $eventner->nama_event
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send approval email', [
                'eventner_id' => $eventnerId,
                'error' => $e->getMessage(),
            ]);
        }

        session()->flash('success', "Eventner \"{$eventner->nama_event}\" berhasil disetujui.");
        $this->loadPending();
    }

    public function openRejectModal($eventnerId)
    {
        $this->selectedEventnerId = $eventnerId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject()
    {
        $this->validate([
            'rejectionReason' => 'nullable|string|max:1000',
        ]);

        $eventner = Eventner::with('user')->findOrFail($this->selectedEventnerId);

        if ($eventner->status !== 'pending') {
            session()->flash('error', 'Eventner ini sudah diproses.');
            $this->loadPending();
            $this->showRejectModal = false;
            return;
        }

        $eventner->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $this->rejectionReason ?: null,
        ]);

        // Send rejection email
        try {
            $mailService = app(MailyService::class);
            $mailService->sendEventnerRejected(
                $eventner->user->email,
                $eventner->user->name,
                $eventner->nama_event,
                $this->rejectionReason ?: 'Tidak memenuhi persyaratan'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send rejection email', [
                'eventner_id' => $this->selectedEventnerId,
                'error' => $e->getMessage(),
            ]);
        }

        session()->flash('success', "Eventner \"{$eventner->nama_event}\" ditolak.");
        $this->showRejectModal = false;
        $this->loadPending();
    }

    public function render()
    {
        return view('livewire.admin.eventner.pending');
    }
}

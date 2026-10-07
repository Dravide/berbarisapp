<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\User;
use App\Services\MailyService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Pending extends Component
{
    public $pendingEventners;
    public $selectedEventnerId = null;
    public $rejectionReason = '';
    public $showRejectModal = false;

    /** Modal "Detail Kontak" — baris tabel sengaja tetap ringkas. */
    public $showDetailModal = false;
    public $detail = null;

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

    /**
     * Susun data kontak pendaftar untuk modal detail.
     *
     * Sumbernya hanya yang benar-benar sudah ada: akun pendaftar (`users` —
     * nama, username, email) dan tautan profil event (`eventners.link_*`).
     * Form pendaftaran eventner tidak meminta nomor telepon, jadi kolom itu
     * memang tidak ada; WhatsApp baru terisi kalau eventner sudah mengisi
     * "Tautan Tambahan" di profil event-nya.
     *
     * Disusun jadi array (bukan model) supaya blade tidak perlu tahu relasi
     * mana yang dipakai dan mana yang kosong.
     */
    public function openDetailModal($eventnerId)
    {
        $eventner = Eventner::with(['user', 'saasPlan'])->findOrFail($eventnerId);

        $this->detail = [
            'id' => $eventner->id,
            'nama_event' => $eventner->nama_event,
            'lokasi' => $eventner->lokasi,
            'tanggal' => $eventner->tanggal,
            'tanggal_akhir' => $eventner->tanggal_akhir,

            'penyelenggara' => $eventner->user?->name,
            'username' => $eventner->user?->username,
            'email' => $eventner->user?->email,

            'whatsapp' => $eventner->link_whatsapp,
            'whatsapp_url' => $this->whatsappUrl($eventner->link_whatsapp),
            'instagram' => $eventner->link_instagram,
            'tiktok' => $eventner->link_tiktok,

            'paket' => $eventner->saasPlan?->name ?? ($eventner->plan === 'paid' ? 'Berbayar' : 'Gratis'),
            'sumber' => $eventner->registration_source === 'self' ? 'Daftar mandiri' : 'Diinput admin',
            'dibayar' => $eventner->registration_paid_at !== null,
            'terdaftar' => $eventner->created_at,
        ];

        $this->showDetailModal = true;
    }

    /**
     * Tautan WhatsApp dari isian bebas: nomor telanjang ("0812...") dirangkai
     * jadi wa.me, URL apa pun dipakai apa adanya. Nilai kosong tetap null
     * supaya blade bisa menyembunyikan barisnya.
     */
    private function whatsappUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // 0 → 62 (kode Indonesia), sisanya dianggap sudah berkode negara.
        $nomor = preg_replace('/\D/', '', $value);
        $nomor = str_starts_with($nomor, '0') ? '62' . substr($nomor, 1) : $nomor;

        return $nomor !== '' ? 'https://wa.me/' . $nomor : null;
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
        $this->tutupDetail();
        $this->loadPending();
    }

    public function openRejectModal($eventnerId)
    {
        $this->selectedEventnerId = $eventnerId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;

        // Bisa dipanggil dari dalam modal detail — jangan biarkan dua modal
        // bertumpuk.
        $this->showDetailModal = false;
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
        $this->tutupDetail();
        $this->loadPending();
    }

    /** Modal detail ikut ditutup setelah barisnya diproses. */
    private function tutupDetail(): void
    {
        $this->showDetailModal = false;
        $this->detail = null;
    }

    public function render()
    {
        return view('livewire.admin.eventner.pending')->title('Persetujuan Eventner - ' . app_name());
    }
}

<?php

namespace App\Livewire\Eventner\Notification;

use App\Models\Registration;
use App\Services\FcmService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $title = '';
    public $body = '';
    public $target = 'broadcast'; // 'broadcast' | 'registration'
    public $registrationId = null;
    public $sending = false;

    public function send()
    {
        $this->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:1000',
            'target' => 'required|in:broadcast,registration',
            'registrationId' => 'required_if:target,registration|nullable|exists:registrations,id',
        ], [
            'title.required' => 'Judul notifikasi wajib diisi.',
            'body.required' => 'Isi pesan wajib diisi.',
            'registrationId.required_if' => 'Pilih sekolah/pasukan tujuan.',
        ]);

        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }

        $fcm = app(FcmService::class);
        $data = ['type' => 'broadcast', 'event_slug' => $eventner->slug];

        $this->sending = true;
        try {
            if ($this->target === 'registration') {
                $reg = Registration::where('eventner_id', $eventner->id)
                    ->findOrFail($this->registrationId);
                $sent = $fcm->sendToModel($reg, $this->title, $this->body, $data);
                $message = "Notifikasi terkirim ke {$reg->nama_sekolah} ({$sent} device).";
            } else {
                $sent = $fcm->sendToEvent($eventner, $this->title, $this->body, $data);
                $message = "Notifikasi broadcast terkirim ke {$sent} device peserta.";
            }

            if ($sent === 0) {
                $message = 'Tidak ada device terdaftar untuk target ini.';
            }

            $this->reset(['title', 'body', 'registrationId']);
            session()->flash('success', $message);
        } catch (\Throwable $e) {
            Log::error('Manual FCM broadcast failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Gagal mengirim notifikasi: ' . $e->getMessage());
        } finally {
            $this->sending = false;
        }
    }

    public function render()
    {
        $eventner = Auth::user()->eventner;

        return view('livewire.eventner.notification.index', [
            'registrations' => Registration::where('eventner_id', $eventner->id)
                ->whereIn('status_berkas', ['confirmed', 'Terverifikasi'])
                ->orderBy('nama_sekolah')
                ->get(['id', 'nama_sekolah', 'label_pasukan']),
        ])->title('Kirim Notifikasi - BARIS APP');
    }
}

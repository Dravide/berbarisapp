<?php

namespace App\Livewire\Eventner\Venue;

use App\Models\CompetitionCategory;
use App\Models\EventnerVenue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $name = '';
    public $alamat = '';
    public $latitude = '';
    public $longitude = '';
    public $google_maps_url = '';
    public $is_active = true;

    // Tiket: kapasitas & harga khusus tempat ini (kosong = tanpa batas / ikut harga event).
    public $ticket_kuota = '';
    public $ticket_price = '';

    public $isEditMode = false;
    public $editingId = null;

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;
    }

    #[Computed]
    public function venues()
    {
        return EventnerVenue::where('eventner_id', $this->eventnerId)
            ->withCount('competitionCategories')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** Sisa kuota tiket + jumlah PENDING per tempat (info meja panitia). */
    #[Computed]
    public function ticketStats()
    {
        return $this->venues->mapWithKeys(function ($venue) {
            $pending = (int) $venue->tickets()->where('status', 'PENDING')->sum('quantity');

            return [$venue->id => [
                'sold' => $venue->ticketsSoldCount(),
                'pending' => $pending,
                'remaining' => $venue->remainingTicketSlots(),
            ]];
        });
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'google_maps_url' => 'nullable|url|max:500',
            'ticket_kuota' => 'nullable|integer|min:0|max:1000000',
            'ticket_price' => 'nullable|integer|min:0|max:1000000000',
        ]);

        $data = [
            'name' => strip_tags($this->name),
            'alamat' => $this->alamat ?: null,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'google_maps_url' => $this->google_maps_url ?: null,
            'is_active' => (bool) $this->is_active,
            'ticket_kuota' => $this->ticket_kuota === '' ? null : (int) $this->ticket_kuota,
            'ticket_price' => $this->ticket_price === '' ? null : (int) $this->ticket_price,
        ];

        if ($this->isEditMode && $this->editingId) {
            $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $venue->update($data);
            session()->flash('success', 'Tempat pelaksanaan berhasil diperbarui.');
        } else {
            $maxOrder = EventnerVenue::where('eventner_id', $this->eventnerId)->max('sort_order') ?? -1;

            EventnerVenue::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'sort_order' => $maxOrder + 1,
            ]));
            session()->flash('success', 'Tempat pelaksanaan baru berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->dispatch('$refresh');
    }

    public function edit($id)
    {
        $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $venue->id;
        $this->name = $venue->name;
        $this->alamat = $venue->alamat ?? '';
        $this->latitude = $venue->latitude ?? '';
        $this->longitude = $venue->longitude ?? '';
        $this->google_maps_url = $venue->google_maps_url ?? '';
        $this->is_active = $venue->is_active;
        $this->ticket_kuota = $venue->ticket_kuota === null ? '' : (string) $venue->ticket_kuota;
        $this->ticket_price = $venue->ticket_price === null ? '' : (string) $venue->ticket_price;
    }

    /**
     * Buat token gerbang untuk tempat ini.
     *
     * Terpisah dari token event (`eventners.checkin_token`) dan disimpan di
     * kolomnya sendiri, supaya merotasi token event tidak ikut mematikan
     * gerbang yang sudah dibagikan ke petugas.
     */
    public function generateCheckinToken($id)
    {
        $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($id);

        if ($venue->checkin_token) {
            session()->flash('error', 'Tempat ini sudah punya token gerbang. Pakai tombol Rotasi bila ingin menggantinya.');
            return;
        }

        $venue->update(['checkin_token' => Str::random(40)]);
        session()->flash('success', 'Token gerbang ' . $venue->name . ' berhasil dibuat.');
    }

    public function regenerateCheckinToken($id)
    {
        $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $venue->update(['checkin_token' => Str::random(40)]);
        session()->flash('success', 'Token gerbang ' . $venue->name . ' berhasil dirotasi. Tautan lama tidak berlaku lagi.');
    }

    public function revokeCheckinToken($id)
    {
        $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $venue->update(['checkin_token' => null]);
        session()->flash('success', 'Token gerbang ' . $venue->name . ' dicabut. Tempat ini kembali memakai token event.');
    }

    /**
     * Hapus tempat. Ditolak kalau masih dipakai tingkat lomba — kalau dibiarkan
     * jalan, venue_id-nya jadi null diam-diam dan panitia tidak sadar tingkat
     * lombanya kehilangan lokasi.
     */
    public function delete($id)
    {
        $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($id);

        $used = CompetitionCategory::where('venue_id', $venue->id)->count();
        if ($used > 0) {
            session()->flash('error', 'Tidak bisa menghapus: tempat ini masih dipakai ' . $used . ' tingkat lomba. Pindahkan tingkatnya terlebih dahulu.');
            return;
        }

        $venue->delete();
        session()->flash('success', 'Tempat pelaksanaan berhasil dihapus.');
    }

    public function toggleActive($id)
    {
        $venue = EventnerVenue::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $venue->is_active = !$venue->is_active;
        $venue->save();
        session()->flash('success', 'Status tempat berhasil diubah.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'alamat', 'latitude', 'longitude', 'google_maps_url', 'ticket_kuota', 'ticket_price', 'isEditMode', 'editingId']);
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.eventner.venue.index')
            ->title('Tempat Pelaksanaan - ' . app_name());
    }
}

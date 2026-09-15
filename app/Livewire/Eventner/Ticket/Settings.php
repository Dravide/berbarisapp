<?php

namespace App\Livewire\Eventner\Ticket;

use App\Models\EventnerVenue;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Settings extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'ticket_settings';

    public $eventner;
    public $ticket_active = false;
    public $ticket_start = '';
    public $ticket_end = '';
    public $ticket_price = '';
    public $ticket_description = '';
    public $ticket_max_per_order = 10;

    public function mount()
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->ticket_active = (bool) $this->eventner->ticket_active;
        $this->ticket_start = $this->eventner->ticket_start ? \Carbon\Carbon::parse($this->eventner->ticket_start)->format('Y-m-d\TH:i') : '';
        $this->ticket_end = $this->eventner->ticket_end ? \Carbon\Carbon::parse($this->eventner->ticket_end)->format('Y-m-d\TH:i') : '';
        $this->ticket_price = $this->eventner->ticket_price ?? '';
        $this->ticket_description = $this->eventner->ticket_description ?? '';
        $this->ticket_max_per_order = $this->eventner->ticket_max_per_order ?? 10;
    }

    /**
     * Generate token check-in statis (sekali). Tidak auto-regenerate.
     * Pakai regenerateCheckinToken() untuk rotate manual.
     */
    public function generateCheckinAccess()
    {
        if ($this->eventner->checkin_token) {
            return;
        }
        $this->eventner->checkin_token = Str::random(40);
        $this->eventner->save();
        session()->flash('success', 'Akses check-in dibuat. URL di bawah statis — tidak berubah.');
    }

    public function regenerateCheckinToken()
    {
        $this->eventner->checkin_token = Str::random(40);
        $this->eventner->save();
        session()->flash('success', 'Token check-in dirotasi. URL lama tidak berlaku lagi.');
    }

    public function revokeCheckinAccess()
    {
        $this->eventner->checkin_token = null;
        $this->eventner->save();
        session()->flash('success', 'Akses check-in dicabut.');
    }

    /**
     * Token gerbang per tempat.
     *
     * Disimpan di `eventner_venues.checkin_token`, bukan di tabel baru, dan
     * sengaja tidak ikut ter-rotate saat token event dirotasi — link yang sudah
     * dibagikan ke petugas gerbang tidak boleh mati mendadak.
     */
    public function generateVenueToken($venueId)
    {
        $venue = $this->findVenue($venueId);

        if ($venue->checkin_token) {
            session()->flash('error', 'Tempat ini sudah punya token gerbang.');
            return;
        }

        $venue->update(['checkin_token' => Str::random(40)]);
        session()->flash('success', 'Token gerbang ' . $venue->name . ' dibuat.');
    }

    public function regenerateVenueToken($venueId)
    {
        $venue = $this->findVenue($venueId);
        $venue->update(['checkin_token' => Str::random(40)]);
        session()->flash('success', 'Token gerbang ' . $venue->name . ' dirotasi. Link lama tidak berlaku lagi.');
    }

    public function revokeVenueToken($venueId)
    {
        $venue = $this->findVenue($venueId);
        $venue->update(['checkin_token' => null]);
        session()->flash('success', 'Token gerbang ' . $venue->name . ' dicabut. Tempat ini memakai link scan event.');
    }

    /** venueId datang dari klien — selalu dipastikan milik eventner ini. */
    private function findVenue($venueId): EventnerVenue
    {
        return EventnerVenue::where('eventner_id', $this->eventner->id)->findOrFail($venueId);
    }

    public function save()
    {
        $this->validate([
            'ticket_price' => ($this->butuhHargaDefault() ? 'required' : 'nullable') . '|numeric|min:0',
            'ticket_max_per_order' => 'required|integer|min:1|max:100',
            'ticket_description' => 'nullable|string|max:1000',
        ], [
            'ticket_price.required' => 'Harga default wajib diisi selama masih ada tempat yang belum punya harga sendiri.',
            'ticket_price.min' => 'Harga tiket minimal 0.',
        ]);

        $this->eventner->update([
            'ticket_active' => $this->ticket_active,
            'ticket_start' => $this->ticket_start ?: null,
            'ticket_end' => $this->ticket_end ?: null,
            // Harga default TIDAK dikosongkan saat tiket dimatikan — tempat yang
            // belum punya harga sendiri memakainya sebagai fallback
            // (EventnerVenue::effectiveTicketPrice), jadi menghapusnya di sini
            // membuat tiket tempat itu diam-diam jadi gratis.
            'ticket_price' => $this->ticket_price === '' ? null : $this->ticket_price,
            'ticket_description' => $this->ticket_description ?: null,
            'ticket_max_per_order' => $this->ticket_max_per_order,
        ]);

        session()->flash('success', 'Pengaturan tiket berhasil disimpan.');
    }

    /**
     * Masih perlukah harga default diisi?
     *
     * Harga per tempat menang atas harga event (lihat
     * EventnerVenue::effectiveTicketPrice()). Jadi angka di halaman ini hanya
     * dipakai tempat yang belum punya harga sendiri — kalau semua tempat
     * sudah berharga, tidak ada yang membacanya dan tidak perlu dipaksa diisi.
     */
    public function butuhHargaDefault(): bool
    {
        if (! $this->ticket_active) {
            return false;
        }

        $dijual = $this->eventner->ticketVenues();

        return $dijual->isEmpty() || $dijual->contains(fn ($venue) => $venue->ticket_price === null);
    }

    /**
     * Harga tiket per tempat — sumber kebenaran harga yang benar-benar dibayar
     * pembeli. Ditampilkan di sini supaya penyelenggara tidak mengira angka di
     * halaman ini berlaku seragam padahal tempat bisa menimpanya.
     */
    #[Computed]
    public function hargaPerTempat()
    {
        return $this->eventner->activeVenues()->map(fn ($venue) => [
            'id' => $venue->id,
            'name' => $venue->name,
            'harga' => $venue->ticket_price,
            'dijual' => $venue->ticket_price !== null || $venue->ticket_kuota !== null,
        ]);
    }

    public function render()
    {
        return view('livewire.eventner.ticket.settings')
            ->title('Pengaturan Tiket - ' . $this->eventner->nama_event);
    }
}

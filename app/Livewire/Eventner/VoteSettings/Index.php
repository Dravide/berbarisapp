<?php

namespace App\Livewire\Eventner\VoteSettings;

use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Pengaturan Vote')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_settings';

    public $vote_active = false;
    public $vote_price = 1000;
    public $vote_max_per_order = 1000;
    public $vote_start = '';
    public $vote_end = '';

    public function mount()
    {
        $this->bootFeatureGate();
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);

        $this->vote_active = (bool) $eventner->vote_active;
        $this->vote_price = $eventner->vote_price ?? 1000;
        $this->vote_max_per_order = $eventner->vote_max_per_order ?? 1000;
        $this->vote_start = $eventner->vote_start ? \Carbon\Carbon::parse($eventner->vote_start)->format('Y-m-d\TH:i') : '';
        $this->vote_end = $eventner->vote_end ? \Carbon\Carbon::parse($eventner->vote_end)->format('Y-m-d\TH:i') : '';
    }

    /**
     * Batas atas jumlah vote per transaksi — wajib ada, bukan pilihan.
     *
     * Halaman vote publik dulu tidak punya batas atas sama sekali, sehingga
     * nominal QRIS ditentukan oleh angka yang diketik pengunjung. Batas ini
     * dibaca halaman publik lewat `eventners.vote_max_per_order`; mengosongkan
     * kolomnya di sini akan membuka celah yang sama lagi. Batas 100.000
     * menjaga agar nominal QRIS tetap masuk akal untuk satu transaksi QRIS.
     */
    public function rules(): array
    {
        return [
            'vote_price' => 'required|numeric|min:0',
            'vote_max_per_order' => 'required|integer|min:1|max:100000',
        ];
    }

    public function messages(): array
    {
        return [
            'vote_max_per_order.required' => 'Batas jumlah vote per transaksi wajib diisi.',
            'vote_max_per_order.min' => 'Batas jumlah vote minimal 1.',
            'vote_max_per_order.max' => 'Batas jumlah vote maksimal 100.000 — di atas itu nominal QRIS satu transaksi jadi tidak masuk akal.',
            'vote_price.required' => 'Harga per vote wajib diisi.',
        ];
    }

    public function save()
    {
        $this->validate();

        $eventner = Auth::user()->eventner;
        $eventner->update([
            'vote_active' => $this->vote_active,
            'vote_price' => $this->vote_price,
            'vote_max_per_order' => (int) $this->vote_max_per_order,
            'vote_start' => $this->vote_start ?: null,
            'vote_end' => $this->vote_end ?: null,
        ]);
        session()->flash('success', 'Pengaturan vote berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.eventner.vote-settings.index');
    }
}

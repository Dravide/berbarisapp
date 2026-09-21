<?php

namespace App\Livewire\Eventner\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ganti password akun eventner.
 *
 * Sebelumnya satu-satunya jalur ganti password adalah tombol reset di
 * /admin/users, jadi pemilik event harus menunggu admin platform. Halaman ini
 * menutup jalur itu.
 *
 * Bukan bagian dari Settings\Profile: profil event menyunting baris `eventners`
 * (nama acara, tanggal, tema), sedangkan halaman ini menyunting baris `users`.
 * Dua tabel berbeda, dua izin berbeda — admin boleh mengubah profil event mana
 * pun, tapi tidak boleh menulis password milik user lain.
 */
#[Layout('layouts.admin')]
class Password extends Component
{
    public string $password_lama = '';
    public string $password_baru = '';
    public string $password_baru_confirmation = '';

    /**
     * Izin diperiksa sekali di mount, bukan di save().
     *
     * Livewire menyusun ulang komponen dari payload request, jadi properti
     * publik bisa disetel ulang dari sisi klien. Kalau izinnya tidak dicek di
     * sini, user yang sudah terautentikasi bisa merakit payload dan menembak
     * save() terhadap dirinya sendiri tanpa pernah membuka halaman ini. Untuk
     * halaman ini dampaknya kecil (akun sendiri), tapi mount() yang menjaga
     * batasnya konsisten dengan Settings\Profile.
     */
    public function mount(): void
    {
        $eventner = Auth::user()->eventner;

        if (! $eventner) {
            abort(403);
        }

        Gate::authorize('manage', $eventner);
    }

    public function save(): void
    {
        // Aturan `current_password` memakai guard default, jadi password lama
        // tidak pernah perlu dibandingkan sendiri dengan Hash::check().
        $this->validate([
            'password_lama' => ['required', 'current_password'],
            'password_baru' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password_lama.required' => 'Password saat ini wajib diisi.',
            'password_lama.current_password' => 'Password saat ini tidak cocok.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password_baru.min' => 'Password baru minimal 8 karakter.',
        ]);

        // Cast 'hashed' di model User yang meng-hash nilainya — jangan
        // Hash::make() lagi di sini, hasilnya ter-hash dua kali.
        Auth::user()->update(['password' => $this->password_baru]);

        $this->reset('password_lama', 'password_baru', 'password_baru_confirmation');

        session()->flash('success', 'Password berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.eventner.settings.password')->title('Ganti Password - ' . app_name());
    }
}

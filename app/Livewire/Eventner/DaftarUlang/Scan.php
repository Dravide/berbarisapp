<?php

namespace App\Livewire\Eventner\DaftarUlang;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Scan QR kartu peserta untuk meja daftar ulang.
 *
 * QR peserta (registrations.qr_token, 8 karakter) sudah tercetak di kartu —
 * layar ini mengubahnya menjadi hadir tanpa mencari satu per satu di tabel
 * Daftar Ulang. Alur scan meniru Checkin\Scan (kamera html5-qrcode + input
 * manual), tetapi hasilnya kartu inline: panitia meja bekerja serial di satu
 * layar, kartu terlihat terus tanpa modal.
 *
 * Hasil lookup berlapis:
 *   ready        peserta sah, belum hadir → tombol "Tandai Hadir"
 *   success      baru saja ditandai hadir
 *   already      sudah hadir sebelumnya → tombol "Batalkan"
 *   wrong_event  kartu milik event lain (token sah, beda eventner)
 *   not_found    token tak dikenal
 *
 * Bukan toggle otomatis saat scan: salah baca kamera sesama peserta saat
 * antrean cepat terlalu mahal untuk dibatalkan diam-diam — penandaan selalu
 * lewat konfirmasi eksplisit.
 */
#[Layout('layouts.admin')]
#[Title('Scan Daftar Ulang')]
class Scan extends Component
{
    use MelaporKePengguna;

    public $eventner;

    /** Barcode yang di-emit scanner — listener Livewire memanggil lookup(). */
    public string $scannedCode = '';

    /** Input manual fallback bila kamera tak tersedia. */
    public string $manualCode = '';

    public ?array $result = null;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (! $this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }
    }

    /**
     * Cari peserta dari token QR (dipanggil scanner + input manual).
     * Tidak langsung menandai hadir — hanya menampilkan kartu hasil.
     */
    public function lookup(?string $code = null)
    {
        $code = strtoupper(trim($code ?? $this->manualCode));
        $this->scannedCode = '';
        $this->manualCode = '';

        if ($code === '') {
            return;
        }

        // Token unik global — cek dulu pemiliknya, lalu bandingkan event,
        // supaya kartu event lain punya pesan tersendiri (bukan "tak dikenal").
        $registration = Registration::where('qr_token', $code)->first();

        if (! $registration) {
            $this->result = ['kind' => 'not_found', 'code' => $code];
            return;
        }

        if ((int) $registration->eventner_id !== (int) $this->eventner->id) {
            $this->result = ['kind' => 'wrong_event', 'code' => $code];
            return;
        }

        $registration->loadMissing('competitionCategory.parent');

        $this->result = $registration->daftar_ulang_at
            ? ['kind' => 'already', 'registration' => $registration]
            : ['kind' => 'ready', 'registration' => $registration];
    }

    public function tandaiHadir(int $registrationId)
    {
        $registration = Registration::where('eventner_id', $this->eventner->id)->findOrFail($registrationId);

        // Idempoten: dua petugas memukul tombol hampir bersamaan tidak
        // menimpa jam kedatangan yang pertama.
        if (! $registration->daftar_ulang_at) {
            $registration->update(['daftar_ulang_at' => now()]);
        }

        $registration->loadMissing('competitionCategory.parent');
        $this->result = ['kind' => 'success', 'registration' => $registration];
        $this->toast('Peserta ditandai hadir.');
    }

    public function batalkan(int $registrationId)
    {
        $registration = Registration::where('eventner_id', $this->eventner->id)->findOrFail($registrationId);

        $registration->update(['daftar_ulang_at' => null]);

        $this->result = ['kind' => 'ready', 'registration' => $registration];
        $this->toast('Kehadiran dibatalkan.', 'error');
    }

    public function render()
    {
        return view('livewire.eventner.daftar-ulang.scan');
    }
}

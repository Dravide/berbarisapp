<?php

namespace App\Livewire\Public\ESertifikat;

use App\Services\PencarianSertifikat;
use App\Support\AksesUnduhSertifikat;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Beranda sekaligus hasil pencarian e-sertifikat.
 *
 * Satu komponen untuk dua keadaan (NISN kosong = beranda, terisi = hasil),
 * bukan dua rute. Alasannya bukan penghematan berkas: hasil pencarian harus
 * tetap ada saat halaman disegarkan atau tautannya dikirim ke diri sendiri
 * lewat WhatsApp, dan itu hanya bisa kalau hasilnya punya URL sendiri.
 *
 * NISN masuk query string supaya alamatnya bisa dibagi. Ini NISN, bukan token:
 * nomornya memang sudah diketahui pemiliknya, dan yang terbuka karenanya hanya
 * nama, sekolah, dan gelar — data yang toh diumumkan di papan skor dan halaman
 * /hasil yang sudah publik.
 *
 * Hasilnya sendiri TIDAK disimpan sebagai properti publik, melainkan dihitung
 * ulang dari `$nisn` lewat #[Computed]. Menyimpannya berarti seluruh daftar
 * sertifikat ikut diserialisasi ke snapshot Livewire di setiap permintaan —
 * satu halaman yang isinya bisa dibaca ulang dari satu parameter tidak perlu
 * dibawa-bawa.
 */
#[Layout('layouts.esertifikat')]
class Cari extends Component
{
    /** NISN yang sedang dicari — kosong berarti beranda. */
    public string $nisn = '';

    /**
     * NISN = query string. `except` membuangnya dari URL saat kosong supaya
     * beranda tetap beralamat bersih, bukan "/?nisn=".
     */
    protected $queryString = [
        'nisn' => ['except' => ''],
    ];

    /**
     * NISN yang formatnya sah untuk dicari.
     *
     * Normalisasi dilakukan sebelum diperiksa: NISN kerap disalin dari rapor
     * atau kartu pelajar dalam bentuk "007 1234 5678", dan orang yang menyalin
     * persis seperti yang tercetak tidak boleh ditolak.
     */
    private function nisnSah(): string
    {
        $bersih = PencarianSertifikat::normalkan($this->nisn);

        return PencarianSertifikat::formatSah($bersih) ? $bersih : '';
    }

    public function mount()
    {
        // Segarkan halaman ber-NISN = ulangi pencariannya, bukan tampilkan
        // layar kosong. Izin unduhnya ikut dipasang lagi di sini — tanpa itu,
        // tautan hasil yang dibuka di tab baru menampilkan daftarnya tapi
        // setiap tombol unduhnya ditolak.
        if ($bersih = $this->nisnSah()) {
            $this->nisn = $bersih;
            $this->izinkanUnduh($bersih);
        }
    }

    public function cari()
    {
        $this->resetErrorBag('nisn');

        $bersih = PencarianSertifikat::normalkan($this->nisn);

        if ($bersih === '') {
            $this->addError('nisn', 'NISN wajib diisi.');

            return;
        }

        if (! PencarianSertifikat::formatSah($bersih)) {
            $this->addError(
                'nisn',
                'NISN terdiri dari ' . PencarianSertifikat::PANJANG_NISN . ' angka. Yang Anda masukkan ' . strlen($bersih) . ' angka.'
            );

            return;
        }

        // Ditulis ulang dalam bentuk bersih supaya URL yang dibagikan memuat
        // angka saja, dan kotak isiannya ikut rapi setelah dikirim.
        $this->nisn = $bersih;

        $this->izinkanUnduh($bersih);
    }

    /** Buang NISN, hasil, dan izin unduh — kembali ke beranda. */
    public function ulangi()
    {
        $this->nisn = '';
        $this->resetErrorBag('nisn');
        AksesUnduhSertifikat::lupakan();
        unset($this->hasil);
    }

    /**
     * Catat registrasi mana saja yang boleh diunduh pada sesi ini.
     *
     * Diambil dari seluruh id di hasil — termasuk baris yang belum terbit.
     * Gerbang terbitnya diperiksa ulang di controller, dan menyaringnya di
     * sini berarti baris yang baru saja dibuka panitia tetap ditolak sampai
     * pencariannya diulang.
     */
    private function izinkanUnduh(string $nisn): void
    {
        $hasil = app(PencarianSertifikat::class)->cari($nisn);

        if ($hasil['jumlah_baris'] === 0) {
            // Sesi yang masih memegang daftar NISN lain sambil menampilkan
            // hasil kosong adalah pintu yang tidak perlu tetap terbuka.
            AksesUnduhSertifikat::lupakan();

            return;
        }

        AksesUnduhSertifikat::berikan($this->idRegistrasi($hasil), $nisn);
    }

    /**
     * Hasil pencarian NISN yang sedang aktif, null bila belum ada NISN yang
     * sah atau NISN-nya tidak ditemukan.
     *
     * @return array{nisn:string, nama:?string, sekolah:?string, jumlah_event:int, jumlah_baris:int, grup:array}|null
     */
    #[Computed]
    public function hasil(): ?array
    {
        $bersih = $this->nisnSah();

        if ($bersih === '') {
            return null;
        }

        return app(PencarianSertifikat::class)->cari($bersih);
    }

    /** @return array<int, int> */
    private function idRegistrasi(array $hasil): array
    {
        $ids = [];

        foreach ($hasil['grup'] as $grup) {
            foreach ($grup['baris'] as $baris) {
                $ids[] = $baris['registration_id'];
            }
        }

        return $ids;
    }

    public function render()
    {
        $hasil = $this->hasil;

        return view('livewire.public.e-sertifikat.cari')
            ->title($hasil && $hasil['jumlah_baris'] > 0
                ? 'Sertifikat ' . ($hasil['nama'] ?? $this->nisnSah())
                : 'Unduh E-Sertifikat');
    }
}

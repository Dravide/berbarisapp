<?php

namespace App\Livewire\Admin;

use App\Models\EoInquiry;
use App\Models\Setting;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar pengajuan event dari formulir /ajukan di subdomain EO.
 *
 * Halaman ini yang membuat janji "kami balas lewat WhatsApp pada hari kerja
 * berikutnya" bisa ditepati. Tanpa daftar yang diperiksa tiap hari, pengajuan
 * hanya menumpuk di tabel dan kalimat itu jadi bohong.
 *
 * Satu daftar, bukan dua: yang perlu dikerjakan admin adalah pengajuan berstatus
 * `baru`; sisanya tetap tampil lewat penyaring status. Urutannya yang terbaru
 * dulu karena pengajuan lama yang belum dibalas justru yang paling mendesak.
 *
 * Kontak tim EO (email/WhatsApp) disunting dari halaman ini juga: ia setelan
 * halaman EO (`eo_contact`), bukan setelan laman utama, dan menaruhnya di sini
 * membuat orang yang menjawab pengajuan bisa langsung memperbaiki nomor yang
 * dilihat calon klien.
 */
#[Layout('layouts.admin')]
class EoInquiryIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $saring = 'baru';

    public string $cari = '';

    /** Pengajuan yang sedang dibuka di panel detail (null = tertutup). */
    public ?int $detailId = null;

    public string $catatan_admin = '';

    // Setelan halaman EO
    public string $eo_nama = '';
    public string $eo_email = '';
    public string $eo_phone = '';
    public string $eo_instagram = '';

    public function mount(): void
    {
        $this->eo_nama = (string) Setting::get('eo_nama', '');

        $kontak = json_decode(Setting::get('eo_contact') ?: '{}', true) ?: [];
        $this->eo_email = (string) ($kontak['email'] ?? '');
        $this->eo_phone = (string) ($kontak['phone'] ?? '');
        $this->eo_instagram = (string) ($kontak['instagram'] ?? '');
    }

    public function updatingSaring(): void
    {
        $this->resetPage();
    }

    public function updatingCari(): void
    {
        $this->resetPage();
    }

    public function lihat(int $id): void
    {
        $pengajuan = EoInquiry::findOrFail($id);

        $this->detailId = $pengajuan->id;
        $this->catatan_admin = (string) ($pengajuan->catatan_admin ?? '');
    }

    public function tutup(): void
    {
        $this->detailId = null;
        $this->catatan_admin = '';
    }

    /**
     * Ubah status pengajuan. `dibalas_at` diisi saat pertama kali ditandai
     * dibalas, supaya bisa dipakai menghitung berapa lama pengajuan menunggu.
     */
    public function ubahStatus(int $id, string $status): void
    {
        if (! array_key_exists($status, EoInquiry::STATUS)) {
            return;
        }

        $pengajuan = EoInquiry::findOrFail($id);

        $pengajuan->status = $status;

        if ($status === 'dibalas' && ! $pengajuan->dibalas_at) {
            $pengajuan->dibalas_at = now();
        }

        $pengajuan->save();

        $this->dispatch('toast', message: 'Status diubah menjadi "' . EoInquiry::STATUS[$status] . '".', type: 'success');
    }

    public function simpanCatatan(): void
    {
        if (! $this->detailId) {
            return;
        }

        $this->validate(['catatan_admin' => 'nullable|string|max:2000']);

        EoInquiry::whereKey($this->detailId)->update([
            'catatan_admin' => $this->catatan_admin !== '' ? $this->catatan_admin : null,
        ]);

        $this->dispatch('toast', message: 'Catatan admin disimpan.', type: 'success');
    }

    public function hapus(int $id): void
    {
        EoInquiry::findOrFail($id)->delete();

        if ($this->detailId === $id) {
            $this->tutup();
        }

        $this->dispatch('toast', message: 'Pengajuan dihapus.', type: 'success');
    }

    public function simpanKontak(): void
    {
        $this->validate([
            'eo_nama' => 'nullable|string|max:100',
            'eo_email' => 'nullable|email|max:255',
            'eo_phone' => 'nullable|string|max:32',
            'eo_instagram' => 'nullable|url|max:255',
        ], [
            'eo_email.email' => 'Alamat surel tidak dikenali.',
            'eo_instagram.url' => 'Tulis tautan lengkap, mis. https://instagram.com/akun.',
        ]);

        Setting::set('eo_nama', trim($this->eo_nama));

        Setting::set('eo_contact', json_encode([
            'email' => trim($this->eo_email),
            'phone' => trim($this->eo_phone),
            'instagram' => trim($this->eo_instagram),
        ], JSON_UNESCAPED_SLASHES));

        $this->dispatch('toast', message: 'Setelan halaman EO disimpan.', type: 'success');
    }

    public function render()
    {
        $query = EoInquiry::query()->latest('id');

        if ($this->saring !== 'semua') {
            $query->where('status', $this->saring);
        }

        if (trim($this->cari) !== '') {
            $kata = '%' . trim($this->cari) . '%';
            $query->where(function ($q) use ($kata) {
                $q->where('instansi', 'like', $kata)
                    ->orWhere('penanggung_jawab', 'like', $kata)
                    ->orWhere('whatsapp', 'like', $kata)
                    ->orWhere('jenis_lomba', 'like', $kata)
                    ->orWhere('lokasi', 'like', $kata);
            });
        }

        $hitung = EoInquiry::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();

        return view('livewire.admin.eo-inquiry-index', [
            'pengajuan' => $query->paginate(20),
            'hitung' => $hitung,
            'detail' => $this->detailId ? EoInquiry::find($this->detailId) : null,
            'pekerjaanPilihan' => EoProfile::pekerjaanPilihan(),
        ])->title('Pengajuan EO - ' . app_name());
    }
}

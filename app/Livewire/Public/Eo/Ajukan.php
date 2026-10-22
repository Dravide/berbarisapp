<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Models\EoInquiry;
use App\Models\Setting;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /ajukan — formulir pengajuan event, plus apa yang terjadi sesudahnya.
 *
 * Isiannya delapan, dan yang wajib hanya empat: instansi, penanggung jawab,
 * nomor WhatsApp, jenis lomba. Perkiraan tanggal dan jumlah peserta boleh
 * dikira-kira — formulir yang menuntut angka pasti membuat orang menutup
 * halaman dan menelepon, dan telepon tidak tercatat di mana pun.
 *
 * Pengajuannya disimpan ke `eo_inquiries`, bukan hanya dikirim sebagai pesan:
 * halaman ini menjanjikan "kami balas hari kerja berikutnya", dan janji itu
 * hanya bisa ditepati kalau pengajuannya punya tempat yang diperiksa tiap hari.
 *
 * Bukan pendaftaran peserta: jangan disambungkan ke `Registration` atau magic
 * link. Dua hal ini punya penerima, tabel, dan masa berlaku yang berbeda.
 */
#[Layout('layouts.eo')]
class Ajukan extends Component
{
    use MelaporKePengguna;
    use MenyiapkanHalamanEo;

    public string $instansi = '';
    public string $penanggung_jawab = '';
    public string $whatsapp = '';
    public string $email = '';
    public string $jenis_lomba = '';
    public string $tanggal = '';
    public string $lokasi = '';
    public string $jumlah_peserta = '';
    public string $catatan = '';

    /** @var array<int, string> */
    public array $pekerjaan = [];

    public bool $terkirim = false;

    public function mount(): void
    {
        // Jenis lomba pertama dipilih lebih dulu supaya daftar centang
        // pekerjaan tidak tampil di atas kolom kosong.
        $this->jenis_lomba = EoProfile::jenisLomba()[0] ?? '';
    }

    protected function rules(): array
    {
        return [
            'instansi' => 'required|string|max:255',
            'penanggung_jawab' => 'required|string|max:255',
            'whatsapp' => ['required', 'string', 'max:32', 'regex:/^(\+?62|0)8[0-9]{7,14}$/'],
            'email' => 'nullable|email|max:255',
            'jenis_lomba' => 'required|string|max:100',
            'tanggal' => 'nullable|date',
            'lokasi' => 'nullable|string|max:255',
            'jumlah_peserta' => 'nullable|integer|min:1|max:100000',
            'catatan' => 'nullable|string|max:2000',
            'pekerjaan' => 'array',
            'pekerjaan.*' => 'string|max:100',
        ];
    }

    protected function messages(): array
    {
        return [
            'instansi.required' => 'Nama instansi atau sekolah wajib diisi.',
            'penanggung_jawab.required' => 'Nama penanggung jawab wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi — balasan kami lewat sana.',
            'whatsapp.regex' => 'Tulis nomor WhatsApp Indonesia, mis. 0812xxxxxxx atau +62812xxxxxxx.',
            'email.email' => 'Alamat surel tidak dikenali.',
            'jumlah_peserta.integer' => 'Perkiraan jumlah peserta ditulis angka saja.',
        ];
    }

    public function kirim(): void
    {
        $data = $this->validate();

        // Nomor disimpan dalam bentuk tunggal (08…) supaya admin yang
        // menyalinnya ke WhatsApp tidak perlu menebak formatnya.
        $data['whatsapp'] = normalisasi_no_hp($data['whatsapp']);
        $data['pekerjaan'] = array_values($this->pekerjaan);
        $data['status'] = 'baru';

        // Kolom opsional yang dikosongkan harus jadi NULL, bukan ''. Kolom
        // `tanggal` ber-cast date: string kosong tersimpan sebagai '' lalu
        // dibaca balik sebagai tanggal hari ini — pengajuan tanpa tanggal
        // mendadak punya tanggal, dan itu salah di daftar admin.
        foreach (['email', 'tanggal', 'lokasi', 'jumlah_peserta', 'catatan'] as $kolom) {
            if (($data[$kolom] ?? null) === '' || $data[$kolom] === null) {
                $data[$kolom] = null;
            }
        }

        EoInquiry::create($data);

        $this->terkirim = true;

        // Reset isian supaya pengajuan berikutnya tidak mewarisi data yang
        // salah, tapi jangan sembunyikan pesan berhasilnya.
        $this->reset(['instansi', 'penanggung_jawab', 'whatsapp', 'email', 'tanggal', 'lokasi', 'jumlah_peserta', 'catatan', 'pekerjaan']);
        $this->jenis_lomba = EoProfile::jenisLomba()[0] ?? '';
        $this->resetValidation();

        $this->toast('Pengajuan terkirim. Kami balas lewat WhatsApp pada hari kerja berikutnya.');
    }

    public function render()
    {
        $kontak = json_decode(Setting::get('eo_contact') ?: '{}', true) ?: [];

        return view('livewire.public.eo.ajukan', array_merge($this->dataEo(), [
            'formulir' => EoProfile::formulir(),
            'jenisLomba' => EoProfile::jenisLomba(),
            'pekerjaanPilihan' => EoProfile::pekerjaanPilihan(),
            'kontak' => $kontak,
            'waUrl' => wa_me_url($kontak['phone'] ?? null),
        ]))
            ->layoutData($this->dataEo())
            ->title('Ajukan Event — ' . self::namaEo());
    }
}

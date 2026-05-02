<?php

namespace App\Livewire\Public\MagicLink;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Registration as RegistrationModel;
use App\Models\Participant;

#[Layout('layouts.frontend')]
#[Title('Formulir Pendaftaran - BARIS APP')]
class Registration extends Component
{
    use WithFileUploads;

    public $token;
    public $registration;

    public $logoSekolah;
    public $suratTugas;
    public $fotoPelatih;
    public $buktiPendaftaran;
    public $dantonNama = '';
    public $dantonNisn = '';
    public $dantonFoto;

    public $participants = [];

    public function mount($token)
    {
        $this->registration = RegistrationModel::with(['eventner', 'competitionCategory', 'participants'])
            ->where('magic_token', $token)
            ->firstOrFail();

        $this->token = $token;
        $this->dantonNama = $this->registration->danton_nama ?? '';
        $this->dantonNisn = $this->registration->danton_nisn ?? '';

        // Load existing participants or seed 12 empty slots
        if ($this->registration->participants->count() > 0) {
            foreach ($this->registration->participants as $p) {
                $this->participants[] = ['nama' => $p->nama, 'nisn' => $p->nisn ?? '', 'foto' => null, 'existing_foto' => $p->foto];
            }
        } else {
            for ($i = 0; $i < 12; $i++) {
                $this->participants[] = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
            }
        }
    }

    public function addParticipant()
    {
        if ($this->registration->is_finalized) return;
        $this->participants[] = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
    }

    public function removeParticipant($index)
    {
        if ($this->registration->is_finalized) return;
        if (count($this->participants) > 1) {
            unset($this->participants[$index]);
            $this->participants = array_values($this->participants);
        }
    }

    public function submit($isFinal = false)
    {
        if ($this->registration->is_finalized) return;

        $rules = [
            'logoSekolah' => 'nullable|image|max:3072',
            'suratTugas' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'fotoPelatih' => 'nullable|image|max:3072',
            'buktiPendaftaran' => 'nullable|image|max:3072',
            'dantonNama' => 'required|string|max:255',
            'dantonNisn' => 'nullable|string|max:20',
            'dantonFoto' => 'nullable|image|max:3072',
            'participants.*.nama' => 'required|string|max:255',
            'participants.*.nisn' => 'nullable|string|max:20',
            'participants.*.foto' => 'nullable|image|max:3072',
        ];

        if ($isFinal) {
            $rules['buktiPendaftaran'] = 'required|image|max:3072';
            $rules['logoSekolah'] = 'required_without:registration.logo_sekolah|image|max:3072';
            $rules['suratTugas'] = 'required_without:registration.surat_tugas|file|mimes:pdf,jpg,png|max:5120';
        }

        $this->validate($rules, [
            'logoSekolah.image' => 'File Logo Sekolah harus berupa gambar (JPG, PNG).',
            'logoSekolah.required_without' => 'Logo Sekolah wajib diunggah untuk finalisasi.',
            'suratTugas.file' => 'File Surat Tugas harus berupa dokumen yang valid.',
            'suratTugas.mimes' => 'File Surat Tugas harus berformat PDF, JPG, atau PNG.',
            'suratTugas.required_without' => 'Surat Tugas wajib diunggah untuk finalisasi.',
            'fotoPelatih.image' => 'Foto pelatih harus berupa gambar.',
            'buktiPendaftaran.image' => 'Kwitansi harus berupa gambar.',
            'buktiPendaftaran.required' => 'Kwitansi pendaftaran wajib diunggah untuk finalisasi.',
            'dantonNama.required' => 'Nama Danton wajib diisi.',
            'dantonNisn.max' => 'NISN Danton maksimal 20 karakter.',
            'dantonFoto.image' => 'Foto Danton harus berupa gambar.',
            'participants.*.nama.required' => 'Nama peserta wajib diisi.',
            'participants.*.foto.image' => 'Foto peserta harus berupa gambar.',
        ]);

        $reg = $this->registration;

        // Upload logo sekolah
        if ($this->logoSekolah) {
            $reg->logo_sekolah = $this->logoSekolah->store('registrations/logos', 'public');
        }

        // Upload surat tugas
        if ($this->suratTugas) {
            $reg->surat_tugas = $this->suratTugas->store('registrations/surat', 'public');
        }

        // Upload foto pelatih
        if ($this->fotoPelatih) {
            $reg->foto_pelatih = $this->fotoPelatih->store('registrations/pelatih', 'public');
        }

        // Upload kwitansi
        if ($this->buktiPendaftaran) {
            $reg->bukti_pendaftaran = $this->buktiPendaftaran->store('registrations/kwitansi', 'public');
        }

        // Save danton
        $reg->danton_nama = $this->dantonNama;
        $reg->danton_nisn = $this->dantonNisn;
        if ($this->dantonFoto) {
            $reg->danton_foto = $this->dantonFoto->store('registrations/danton', 'public');
        }

        if ($isFinal) {
            $reg->is_finalized = true;
        }

        $reg->save();

        // Delete old participants and re-create
        $reg->participants()->delete();

        foreach ($this->participants as $index => $p) {
            $fotoPath = null;
            if (isset($p['foto']) && $p['foto']) {
                $fotoPath = $p['foto']->store('registrations/peserta', 'public');
            } elseif (isset($p['existing_foto']) && $p['existing_foto']) {
                $fotoPath = $p['existing_foto'];
            }

            Participant::create([
                'registration_id' => $reg->id,
                'nama' => $p['nama'],
                'nisn' => $p['nisn'] ?? null,
                'foto' => $fotoPath,
            ]);
        }

        if ($isFinal) {
            session()->flash('success', 'Data Pendaftaran telah BERHASIL DIKIRIM dan DIKUNCI. Terima kasih!');
        } else {
            session()->flash('success', 'Draft Pendaftaran berhasil disimpan! Anda masih bisa melengkapi data sebelum dikirim final.');
        }

        // Reload data
        $this->registration = $reg->fresh(['participants']);
        $this->logoSekolah = null;
        $this->suratTugas = null;
        $this->fotoPelatih = null;
        $this->buktiPendaftaran = null;
        $this->dantonFoto = null;
    }

    public function render()
    {
        return view('livewire.public.magic-link.registration')
            ->layoutData(['eventner' => $this->registration->eventner]);
    }
}

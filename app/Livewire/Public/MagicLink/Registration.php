<?php

namespace App\Livewire\Public\MagicLink;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Registration as RegistrationModel;
use App\Models\Participant;

#[Layout('layouts.frontend')]
class Registration extends Component
{
    use WithFileUploads;

    public $token;
    public $portalUrl;
    public $registration;
    public $siblingRegistrations;

    // Active tab for managing which registration
    public $activeRegId;

    // Form fields
    public $logoSekolah;
    public $suratTugas;
    public $fotoPelatih;
    public $buktiPendaftaran;
    public $dantonNama = '';
    public $dantonNisn = '';
    public $dantonFoto;
    public $namaPelatih = '';

    public $paymentProof;
    public $participants = [];

    // Draft state cache per registration (survives tab switching)
    public array $draftCache = [];

    public function mount($token)
    {
        $this->portalUrl = url()->current();
        $this->registration = RegistrationModel::with(['eventner', 'competitionCategory', 'participants'])
            ->where('magic_token', $token)
            ->firstOrFail();

        $this->token = $token;
        $this->activeRegId = $this->registration->id;

        // Load all registrations from the same school (same NPSN + same event)
        $this->siblingRegistrations = RegistrationModel::with(['competitionCategory', 'participants'])
            ->where('eventner_id', $this->registration->eventner_id)
            ->where('npsn', $this->registration->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        $this->loadFormData();
    }

    public function switchRegistration($regId)
    {
        if ($regId == $this->activeRegId) return;

        // Cache current draft state before switching away
        $this->draftCache[$this->activeRegId] = [
            'participants' => $this->participants,
            'dantonNama'   => $this->dantonNama,
            'dantonNisn'   => $this->dantonNisn,
            'namaPelatih'  => $this->namaPelatih,
        ];

        $reg = $this->siblingRegistrations->firstWhere('id', $regId);
        if (!$reg) return;

        $this->activeRegId = $regId;
        $this->registration = $reg;

        // Restore cached draft if present, otherwise load from DB
        if (isset($this->draftCache[$regId])) {
            $c = $this->draftCache[$regId];
            $this->participants = $c['participants'];
            $this->dantonNama   = $c['dantonNama'];
            $this->dantonNisn   = $c['dantonNisn'];
            $this->namaPelatih  = $c['namaPelatih'];
        } else {
            $this->loadFormData();
        }
    }

    private function loadFormData()
    {
        $this->dantonNama = $this->registration->danton_nama ?? '';
        $this->dantonNisn = $this->registration->danton_nisn ?? '';
        $this->namaPelatih = $this->registration->nama_pelatih ?? '';

        if ($this->registration->participants->count() > 0) {
            $this->participants = [];
            foreach ($this->registration->participants as $p) {
                $this->participants[] = ['nama' => $p->nama, 'nisn' => $p->nisn ?? '', 'foto' => null, 'existing_foto' => $p->foto];
            }
        } else {
            $this->participants = [];
            for ($i = 0; $i < 12; $i++) {
                $this->participants[] = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
            }
        }

        // Reset file uploads
        $this->logoSekolah = null;
        $this->suratTugas = null;
        $this->fotoPelatih = null;
        $this->buktiPendaftaran = null;
        $this->dantonFoto = null;
        $this->paymentProof = null;
    }

    public function submitPaymentProof()
    {
        $this->validate([
            'paymentProof' => 'required|image|max:5120',
        ]);

        $path = $this->paymentProof->store('registrations/payment', 'public');
        $this->registration->payment_proof = $path;
        $this->registration->payment_status = 'pending_verification';
        $this->registration->save();

        $this->paymentProof = null;
        $this->registration = $this->registration->fresh();

        session()->flash('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi panitia.');
    }

    public function addParticipant()
    {
        if ($this->registration->status_berkas === 'Terverifikasi') return;
        $this->participants[] = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
    }

    public function removeParticipant($index)
    {
        if ($this->registration->status_berkas === 'Terverifikasi') return;
        if (count($this->participants) > 1) {
            unset($this->participants[$index]);
            $this->participants = array_values($this->participants);
        }
    }

    public function confirm()
    {
        $reg = $this->registration;

        if ($reg->status_berkas !== 'booking') return;

        // Only require TM check if registration is still in booking mode
        if (($reg->eventner->registration_status ?? 'open') === 'booking') {
            if ($reg->eventner->technical_meeting && now()->lt($reg->eventner->technical_meeting)) {
                session()->flash('error', 'Konfirmasi hanya bisa dilakukan setelah Technical Meeting (' . \Carbon\Carbon::parse($reg->eventner->technical_meeting)->translatedFormat('d F Y, H:i') . ').');
                return;
            }
        }

        $this->validate([
            'namaPelatih' => 'required|string|max:255',
            'dantonNama' => 'required|string|max:255',
            'participants.*.nama' => 'required|string|max:255',
        ], [
            'namaPelatih.required' => 'Nama pelatih wajib diisi.',
            'dantonNama.required' => 'Nama danton wajib diisi.',
            'participants.*.nama.required' => 'Nama peserta wajib diisi.',
        ]);

        $reg->nama_pelatih = strip_tags($this->namaPelatih);
        $reg->danton_nama = strip_tags($this->dantonNama);
        $reg->danton_nisn = strip_tags($this->dantonNisn);
        $reg->status_berkas = 'confirmed';
        $reg->save();

        $this->saveParticipants();

        // Refresh
        $this->registration = $reg->fresh(['participants']);
        $this->siblingRegistrations = RegistrationModel::with(['competitionCategory', 'participants'])
            ->where('eventner_id', $this->registration->eventner_id)
            ->where('npsn', $this->registration->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        // Re-sync form state with what was just saved
        $this->loadFormData();
        $this->draftCache[$this->activeRegId] = [
            'participants' => $this->participants,
            'dantonNama'   => $this->dantonNama,
            'dantonNisn'   => $this->dantonNisn,
            'namaPelatih'  => $this->namaPelatih,
        ];

        session()->flash('success', 'Konfirmasi berhasil! Data pasukan telah dikirim untuk diverifikasi panitia.');
    }

    public function submit($isFinal = false)
    {
        $reg = $this->registration;

        if ($reg->status_berkas === 'Terverifikasi') return;

        // Check registration status of the event
        if (($reg->eventner->registration_status ?? 'open') == 'booking') {
            session()->flash('error', 'Saat ini hanya diperbolehkan booking slot. Pengisian data pasukan akan dibuka setelah masa pendaftaran dibuka secara resmi.');
            return;
        }

        if (($reg->eventner->registration_status ?? 'open') == 'closed' && $reg->eventner->hasEventDayStarted()) {
            session()->flash('error', 'Hari-H pelaksanaan telah tiba. Perubahan data tidak lagi diperbolehkan.');
            return;
        }

        $rules = [
            'logoSekolah' => 'nullable|image|max:3072',
            'suratTugas' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'fotoPelatih' => 'nullable|image|max:3072',
            'dantonNama' => 'required|string|max:255',
            'dantonNisn' => 'nullable|string|max:20',
            'dantonFoto' => 'nullable|image|max:3072',
            'namaPelatih' => 'required|string|max:255',
            'participants.*.nama' => 'required|string|max:255',
            'participants.*.nisn' => 'nullable|string|max:20',
            'participants.*.foto' => 'nullable|image|max:3072',
        ];

        if ($isFinal) {
            if ($reg->eventner->surat_tugas_required) {
                $rules['suratTugas'] = 'required_without:registration.surat_tugas|file|mimes:pdf,jpg,png|max:5120';
            }
        }

        $this->validate($rules, [
            'logoSekolah.image' => 'File Logo Sekolah harus berupa gambar.',
            'suratTugas.file' => 'File Surat Tugas harus berupa dokumen.',
            'suratTugas.mimes' => 'File Surat Tugas harus PDF, JPG, atau PNG.',
            'suratTugas.required_without' => 'Surat Tugas wajib diunggah.',
            'buktiPendaftaran.image' => 'File Kwitansi harus berupa gambar.',
            'buktiPendaftaran.required_without' => 'Kwitansi pendaftaran wajib diunggah.',
            'fotoPelatih.image' => 'Foto pelatih harus berupa gambar.',
            'dantonNama.required' => 'Nama Danton wajib diisi.',
            'namaPelatih.required' => 'Nama pelatih wajib diisi.',
            'participants.*.nama.required' => 'Nama peserta wajib diisi.',
            'participants.*.foto.image' => 'Foto peserta harus berupa gambar.',
        ]);

        if ($this->logoSekolah) {
            $reg->logo_sekolah = $this->logoSekolah->store('registrations/logos', 'public');
        }
        if ($this->suratTugas) {
            $reg->surat_tugas = $this->suratTugas->store('registrations/surat', 'public');
        }
        if ($this->fotoPelatih) {
            $reg->foto_pelatih = $this->fotoPelatih->store('registrations/pelatih', 'public');
        }

        $reg->nama_pelatih = strip_tags($this->namaPelatih);
        $reg->danton_nama = strip_tags($this->dantonNama);
        $reg->danton_nisn = strip_tags($this->dantonNisn);
        if ($this->dantonFoto) {
            $reg->danton_foto = $this->dantonFoto->store('registrations/danton', 'public');
        }

        if ($isFinal) {
            $reg->is_finalized = true;
            if ($reg->status_berkas === 'booking') {
                $reg->status_berkas = 'confirmed';
            }
        }

        $reg->save();
        $this->saveParticipants();

        // Refresh
        $this->registration = $reg->fresh(['participants']);
        $this->siblingRegistrations = RegistrationModel::with(['competitionCategory', 'participants'])
            ->where('eventner_id', $this->registration->eventner_id)
            ->where('npsn', $this->registration->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        $this->logoSekolah = null;
        $this->suratTugas = null;
        $this->fotoPelatih = null;
        $this->buktiPendaftaran = null;
        $this->dantonFoto = null;

        // Re-sync form state with what was just saved (staged fotos consumed, existing_foto updated)
        $this->loadFormData();
        $this->draftCache[$this->activeRegId] = [
            'participants' => $this->participants,
            'dantonNama'   => $this->dantonNama,
            'dantonNisn'   => $this->dantonNisn,
            'namaPelatih'  => $this->namaPelatih,
        ];

        session()->flash('success', $isFinal
            ? 'Data berhasil difinalisasi dan dikirim ke panitia!'
            : 'Draft berhasil disimpan!'
        );
    }

    private function saveParticipants()
    {
        $this->registration->participants()->delete();

        foreach ($this->participants as $p) {
            if (empty($p['nama'])) continue;

            $fotoPath = null;
            if (isset($p['foto']) && $p['foto']) {
                $fotoPath = $p['foto']->store('registrations/peserta', 'public');
            } elseif (isset($p['existing_foto']) && $p['existing_foto']) {
                $fotoPath = $p['existing_foto'];
            }

            Participant::create([
                'registration_id' => $this->registration->id,
                'nama' => $p['nama'],
                'nisn' => $p['nisn'] ?? null,
                'foto' => $fotoPath,
            ]);
        }
    }

    public function getVoteTotalProperty()
    {
        return \App\Models\VoteTransaction::where('registration_id', $this->activeRegId)
            ->where('status', 'PAID')
            ->sum('votes_earned');
    }

    public function getIsScoringFinalizedProperty()
    {
        return \App\Models\AssessmentScore::where('registration_id', $this->activeRegId)
            ->where('is_finalized', true)
            ->exists();
    }

    public function getFinalScoresProperty()
    {
        return \App\Models\AssessmentScore::where('registration_id', $this->activeRegId)
            ->where('is_finalized', true)
            ->with(['judge', 'assessmentCriteria.subCategory.category'])
            ->get();
    }

    public function getScoreCategoriesProperty()
    {
        return \App\Models\AssessmentCategory::where('eventner_id', $this->registration->eventner_id)->get();
    }

    public function getScoreJudgesProperty()
    {
        $finalScores = $this->finalScores;
        $judgeIds = $finalScores->pluck('judge_id')->unique();
        return $judgeIds->isNotEmpty()
            ? \App\Models\Judge::whereIn('id', $judgeIds)->get()
            : collect();
    }

    public function render()
    {
        return view('livewire.public.magic-link.registration', [
            'portalUrl' => $this->portalUrl,
        ])
            ->title('Kelola Pendaftaran - ' . $this->registration->eventner->nama_event)
            ->layoutData(['eventner' => $this->registration->eventner]);
    }
}

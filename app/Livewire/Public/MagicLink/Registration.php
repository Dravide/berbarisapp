<?php

namespace App\Livewire\Public\MagicLink;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Livewire\Concerns\MengelolaWilayah;
use App\Models\Registration as RegistrationModel;
use App\Models\Participant;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use Livewire\Attributes\Computed;

#[Layout('layouts.frontend')]
class Registration extends Component
{
    use WithFileUploads;
    use MengelolaWilayah;

    public $token;
    public $portalUrl;
    public $registration;
    public $siblingRegistrations;

    // Active tab for managing which registration
    public $activeRegId;

    // Nilai nama pelatih tidak punya properti sendiri: baris builder
    // `nama_pelatih` mengalir lewat `fieldValues` seperti field teks lain.
    // Foto pelatih, danton, dan berkas diunggah lewat endpoint HTTP generik
    // (magic.link.field.upload) — properti upload Livewire harus ada saat
    // compile, sedangkan field builder jumlahnya dinamis.
    public $dantonNama = '';
    public $dantonNisn = '';

    /**
     * Field yang di portal punya properti Livewire sendiri, bukan lewat
     * `fieldValues` — properti -> builtin_source.
     *
     * Dipakai agar satu field tidak divalidasi dua kali dengan nama berbeda:
     * kartu Danton mengikat `dantonNama`, sedangkan `fieldValues.danton_nama`
     * tidak pernah diisi apa pun dan akan selalu gagal "wajib diisi".
     */
    public const SUMBER_PROPRIET_SENDIRI = [
        'danton_nama' => 'dantonNama',
        'danton_nisn' => 'dantonNisn',
    ];

    /**
     * Nilai field buatan panitia (tanpa kolom di registrations), key = field_key.
     *
     * Field berkas tidak di sini: berkas diunggah lewat endpoint khusus
     * (magic.link.field.upload) dan langsung tersimpan.
     */
    public array $fieldValues = [];

    public $paymentProof;
    public $participants = [];

    // Draft state cache per registration (survives tab switching)
    public array $draftCache = [];

    public function mount($token)
    {
        $this->portalUrl = url()->current();
        $this->registration = RegistrationModel::with(['eventner', 'competitionCategory', 'participants', 'fieldValues'])
            ->where('magic_token', $token)
            ->firstOrFail();

        $this->token = $token;
        $this->activeRegId = $this->registration->id;

        // Event bisa saja belum pernah membuka builder-nya.
        RegistrationField::ensureDefaults($this->registration->eventner);

        // Load all registrations from the same school (same NPSN + same event)
        $this->siblingRegistrations = RegistrationModel::with(['competitionCategory', 'participants', 'fieldValues'])
            ->where('eventner_id', $this->registration->eventner_id)
            ->where('npsn', $this->registration->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        $this->loadFormData();
    }

    /**
     * Field builder yang tampil di portal, urut sesuai builder.
     *
     * Semua tipe ikut — termasuk berkas — karena di sini pendaftar sudah
     * memegang tautan portal dan memang waktunya melengkapi berkas.
     */
    #[Computed]
    public function fields()
    {
        return RegistrationField::forEventner($this->registration->eventner);
    }

    /** Field non-berkas yang bukan group: nilainya ikut disimpan saat tombol simpan ditekan. */
    #[Computed]
    public function textFields()
    {
        return $this->fields->reject(fn ($f) => $f->isFile() || $f->isGroup())->values();
    }

    #[Computed]
    public function fileFields()
    {
        return $this->fields->filter(fn ($f) => $f->isFile())->values();
    }

    /** Field berulang (daftar anggota pasukan). */
    #[Computed]
    public function groupFields()
    {
        return $this->fields->filter(fn ($f) => $f->isGroup())->values();
    }

    /**
     * Baris builder untuk satu `builtin_source`, null bila event ini belum
     * punya barisnya. Dipakai view untuk label kartu tetap (danton, pelatih).
     */
    public function barisField(string $source): ?RegistrationField
    {
        return $this->fields->firstWhere('builtin_source', $source);
    }

    /** Definisi satu field berdasarkan id — untuk blok unggahan berkas. */
    public function fieldById(int $id): ?RegistrationField
    {
        return $this->fields->firstWhere('id', $id);
    }

    /** Nilai bidang teks, dihitung dari registrasi yang sedang aktif. */
    public function nilaiField(RegistrationField $field): string
    {
        if ($field->builtin_source) {
            return (string) ($this->registration->{$field->builtin_source} ?? '');
        }

        return (string) ($this->registration->fieldValues
            ->firstWhere('registration_field_id', $field->id)?->value ?? '');
    }

    /**
     * Aturan validasi field teks, dibangun dari definisi di DB.
     *
     * Termasuk field yang punya properti sendiri di portal (nama danton, NISN
     * danton) dan sub-field grup peserta (nama anggota): label, wajib, dan
     * tampil atau tidaknya semuanya dari baris builder, jadi mematikan
     * "Nama Danton" di builder benar-benar melonggarkan validasinya.
     */
    private function aturanField(): array
    {
        $aturan = [];

        foreach ($this->textFields as $field) {
            // Danton punya propertinya sendiri (dantonNama/dantonNisn) —
            // aturannya dibuat di bawah, bukan lewat fieldValues.
            if (self::SUMBER_PROPRIET_SENDIRI[$field->builtin_source] ?? null) {
                continue;
            }

            $aturanDasar = $field->builtin_source === 'school_email'
                ? ($field->is_required ? 'required|email|max:255' : 'nullable|email|max:255')
                : $field->validationRule($field->is_required);

            // Field wilayah dapat rule tambahan: memastikan yang dikirim memang
            // hasil pilihan dropdown, bukan kode yang disusun sendiri.
            if ($field->type === 'wilayah') {
                $this->tambahAturanWilayah($aturan, $field, $aturanDasar);
                continue;
            }

            $aturan['fieldValues.' . $field->field_key] = $aturanDasar;
        }

        foreach (['dantonNama' => 'danton_nama', 'dantonNisn' => 'danton_nisn'] as $properti => $source) {
            $aturan[$properti] = $this->aturanSumber($source, 'string|max:255');
        }

        $peserta = $this->barisField('peserta');

        if ($peserta) {
            foreach ($peserta->sub_fields['items'] ?? [] as $sub) {
                if (($sub['type'] ?? 'text') !== 'text') {
                    continue;
                }

                $aturan['participants.*.' . $sub['key']] = ($sub['is_required'] ?? false)
                    ? 'required|string|max:255'
                    : 'nullable|string|max:255';
            }
        }

        return $aturan;
    }

    /**
     * Aturan untuk satu baris builder, dari properti milik portal.
     *
     * Field nonaktif tidak menghasilkan aturan sama sekali; field aktif yang
     * tidak diwajibkan jadi nullable.
     */
    private function aturanSumber(string $source, string $rule): string
    {
        $field = $this->barisField($source);

        return ($field && $field->is_required) ? 'required|' . $rule : 'nullable|' . $rule;
    }

    private function pesanField(): array
    {
        $pesan = [];

        foreach ($this->textFields as $field) {
            $pesan['fieldValues.' . $field->field_key . '.required'] = $field->label . ' wajib diisi.';
            $pesan['fieldValues.' . $field->field_key . '.in'] = 'Pilihan ' . $field->label . ' tidak valid.';
            $pesan['fieldValues.' . $field->field_key . '.numeric'] = $field->label . ' harus berupa angka.';
            $pesan['fieldValues.' . $field->field_key . '.date'] = $field->label . ' harus berupa tanggal yang valid.';
        }

        if ($labelDanton = $this->labelSumber('danton_nama')) {
            $pesan['dantonNama.required'] = $labelDanton . ' wajib diisi.';
        }

        if ($labelNisn = $this->labelSumber('danton_nisn')) {
            $pesan['dantonNisn.string'] = $labelNisn . ' harus berupa teks.';
        }

        if ($peserta = $this->barisField('peserta')) {
            foreach ($peserta->sub_fields['items'] ?? [] as $sub) {
                $pesan['participants.*.' . $sub['key'] . '.required'] = ($sub['label'] ?? 'Isian') . ' wajib diisi.';
            }
        }

        return $pesan;
    }

    /** Label satu baris builder, null bila barisnya tidak ada/nonaktif. */
    public function labelSumber(string $source): ?string
    {
        $field = $this->barisField($source);

        return $field?->is_active ? $field->label : null;
    }

    /**
     * Tulis nilai field teks ke tempatnya masing-masing: kolom registrations
     * untuk field bawaan, registration_field_values untuk field buatan panitia.
     */
    private function simpanFieldTeks(RegistrationModel $reg): void
    {
        foreach ($this->textFields as $field) {
            // Danton punya properti sendiri: nilainya diambil dari situ, bukan
            // dari fieldValues yang tidak pernah terisi (lihat tulisDanton()).
            if (self::SUMBER_PROPRIET_SENDIRI[$field->builtin_source] ?? null) {
                continue;
            }

            $nilai = $this->fieldValues[$field->field_key] ?? null;
            $nilai = $nilai !== null && $nilai !== '' ? strip_tags((string) $nilai) : null;

            if ($field->isGroup()) {
                continue;
            }

            if ($field->builtin_source) {
                $reg->{$field->builtin_source} = $nilai;
                continue;
            }

            RegistrationFieldValue::updateOrCreate(
                [
                    'registration_id' => $reg->id,
                    'registration_field_id' => $field->id,
                ],
                ['value' => $nilai]
            );
        }
    }

    /**
     * Simpan danton dari propertinya sendiri, tapi hanya untuk field yang
     * barisnya masih ada dan aktif di builder.
     *
     * Field yang dimatikan panitia tidak dirender dan tidak divalidasi; tanpa
     * penjagaan ini menyimpan draft akan mengosongkan isian lama diam-diam.
     */
    private function tulisDanton(RegistrationModel $reg): void
    {
        foreach (self::SUMBER_PROPRIET_SENDIRI as $sumber => $properti) {
            $field = $this->barisField($sumber);

            if (! $field) {
                continue;
            }

            $reg->{$sumber} = strip_tags((string) $this->{$properti});
        }
    }


    public function switchRegistration($regId)
    {
        if ($regId == $this->activeRegId) return;

        // Cache current draft state before switching away
        $this->draftCache[$this->activeRegId] = $this->draftSaatIni();

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
            $this->fieldValues  = $c['fieldValues'] ?? [];
        } else {
            $this->loadFormData();
        }
    }

    /**
     * Isian yang belum disimpan milik registrasi yang sedang dibuka.
     *
     * Dipakai saat pindah tab registrasi: tanpa ini, isian field builder hilang
     * begitu pendaftar mengecek pasukan lain lalu kembali.
     */
    private function draftSaatIni(): array
    {
        return [
            'participants' => $this->participants,
            'dantonNama'   => $this->dantonNama,
            'dantonNisn'   => $this->dantonNisn,
            'fieldValues'  => $this->fieldValues,
        ];
    }

    private function loadFormData()
    {
        $this->dantonNama = $this->registration->danton_nama ?? '';
        $this->dantonNisn = $this->registration->danton_nisn ?? '';

        if ($this->registration->participants->count() > 0) {
            $this->participants = [];
            foreach ($this->registration->participants as $p) {
                $this->participants[] = ['nama' => $p->nama, 'nisn' => $p->nisn ?? '', 'foto' => null, 'existing_foto' => $p->foto];
            }
        } else {
            // Jumlah baris kosong ikut builder, bukan angka tetap.
            $jumlah = $this->groupFields->first()?->defaultRows() ?? 12;

            $this->participants = [];
            for ($i = 0; $i < $jumlah; $i++) {
                $this->participants[] = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
            }
        }

        // Nilai field teks dari DB — berkas tidak ikut, karena diunggah lewat
        // endpoint sendiri dan statusnya dibaca langsung dari registrasi.
        $this->fieldValues = [];
        foreach ($this->textFields as $field) {
            $this->fieldValues[$field->field_key] = $this->nilaiField($field);
        }

        // Field wilayah dipulihkan ke pilihannya semula supaya dropdown tampil
        // sudah terisi, bukan kosong seolah pesertanya belum pernah memilih.
        $this->wilayah = [];
        $this->muatWilayahTersimpan($this->textFields);
    }

    /** Nilai awal field wilayah: nilai yang tersimpan di DB untuk registrasi ini. */
    protected function nilaiWilayahAwal(RegistrationField $field): string
    {
        return trim($this->fieldValues[$field->field_key] ?? '');
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

        $this->validate(array_merge($this->aturanField()), $this->pesanField());

        $this->tulisDanton($reg);
        $this->simpanFieldTeks($reg);
        $reg->status_berkas = 'confirmed';
        $reg->save();

        $this->saveParticipants();

        // Refresh
        $this->registration = $reg->fresh(['participants', 'fieldValues']);
        $this->siblingRegistrations = RegistrationModel::with(['competitionCategory', 'participants', 'fieldValues'])
            ->where('eventner_id', $this->registration->eventner_id)
            ->where('npsn', $this->registration->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        // Re-sync form state with what was just saved
        $this->loadFormData();
        $this->draftCache[$this->activeRegId] = $this->draftSaatIni();

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
            'participants.*.foto' => 'nullable|image|max:3072',
        ];

        $pesan = [
            'participants.*.foto.image' => 'Foto peserta harus berupa gambar.',
        ];

        $berkasWajib = $this->berkasWajibBelumAda();

        if ($isFinal && $berkasWajib->isNotEmpty()) {
            foreach ($berkasWajib as $field) {
                $this->addError('fields.' . $field->id, $field->label . ' wajib diunggah sebelum data difinalisasi.');
            }

            session()->flash('error', 'Masih ada berkas wajib yang belum diunggah: ' . $berkasWajib->pluck('label')->implode(', ') . '.');

            return;
        }

        // Aturan semua field — teks, danton, dan sub-field grup peserta —
        // dibangun dari baris builder. Berkas tidak di sini karena sudah
        // tersimpan langsung oleh endpoint unggahan.
        $this->validate(array_merge($rules, $this->aturanField()), array_merge($pesan, $this->pesanField()));

        $this->tulisDanton($reg);
        $this->simpanFieldTeks($reg);

        if ($isFinal) {
            $reg->is_finalized = true;
            if ($reg->status_berkas === 'booking') {
                $reg->status_berkas = 'confirmed';
            }
        }

        $reg->save();
        $this->saveParticipants();

        // Refresh
        $this->registration = $reg->fresh(['participants', 'fieldValues']);
        $this->siblingRegistrations = RegistrationModel::with(['competitionCategory', 'participants', 'fieldValues'])
            ->where('eventner_id', $this->registration->eventner_id)
            ->where('npsn', $this->registration->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        // Re-sync form state with what was just saved (staged fotos consumed, existing_foto updated)
        $this->loadFormData();
        $this->draftCache[$this->activeRegId] = $this->draftSaatIni();

        session()->flash('success', $isFinal
            ? 'Data berhasil difinalisasi dan dikirim ke panitia!'
            : 'Draft berhasil disimpan!'
        );
    }

    /**
     * Field berkas aktif yang wajib tapi belum ada isinya.
     *
     * Berkas diunggah lewat endpoint sendiri, jadi statusnya dibaca dari
     * kolom registrations / registration_field_values — bukan dari properti
     * Livewire.
     *
     * @return \Illuminate\Support\Collection<int, RegistrationField>
     */
    private function berkasWajibBelumAda()
    {
        return $this->fileFields
            ->filter(fn ($field) => $field->is_required)
            ->filter(function ($field) {
                $nilai = $field->builtin_source
                    ? $this->registration->{$field->builtin_source}
                    : $this->registration->fieldValues
                        ->firstWhere('registration_field_id', $field->id)?->value;

                return ! $nilai;
            })
            ->values();
    }

    /** Field berkas aktif + nilai & URL-nya, untuk blok unggahan di view. */
    #[Computed]
    public function berkasFields()
    {
        return $this->fileFields->map(function ($field) {
            $nilai = $field->builtin_source
                ? $this->registration->{$field->builtin_source}
                : $this->registration->fieldValues
                    ->firstWhere('registration_field_id', $field->id)?->value;

            return [
                'field' => $field,
                'path' => $nilai,
                'url' => $nilai ? asset('storage/' . ltrim($nilai, '/')) : null,
            ];
        });
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

    /**
     * Komentar pendukung (voter) untuk pasukan di tab aktif — hanya
     * transaksi PAID yang mengisi komentar.
     */
    public function getVoteCommentsProperty()
    {
        return \App\Models\VoteTransaction::where('registration_id', $this->activeRegId)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'voter_name', 'comment', 'votes_earned', 'paid_at']);
    }

    public function getVoteCommentCountProperty(): int
    {
        return \App\Models\VoteTransaction::where('registration_id', $this->activeRegId)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->count();
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
        // Hanya kategori penilaian yang relevan dengan tingkat lomba
        // registrasi ini (spesifik tingkat + global) — sama seperti
        // logika input nilai. Kategori tingkat lain tidak ikut tampil
        // sebagai baris nol.
        $compCatId = $this->registration->competition_category_id;

        return \App\Models\AssessmentCategory::where('eventner_id', $this->registration->eventner_id)
            ->where(function ($q) use ($compCatId) {
                $q->where('competition_category_id', $compCatId)
                    ->orWhereNull('competition_category_id');
            })
            ->get();
    }

    public function getScoreDeductionsProperty()
    {
        return \App\Models\ScoreDeduction::where('registration_id', $this->activeRegId)->get();
    }

    public function getScoreJudgesProperty()
    {
        $finalScores = $this->finalScores;
        $judgeIds = $finalScores->pluck('judge_id')->unique();
        return $judgeIds->isNotEmpty()
            ? \App\Models\Judge::whereIn('id', $judgeIds)->get()
            : collect();
    }

    /**
     * Pasukan (registrasi) sekolah ini beserta hak sertifikatnya, keyed by id:
     * [registration_id => ['registration' => Registration, 'rank' => ?int,
     * 'title' => string, 'is_champion' => bool]].
     *
     * Semua tab dapat entri — pasukan juara bergelar juara, sisanya PESERTA
     * (sertifikat PESERTA juga terbit untuk non-juara, bukan 404).
     */
    public function getCertificateRegistrationsProperty()
    {
        $eventner = $this->registration->eventner;

        if (!\App\Models\CertificateTemplate::where('eventner_id', $eventner->id)
            ->where('is_active', true)
            ->exists()) {
            return collect();
        }

        $peserta = fn ($reg) => [
            'registration' => $reg,
            'rank' => null,
            'title' => 'PESERTA',
            'is_champion' => false,
        ];

        $championCategories = \App\Models\ChampionCategory::where('eventner_id', $eventner->id)
            ->with(['assessmentSubCategories.category', 'rankTitles'])
            ->get();

        // Tab aktif selalu dapat entri walau npsn kosong (siblingRegistrations
        // difilter by npsn sehingga bisa tidak memuat dirinya sendiri).
        $result = [$this->registration->id => $peserta($this->registration)];

        if ($championCategories->isEmpty()) {
            foreach ($this->siblingRegistrations as $reg) {
                $result[$reg->id] = $peserta($reg);
            }

            return collect($result);
        }

        $calculator = app(\App\Services\ChampionCalculator::class);

        // Peringkat dihitung sekali per mata lomba, dipakai ulang untuk semua
        // pasukan sekolah ini. Scope per mata lomba — pool gabungan lintas
        // mata lomba membuat gelar juara tertukar antar pasukan.
        $winnersByCategory = [];

        foreach ($this->siblingRegistrations as $reg) {
            $catId = $reg->competition_category_id;
            $won = null;

            foreach ($catId ? $championCategories : [] as $championCategory) {
                if (!$championCategory->isVisibleFor($catId)) {
                    continue;
                }

                $winnersByCategory[$catId][$championCategory->id] ??=
                    $calculator->winners($championCategory, $catId)[2];

                foreach ($winnersByCategory[$catId][$championCategory->id] as $winner) {
                    if ($winner['registration']->id !== $reg->id) {
                        continue;
                    }

                    $won = [
                        'registration' => $reg,
                        'rank' => $winner['rank'],
                        'title' => $championCategory->name . ' — ' . $this->gelarJuara($championCategory, $winner['rank']),
                        'is_champion' => true,
                    ];
                    break 2;
                }
            }

            $result[$reg->id] = $won ?? $peserta($reg);
        }

        return collect($result);
    }

    /**
     * Gelar juara dengan nomor posisi dalam grup rank title, mengikuti
     * perhitungan yang sama dengan halaman hasil & unduhan sertifikat.
     */
    private function gelarJuara(\App\Models\ChampionCategory $championCategory, int $rank): string
    {
        return $championCategory->titleForRank($rank) ?? 'Juara ' . $rank;
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

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
     * Section yang kartunya di halaman cuma meringkas dan diubah lewat modal.
     *
     * Ketiga section builder memakai bentuk yang sama: kartu ringkas di halaman,
     * pengubahan lewat modal, validasi menyempit ke section itu saja. Dengan
     * begitu salah isi di Data Pendaftaran tidak lagi membanjiri Data Pelatih
     * dan Danton sekaligus, dan halaman tetap terbaca sekilas tanpa menggulir
     * satu form panjang.
     */
    public const SEKSI_BERMODAL = ['umum', 'pelatih', 'danton'];

    /**
     * Nilai field buatan panitia (tanpa kolom di registrations), key = field_key.
     *
     * Field berkas tidak di sini: berkas diunggah lewat endpoint khusus
     * (magic.link.field.upload) dan langsung tersimpan.
     */
    public array $fieldValues = [];

    public $paymentProof;
    public $participants = [];

    /**
     * Anggota diisi satu per satu lewat modal.
     *
     * `$memberDraft` adalah satu-satunya properti unggahan anggota di portal —
     * FilePond tunggal, bukan satu per baris. Menekan "Simpan Anggota" menulis
     * barisnya ke tabel `participants` saat itu juga, demikian pula tombol
     * hapus; menyimpannya sampai Simpan Draft berarti pendaftar yang menyegarkan
     * halaman harus mengunggah ulang semua foto.
     *
     * Setiap baris `$participants` karena itu membawa `id` barisnya di DB —
     * itulah penanda yang dipakai `saveParticipants()` untuk menyelaraskan,
     * bukan menghapus-semua lalu membuat ulang.
     */
    public bool $modalAnggota = false;

    public array $memberDraft = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];

    /** null = mode tambah, angka = mode ubah baris ke-n. */
    public ?int $editingIndex = null;

    /** Baris yang menunggu konfirmasi hapus; null = tidak ada dialog. */
    public ?int $konfirmasiHapus = null;

    /**
     * Penanda buka-modal.
     *
     * Ikut masuk `wire:key` modal supaya Livewire tidak memakai ulang elemen
     * lama saat modal dibuka lagi — FilePond meninggalkan DOM-nya sendiri di
     * dalam elemen itu, dan sisa instance lama membuat kotak unggahan kosong.
     */
    public int $modalNonce = 0;

    /**
     * Section yang sedang dibuka di modal — `pelatih`, `danton`, atau null.
     *
     * Data Pelatih dan Danton punya bentuk yang sama dengan anggota: kartunya
     * di halaman hanya meringkas isian, pengubahannya lewat modal, dan
     * validasinya menyempit ke section itu saja. Bedanya, di sini tidak ada
     * properti draf terpisah — modal mengikat `fieldValues` / `dantonNama` yang
     * sama dengan form inline, jadi tidak ada dua salinan nilai yang bisa
     * berselisih. Menekan "Simpan" di modal menulis section itu ke DB saat itu
     * juga, sama seperti `simpanAnggota()`.
     */
    public ?string $modalSeksi = null;

    /**
     * Penanda buka-modal section, alasannya sama dengan `$modalNonce`: FilePond
     * menanam DOM-nya sendiri di dalam elemen modal, jadi elemen lama harus
     * dibuang saat modal dibuka ulang.
     */
    public int $seksiNonce = 0;

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
     * Nama properti Livewire milik satu field, null bila nilainya mengalir
     * lewat `fieldValues`.
     *
     * Dipakai view supaya kartu ringkas dan modal section memakai pengikatan
     * yang sama untuk field mana pun — termasuk danton, satu-satunya section
     * yang propertinya berdiri sendiri.
     */
    public function propertiField(RegistrationField $field): ?string
    {
        return self::SUMBER_PROPRIET_SENDIRI[$field->builtin_source] ?? null;
    }

    /**
     * Nilai terkini satu field untuk ditampilkan di kartu ringkas.
     *
     * Berbeda dari `nilaiField()` yang membaca DB: isian yang belum disimpan
     * ikut terbaca. Kartu Pelatih dan Danton meringkas apa yang sedang ada di
     * form — kalau meringkas DB, pendaftar yang baru mengetik akan melihat
     * namanya sendiri hilang dari ringkasan.
     */
    public function nilaiTampil(RegistrationField $field): string
    {
        $properti = $this->propertiField($field);

        if ($properti) {
            return trim((string) $this->{$properti});
        }

        return trim((string) ($this->fieldValues[$field->field_key] ?? ''));
    }

    /**
     * Aturan validasi field teks, dibangun dari definisi di DB.
     *
     * Termasuk field yang punya properti sendiri di portal (nama danton, NISN
     * danton) dan sub-field grup peserta (nama anggota): label, wajib, dan
     * tampil atau tidaknya semuanya dari baris builder, jadi mematikan
     * "Nama Danton" di builder benar-benar melonggarkan validasinya.
     *
     * `$seksi` mempersempit aturannya ke satu section saja — itu yang dipakai
     * modal Pelatih dan Danton. Tanpa argumen, seluruh field kembali masuk,
     * seperti pada submit akhir.
     */
    private function aturanField(?string $seksi = null): array
    {
        $aturan = [];

        foreach ($this->textFields as $field) {
            if ($seksi !== null && ($field->section ?: 'umum') !== $seksi) {
                continue;
            }

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

        if ($seksi === null || $seksi === 'danton') {
            foreach (['dantonNama' => 'danton_nama', 'dantonNisn' => 'danton_nisn'] as $properti => $source) {
                $aturan[$properti] = $this->aturanSumber($source, 'string|max:255');
            }
        }

        if ($seksi !== null) {
            return $aturan;
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
     * Aturan validasi untuk SATU anggota — dibaca dari `memberDraft`.
     *
     * Definisi sub-field-nya sama dengan `aturanField()`, tapi terikat ke
     * properti draf, bukan ke `participants.*`. Inilah yang membuat modal
     * anggota gagal di dalam dirinya sendiri: yang salah hanya anggota yang
     * sedang diisi, bukan sebelas baris lain yang tidak pernah disentuh.
     *
     * `aturanField()` tetap memakai `participants.*` — dengan daftar yang tidak
     * lagi menyimpan baris kosong, aturan itu berhenti melukai dan tetap
     * menjaga klien yang menyusun payload sendiri.
     */
    private function aturanAnggota(): array
    {
        $aturan = [];

        foreach ($this->subFieldsAnggota() as $sub) {
            $aturan['memberDraft.' . $sub['key']] = match (true) {
                ($sub['type'] ?? 'text') === 'image' => 'nullable|image|max:' . (int) ($sub['max_kb'] ?? 3072),
                ($sub['is_required'] ?? false) => 'required|string|max:255',
                default => 'nullable|string|max:255',
            };
        }

        return $aturan;
    }

    /** Pesan validasi untuk satu anggota, memakai label dari builder. */
    private function pesanAnggota(): array
    {
        $pesan = [];

        foreach ($this->subFieldsAnggota() as $sub) {
            $label = $sub['label'] ?? 'Isian';
            $pesan['memberDraft.' . $sub['key'] . '.required'] = $label . ' wajib diisi.';
            $pesan['memberDraft.' . $sub['key'] . '.image'] = $label . ' harus berupa gambar.';
        }

        return $pesan;
    }

    /** Sub-field anggota yang boleh diisi — urut builder. */
    private function subFieldsAnggota(): array
    {
        return $this->barisField('peserta')?->sub_fields['items'] ?? [];
    }

    /**
     * Jumlah anggota paling sedikit yang diharapkan event ini.
     *
     * Isyarat, bukan gerbang: tombol kirim tidak pernah diblokir olehnya.
     */
    #[Computed]
    public function minAnggota(): int
    {
        return $this->barisField('peserta')?->minRows() ?? 1;
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

    /**
     * Pesan validasi field teks, sepadan dengan `aturanField()`.
     *
     * `$seksi` mengikuti argumen yang sama: menyempit ke satu section untuk
     * modal, atau null untuk submit akhir.
     */
    private function pesanField(?string $seksi = null): array
    {
        $pesan = [];

        foreach ($this->textFields as $field) {
            if ($seksi !== null && ($field->section ?: 'umum') !== $seksi) {
                continue;
            }

            $pesan['fieldValues.' . $field->field_key . '.required'] = $field->label . ' wajib diisi.';
            $pesan['fieldValues.' . $field->field_key . '.in'] = 'Pilihan ' . $field->label . ' tidak valid.';
            $pesan['fieldValues.' . $field->field_key . '.numeric'] = $field->label . ' harus berupa angka.';
            $pesan['fieldValues.' . $field->field_key . '.date'] = $field->label . ' harus berupa tanggal yang valid.';
        }

        if ($seksi === null || $seksi === 'danton') {
            if ($labelDanton = $this->labelSumber('danton_nama')) {
                $pesan['dantonNama.required'] = $labelDanton . ' wajib diisi.';
            }

            if ($labelNisn = $this->labelSumber('danton_nisn')) {
                $pesan['dantonNisn.string'] = $labelNisn . ' harus berupa teks.';
            }
        }

        if ($seksi !== null) {
            return $pesan;
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
     *
     * `$seksi` mempersempit penulisan ke satu section — dipakai modal section,
     * yang hanya memvalidasi section itu: menulis section lain yang belum
     * divalidasi berarti membuang isian yang belum sempat diperiksa.
     */
    private function simpanFieldTeks(RegistrationModel $reg, ?string $seksi = null): void
    {
        foreach ($this->textFields as $field) {
            if ($seksi !== null && ($field->section ?: 'umum') !== $seksi) {
                continue;
            }

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
    private function tulisDanton(RegistrationModel $reg, ?string $seksi = null): void
    {
        foreach (self::SUMBER_PROPRIET_SENDIRI as $sumber => $properti) {
            $field = $this->barisField($sumber);

            if (! $field) {
                continue;
            }

            if ($seksi !== null && ($field->section ?: 'umum') !== $seksi) {
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

        // Modal anggota milik pasukan sebelumnya — dibawa pindah tab, ia akan
        // menyimpan draf ke daftar pasukan yang salah. Modal section sama:
        // isinya terikat ke properti form, jadi dibiarkan terbuka ia akan
        // mengedit pasukan yang baru, bukan yang tadi dibuka.
        $this->tutupModalAnggota();
        $this->tutupModalSeksi();
        $this->konfirmasiHapus = null;

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
                $this->participants[] = [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'nisn' => $p->nisn ?? '',
                    'foto' => null,
                    'existing_foto' => $p->foto,
                ];
            }
        } else {
            // Daftar kosong, bukan 12 baris kosong.
            //
            // Dulu `default_rows` dipakai sebagai jumlah baris yang dirender,
            // dan setiap baris ikut divalidasi `participants.*.nama required`
            // saat submit — pendaftar yang mengisi tiga anggota menerima
            // sembilan error sekaligus. Sekarang anggota masuk satu per satu
            // lewat modal; `default_rows` tinggal jadi petunjuk ukuran pasukan.
            $this->participants = [];
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

    /**
     * Buka modal anggota. Tanpa indeks = mode tambah; dengan indeks = mode ubah.
     *
     * Semua nama aksi modal memakai awalan `anggota` supaya tidak ada satu pun
     * yang bertabrakan dengan alias `$wire` Livewire — tabrakan seperti itu
     * hanya hidup di JS dan lolos dari tes per-fitur.
     */
    public function bukaModalAnggota($index = null)
    {
        if ($this->terkunci()) {
            return;
        }

        if ($index === null) {
            $this->editingIndex = null;
            $this->memberDraft = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
        } else {
            $index = (int) $index;
            $baris = $this->participants[$index] ?? null;

            if (is_null($baris)) {
                return;
            }

            $this->editingIndex = $index;
            $this->memberDraft = [
                'nama' => (string) ($baris['nama'] ?? ''),
                'nisn' => (string) ($baris['nisn'] ?? ''),
                'foto' => null,
                'existing_foto' => $baris['existing_foto'] ?? null,
            ];
        }

        $this->resetValidation();
        $this->modalNonce++;
        $this->modalAnggota = true;
    }

    /**
     * Tutup modal dan buang drafnya.
     *
     * Draf yang batal tidak meninggalkan baris kosong: daftar hanya berisi
     * anggota yang benar-benar pernah ditekan "Simpan Anggota".
     */
    public function tutupModalAnggota()
    {
        $this->modalAnggota = false;
        $this->editingIndex = null;
        $this->memberDraft = ['nama' => '', 'nisn' => '', 'foto' => null, 'existing_foto' => null];
        $this->resetValidation();
    }

    /**
     * Simpan satu anggota dari modal — langsung ke tabel `participants`.
     *
     * Dua hal yang dijaga di sini. Pertama, validasi hanya menyentuh draf yang
     * sedang diisi: sebelumnya `submit()` memvalidasi `participants.*.nama`
     * untuk SEMUA baris sekaligus, sehingga 12 baris kosong menghasilkan dinding
     * error meski pendaftar baru mengisi tiga anggota. Kedua, barisnya ditulis
     * ke DB saat itu juga — foto yang baru diunggah ikut tersimpan, jadi
     * menyegarkan halaman tidak lagi memaksa mengunggah ulang semuanya.
     */
    public function simpanAnggota()
    {
        if ($this->terkunci() || ! $this->bolehMenulis()) {
            return;
        }

        $this->validate($this->aturanAnggota(), $this->pesanAnggota());

        $nama = trim(strip_tags((string) $this->memberDraft['nama']));
        $nisn = trim(strip_tags((string) ($this->memberDraft['nisn'] ?? '')));

        // Mode ubah tanpa baris sasaran (mis. indeks basi dari dua tab) tidak
        // menulis apa pun — lebih baik tidak terjadi apa-apa.
        $lama = $this->editingIndex === null ? null : ($this->participants[$this->editingIndex] ?? null);

        if ($this->editingIndex !== null && is_null($lama)) {
            return;
        }

        $anggota = $this->tulisBarisAnggota(
            $lama['id'] ?? null,
            $nama,
            $nisn === '' ? null : $nisn,
            $this->fotoDraftAnggota()
        );

        $baris = [
            'id' => $anggota->id,
            'nama' => $nama,
            'nisn' => $nisn,
            'foto' => null,
            'existing_foto' => $anggota->foto,
        ];

        if ($this->editingIndex === null) {
            $this->participants[] = $baris;
        } else {
            $this->participants[$this->editingIndex] = $baris;
        }

        $this->tutupModalAnggota();
        $this->draftCache[$this->activeRegId] = $this->draftSaatIni();

        session()->flash('success', $nama . ' tersimpan.');
    }

    /**
     * Foto yang harus dipakai baris anggota: unggahan baru bila ada, kalau tidak
     * foto lama. Mengubah nama tidak boleh menghapus pas foto yang sudah ada.
     */
    private function fotoDraftAnggota(): ?string
    {
        if (! empty($this->memberDraft['foto'])) {
            return $this->memberDraft['foto']->store('registrations/peserta', 'public');
        }

        return $this->memberDraft['existing_foto'] ?? null;
    }

    /**
     * Tulis satu baris anggota ke DB dan kembalikan modelnya.
     *
     * `$id` null atau baris yang sudah tidak ada (dihapus dari tab lain) berarti
     * baris baru. Ditulis per baris, bukan hapus-semua-lalu-buat-ulang, supaya
     * id anggota tidak berubah-ubah setiap kali Simpan Draft ditekan.
     */
    private function tulisBarisAnggota(?int $id, string $nama, ?string $nisn, ?string $foto): Participant
    {
        $atribut = ['nama' => $nama, 'nisn' => $nisn, 'foto' => $foto];

        $anggota = $id ? $this->registration->participants()->find($id) : null;

        if ($anggota) {
            $anggota->update($atribut);

            return $anggota;
        }

        return $this->registration->participants()->create($atribut);
    }

    /**
     * Gerbang tulis untuk anggota dan section bermodal: booking dan hari-H.
     *
     * Menulis langsung ke DB melewati penjagaan yang dulu hanya ada di
     * `submit()`. Dua syarat itu diperiksa di sini juga, karena kalau tidak,
     * tombol "Simpan" jadi celah masuk data saat pendaftaran belum dibuka atau
     * sudah lewat.
     *
     * Masa booking tidak lagi memblokir tanpa syarat: pasukan yang biaya
     * pendaftarannya sudah lunas boleh mengisi lebih awal, sama seperti view
     * yang membuka formulirnya (Registration::sudahBolehMengisi()).
     */
    private function bolehMenulis(): bool
    {
        $status = $this->registration->eventner->registration_status ?? 'open';

        if ($status === 'booking' && ! $this->registration->sudahBolehMengisi()) {
            session()->flash('error', 'Saat ini hanya diperbolehkan booking slot. Pengisian data pasukan akan dibuka setelah masa pendaftaran dibuka secara resmi, atau setelah biaya pendaftaran diverifikasi lunas.');

            return false;
        }

        if ($status === 'closed' && $this->registration->eventner->hasEventDayStarted()) {
            session()->flash('error', 'Hari-H pelaksanaan telah tiba. Perubahan data tidak lagi diperbolehkan.');

            return false;
        }

        return true;
    }

    /** Minta konfirmasi hapus satu baris — dialognya modal sendiri, bukan window.confirm. */
    public function mintaHapusAnggota($index)
    {
        if ($this->terkunci()) {
            return;
        }

        $this->konfirmasiHapus = is_null($this->participants[(int) $index] ?? null) ? null : (int) $index;
    }

    public function batalHapusAnggota()
    {
        $this->konfirmasiHapus = null;
    }

    /**
     * Buang satu anggota — dari daftar dan langsung dari DB.
     *
     * Baris terakhir tidak boleh dihapus — pasukan tanpa anggota bukan keadaan
     * yang bisa disimpan.
     */
    public function hapusAnggota($index)
    {
        if ($this->terkunci() || ! $this->bolehMenulis()) {
            return;
        }

        $index = (int) $index;

        if (count($this->participants) <= 1 || is_null($this->participants[$index] ?? null)) {
            $this->konfirmasiHapus = null;

            return;
        }

        $anggota = $this->participants[$index];

        if (! empty($anggota['id'])) {
            $this->registration->participants()->whereKey($anggota['id'])->delete();
        }

        unset($this->participants[$index]);
        $this->participants = array_values($this->participants);

        // Indeks bergeser setelah penghapusan: mode ubah yang masih menunjuk
        // baris setelahnya akan menyunting anggota yang salah.
        if ($this->editingIndex !== null && $this->editingIndex > $index) {
            $this->editingIndex--;
        }

        $this->konfirmasiHapus = null;
        $this->draftCache[$this->activeRegId] = $this->draftSaatIni();

        session()->flash('success', 'Anggota dihapus.');
    }

    /**
     * Terkunci = sudah difinalisasi (kecuali sedang ditolak panitia), atau
     * sudah dinyatakan Terverifikasi.
     *
     * Dua syarat, karena keduanya pernah jadi satu-satunya penjaga di jalur
     * berbeda: view memakai `$isLocked` (finalisasi), sedangkan aksi lama
     * memakai `status_berkas === 'Terverifikasi'`. Digabung supaya tidak ada
     * celah antara keduanya.
     */
    private function terkunci(): bool
    {
        return $this->registration->status_berkas === 'Terverifikasi'
            || ($this->registration->is_finalized && $this->registration->status_berkas !== 'Ditolak');
    }

    // ── Modal section (Data Pelatih & Danton) ─────────────────────────────

    /**
     * Buka modal satu section.
     *
     * Tidak ada draf terpisah seperti modal anggota: modal ini mengikat
     * `fieldValues` / `dantonNama` yang sama dengan form, jadi mengedit di sini
     * sama saja dengan mengedit di tempat. Yang didapat dari pemisahan ini
     * adalah validasinya — `aturanField($seksi)` hanya menyentuh section itu,
     * sehingga salah isi di Data Pelatih tidak lagi memunculkan error Danton
     * sekaligus.
     */
    public function bukaModalSeksi($seksi)
    {
        if ($this->terkunci()) {
            return;
        }

        $seksi = (string) $seksi;

        if (! in_array($seksi, self::SEKSI_BERMODAL, true) || ! $this->adaIsiSeksi($seksi)) {
            return;
        }

        $this->resetValidation($this->kunciValidasiSeksi($seksi));
        $this->modalSeksi = $seksi;
        $this->seksiNonce++;
    }

    /** Tutup modal section; isian yang sudah diketik tetap ada di form. */
    public function tutupModalSeksi()
    {
        $seksi = $this->modalSeksi;

        $this->modalSeksi = null;

        if ($seksi !== null) {
            $this->resetValidation($this->kunciValidasiSeksi($seksi));
        }
    }
    /**
     * Simpan isian modal section ke DB.
     *
     * Pelatih dan Danton ikut jalur anggota: menekan "Simpan" menulis nilainya
     * saat itu juga, jadi menyegarkan halaman tidak lagi membuang isian yang
     * baru diketik. Yang diketik memang sudah berada di properti form yang sama
     * dengan kartu ringkas — di sini validasinya menyempit ke section itu, lalu
     * nilainya ditulis.
     *
     * Penjagaan status pendaftaran (booking / hari-H) ikut dipasang, sama
     * seperti `simpanAnggota()`: dulu satu-satunya jalur tulis adalah `submit()`
     * yang memegang penjagaan itu, dan membuka jalur tulis baru tanpa
     * penjagaan berarti celah masuk data.
     */
    public function simpanSeksi()
    {
        if ($this->terkunci() || ! $this->bolehMenulis()) {
            return;
        }

        $seksi = $this->modalSeksi;

        if ($seksi === null || ! in_array($seksi, self::SEKSI_BERMODAL, true)) {
            return;
        }

        $this->validate($this->aturanField($seksi), $this->pesanField($seksi));

        $this->tulisDanton($this->registration, $seksi);
        $this->simpanFieldTeks($this->registration, $seksi);
        $this->registration->save();

        $this->draftCache[$this->activeRegId] = $this->draftSaatIni();
        $this->tutupModalSeksi();

        session()->flash('success', (RegistrationField::SECTIONS[$seksi] ?? 'Data') . ' tersimpan.');
    }

    /** Section ini punya sesuatu untuk ditampilkan? (field teks atau berkas) */
    private function adaIsiSeksi(string $seksi): bool
    {
        if ($this->textFields->contains(fn ($f) => ($f->section ?: 'umum') === $seksi)) {
            return true;
        }

        return collect($this->berkasFields)->contains(fn ($b) => ($b['field']->section ?: 'umum') === $seksi);
    }

    /**
     * Nama error milik satu section.
     *
     * Dipakai supaya membuka modal hanya membersihkan error section itu —
     * `resetValidation()` tanpa argumen akan ikut menghapus error form umum
     * yang sedang ditampilkan, dan pendaftar kehilangan petunjuk di sana.
     *
     * @return list<string>
     */
    private function kunciValidasiSeksi(string $seksi): array
    {
        $kunci = [];

        foreach ($this->textFields as $field) {
            if (($field->section ?: 'umum') !== $seksi) {
                continue;
            }

            $properti = self::SUMBER_PROPRIET_SENDIRI[$field->builtin_source] ?? null;

            $kunci[] = $properti ?: 'fieldValues.' . $field->field_key;
        }

        foreach ($this->berkasFields as $berkas) {
            if (($berkas['field']->section ?: 'umum') === $seksi) {
                $kunci[] = 'fields.' . $berkas['field']->id;
            }
        }

        return $kunci;
    }

    public function confirm()
    {
        $reg = $this->registration;

        if ($reg->status_berkas !== 'booking') return;

        // Masa booking ditahan gerbang yang sama dengan view: TM lewat, atau
        // biaya pendaftaran sudah lunas. Dua jalur berbeda berarti pendaftar
        // melihat tombolnya tapi ditolak server, atau sebaliknya.
        if (($reg->eventner->registration_status ?? 'open') === 'booking' && ! $reg->sudahBolehMengisi()) {
            session()->flash('error', 'Konfirmasi hanya bisa dilakukan setelah Technical Meeting (' . \Carbon\Carbon::parse($reg->eventner->technical_meeting)->translatedFormat('d F Y, H:i') . '), atau setelah biaya pendaftaran diverifikasi lunas.');
            return;
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
        if (($reg->eventner->registration_status ?? 'open') == 'booking' && ! $reg->sudahBolehMengisi()) {
            session()->flash('error', 'Saat ini hanya diperbolehkan booking slot. Pengisian data pasukan akan dibuka setelah masa pendaftaran dibuka secara resmi, atau setelah biaya pendaftaran diverifikasi lunas.');
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

    /**
     * Selaraskan daftar anggota di form dengan tabel `participants`.
     *
     * Anggota sudah ditulis saat "Simpan Anggota" ditekan, jadi di sini tidak
     * ada yang perlu dihapus-semua: baris yang sudah punya id diperbarui, yang
     * belum (dikirim langsung dari klien yang menyusun payload sendiri) dibuat,
     * dan baris yang tidak lagi ada di daftar barulah dihapus. Menghapus semua
     * lalu membuat ulang akan membuang id anggota — padahal itu penanda yang
     * menyambung baris form dengan baris DB.
     */
    private function saveParticipants()
    {
        $tersisa = [];

        foreach ($this->participants as $p) {
            if (empty($p['nama'])) continue;

            $id = $p['id'] ?? null;
            $anggota = $id ? $this->registration->participants()->find($id) : null;

            $fotoPath = $anggota?->foto;

            if (isset($p['foto']) && $p['foto']) {
                $fotoPath = $p['foto']->store('registrations/peserta', 'public');
            } elseif (isset($p['existing_foto']) && $p['existing_foto']) {
                $fotoPath = $p['existing_foto'];
            }

            $atribut = [
                'nama' => $p['nama'],
                'nisn' => $p['nisn'] ?? null,
                'foto' => $fotoPath,
            ];

            if ($anggota) {
                $anggota->update($atribut);
                $tersisa[] = $anggota->id;
            } else {
                $tersisa[] = Participant::create([
                    'registration_id' => $this->registration->id,
                ] + $atribut)->id;
            }
        }

        $this->registration->participants()
            ->when($tersisa, fn ($q) => $q->whereNotIn('id', $tersisa))
            ->delete();
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

    public function getJudgeNotesProperty()
    {
        return \App\Models\JudgeNote::where('registration_id', $this->activeRegId)
            ->with(['judge', 'competitionRound'])
            ->get();
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
        return \App\Models\ScoreDeduction::where('registration_id', $this->activeRegId)
            ->with(['deductionCriteria', 'assessmentCategory'])
            ->get();
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

    /**
     * Baris kartu "Dokumen & Unduhan": tiga berkas yang bisa diunduh pendaftar,
     * lengkap dengan keadaan terbuka/terkunci dan alasan tertulis.
     *
     * Gerbangnya sengaja disalin dari server — ParticipantController::
     * downloadFormulir menolak 403 selama berkas belum terverifikasi,
     * downloadInvoiceByToken 403 selama pembayaran belum lunas, dan sertifikat
     * butuh template aktif sekaligus fitur `certificate` pada paket event.
     * Alasannya memakai kalimat yang sama dengan pesan abort-nya, supaya
     * pendaftar membaca syarat yang benar-benar ditegakkan — bukan tombol yang
     * hilang tanpa keterangan.
     *
     * @return array<int, array{key:string,judul:string,ikon:string,cakupan:?string,
     *                          keterangan:string,terbuka:bool,berlaku:bool,url:?string,
     *                          tombol:string,aksen:?string,gelar:?string}>
     */
    #[Computed]
    public function unduhan(): array
    {
        $reg = $this->registration;
        $terverifikasi = $reg->status_berkas === 'Terverifikasi';
        $lunas = $reg->payment_status === 'paid';
        $gratis = $reg->payment_status === 'free';
        $cert = $this->certificate_registrations[$this->activeRegId] ?? null;

        $labelPasukan = trim((string) $reg->label_pasukan);
        $namaPasukan = $labelPasukan !== '' ? 'Pasukan ' . $labelPasukan : $reg->nama_sekolah;
        $jumlahAnggota = $reg->participants->count();
        $rincianFormulir = 'Data ' . $namaPasukan
            . ($jumlahAnggota > 0 ? ' — pelatih, danton, dan ' . $jumlahAnggota . ' anggota' : '')
            . '.';

        // Cakupan tiap berkas berbeda, dan itu pembeda yang paling mudah salah
        // paham: formulir & sertifikat milik tab yang dibuka, invoice digabung
        // seluruh sekolah.
        $ruangSertifikat = $reg->eventner->canAccessFeature('certificate');

        return [
            [
                'key' => 'formulir',
                'judul' => 'Formulir Pendaftaran',
                'ikon' => 'ti-file-description',
                'cakupan' => 'Pasukan ini',
                'keterangan' => $terverifikasi
                    ? $rincianFormulir
                    : 'Tersedia setelah berkas pendaftaran diverifikasi panitia.',
                'terbuka' => $terverifikasi,
                'berlaku' => true,
                'url' => $terverifikasi ? route('magic.link.formulir', $reg->magic_token) : null,
                'tombol' => 'Unduh Formulir',
                'aksen' => null,
                'gelar' => null,
            ],
            [
                'key' => 'invoice',
                'judul' => 'Invoice / Kwitansi',
                'ikon' => 'ti-file-invoice',
                'cakupan' => 'Seluruh sekolah',
                'keterangan' => $lunas
                    ? 'Satu berkas gabungan untuk ' . $this->jumlahPasukanLunas() . ' pasukan sekolah ini yang sudah dibayar.'
                    : ($gratis
                        ? 'Event ini tidak menarik biaya pendaftaran.'
                        : 'Tersedia setelah pembayaran diverifikasi panitia.'),
                'terbuka' => $lunas,
                'berlaku' => !$gratis,
                'url' => $lunas ? route('magic.link.invoice', $reg->magic_token) : null,
                'tombol' => 'Unduh Invoice',
                'aksen' => null,
                'gelar' => null,
            ],
            [
                'key' => 'sertifikat',
                'judul' => $cert
                    ? ($cert['is_champion'] ? 'Sertifikat Juara' : 'Sertifikat Peserta')
                    : 'Sertifikat',
                'ikon' => 'ti-certificate',
                'cakupan' => 'Pasukan ini',
                'keterangan' => $cert
                    ? ($cert['is_champion']
                        ? 'Selamat! Pasukan ini masuk jajaran juara — satu halaman per anggota.'
                        : 'Sertifikat keikutsertaan — satu halaman per anggota.')
                    : ($ruangSertifikat
                        ? 'Terbit setelah panitia memasang template sertifikat.'
                        : 'Fitur sertifikat belum aktif untuk event ini.'),
                'terbuka' => (bool) $cert,
                'berlaku' => $ruangSertifikat,
                // Sertifikat diunduh per mata lomba: rute ber-parameter ini juga
                // membuka sertifikat pasukan lain dari sekolah yang sama.
                'url' => $cert
                    ? route('magic.link.certificate.category', [$reg->magic_token, $cert['registration']->competition_category_id])
                    : null,
                'tombol' => 'Unduh Sertifikat',
                'aksen' => $cert && $cert['is_champion'] ? 'champion' : null,
                'gelar' => $cert['title'] ?? null,
            ],
        ];
    }

    /**
     * Jumlah pasukan sekolah ini yang pembayarannya sudah lunas — angka yang
     * sama dengan isi invoice gabungannya.
     *
     * Pencocokan sekolahnya mengikuti renderInvoice() apa adanya: NPSN bila
     * ada, kalau tidak lewat nama (kolom npsn nullable sejak 2026_09_21, dan
     * `= NULL` tak pernah cocok di MySQL).
     */
    private function jumlahPasukanLunas(): int
    {
        $reg = $this->registration;

        return RegistrationModel::where('eventner_id', $reg->eventner_id)
            ->when(
                $reg->npsn,
                fn ($q) => $q->where('npsn', $reg->npsn),
                fn ($q) => $q->whereNull('npsn')->where('nama_sekolah', $reg->nama_sekolah)
            )
            ->where('payment_status', 'paid')
            ->where('status_berkas', '!=', 'dibatalkan')
            ->count();
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

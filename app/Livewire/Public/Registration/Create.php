<?php

namespace App\Livewire\Public\Registration;

use App\Livewire\Concerns\MengelolaWilayah;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use App\Models\School;
use App\Services\MailyService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.frontend')]
class Create extends Component
{
    use WithFileUploads;
    use MengelolaWilayah;

    public $eventner;
    public $slug;

    // Step tracking
    public $step = 1;

    // Step 1: Category selection (multi)
    public $selectedCategories = []; // array of category IDs
    public $teamCounts = [];         // [categoryId => count]

    // Step 2: School data — field bawaan (kolom nyata di tabel registrations).
    // Properti ini wajib ada karena form dirender dinamis: field dengan
    // builtin_source = 'nama_sekolah' di-bind ke wire:model="nama_sekolah".
    public $npsn = '';
    public $nama_sekolah = '';
    public $label_pasukan = '';
    public $nama_pelatih = '';
    public $no_hp = '';
    public $school_email = '';

    /**
     * Nilai field buatan panitia (tanpa kolom di registrations), key = field_key.
     * Juga menampung field bawaan yang batas panjang/tipe-nya diatur builder.
     */
    public array $fieldValues = [];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
            $this->slug = $resolved->slug;
        } else {
            $this->slug = $slug;
            $this->eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        // Form dirender dari definisi field milik event ini. Event baru/event
        // lama yang belum pernah dibuka builder-nya diisi bawaan di sini.
        RegistrationField::ensureDefaults($this->eventner);

        $this->isiNilaiAwal();
        $this->muatWilayahTersimpan($this->fields);

        if ($this->eventner->tanggal_pendaftaran && now()->isAfter($this->eventner->tanggal_pendaftaran)) {
            session()->flash('error', 'Pendaftaran sudah ditutup.');
        }
    }

    /**
     * Field yang tampil di form publik.
     *
     * Tipe berkas (file/image) sengaja tidak di sini: unggahan baru bisa
     * dilakukan setelah booking, dari portal magic link yang diproteksi token.
     * Field group (daftar anggota) juga tidak di sini — anggotanya diisi di
     * portal, bukan saat booking.
     *
     * Field bawaan yang bukan milik form ini (danton, foto pelatih, logo)
     * tersaring dengan sendirinya: field itu baru dipakai kalau komponen ini
     * punya propertinya. Field baru buatan panitia (builtin_source null) tetap
     * ikut lewat $fieldValues.
     */
    #[Computed]
    public function fields()
    {
        return RegistrationField::forEventner($this->eventner)
            ->reject(fn ($f) => $f->isFile() || $f->isGroup())
            ->filter(fn ($f) => $f->builtin_source === null || property_exists($this, $f->builtin_source))
            ->values();
    }

    /** Nilai awal dari builder (default_value). */
    private function isiNilaiAwal(): void
    {
        foreach (RegistrationField::forEventner($this->eventner) as $field) {
            if ($field->isFile() || $field->isGroup() || ! $field->default_value) {
                continue;
            }

            if (! isset($this->fieldValues[$field->field_key])) {
                $this->fieldValues[$field->field_key] = $field->default_value;
            }
        }

        // Field bawaan juga punya properti sendiri (dipakai menulis kolom).
        // Hanya field yang benar-benar punya properti di komponen ini — field
        // berkas seperti logo_sekolah bukan bagian form publik.
        foreach ($this->fields as $field) {
            if (! $field->builtin_source || ! property_exists($this, $field->builtin_source)) {
                continue;
            }

            if ($this->{$field->builtin_source} === '') {
                continue;
            }

            $this->fieldValues[$field->field_key] = $this->{$field->builtin_source};
        }
    }

    /**
     * Nilai satu field — satu-satunya sumber selama pengisian formulir, baik
     * untuk field bawaan maupun field buatan panitia.
     */
    public function nilaiField(RegistrationField $field): string
    {
        return (string) ($this->fieldValues[$field->field_key]
            ?? $this->{$field->builtin_source ?? ''}
            ?? '');
    }

    /**
     * Aturan validasi untuk field form publik, dibangun dari DB.
     *
     * @return array<string, string>
     */
    private function aturanField(): array
    {
        $aturan = [];

        foreach ($this->fields as $field) {
            $aturanDasar = match ($field->builtin_source) {
                'school_email' => $field->is_required ? 'required|email|max:255' : 'nullable|email|max:255',
                default => $field->validationRule($field->is_required),
            };

            // Field wilayah dapat rule tambahan: memastikan yang dikirim memang
            // hasil pilihan dropdown, bukan kode yang disusun sendiri.
            if ($field->type === 'wilayah') {
                $this->tambahAturanWilayah($aturan, $field, $aturanDasar);
                continue;
            }

            // Field bawaan yang butuh format khusus tetap pakai aturan tetap.
            $aturan['fieldValues.' . $field->field_key] = $aturanDasar;
        }

        return $aturan;
    }

    /** Pesan error memakai label panitia, bukan nama properti Livewire. */
    private function pesanField(): array
    {
        $pesan = [];

        foreach ($this->fields as $field) {
            if ($field->builtin_source) {
                continue;
            }

            $pesan['fieldValues.' . $field->field_key . '.required'] = $field->label . ' wajib diisi.';
            $pesan['fieldValues.' . $field->field_key . '.in'] = 'Pilihan ' . $field->label . ' tidak valid.';
            $pesan['fieldValues.' . $field->field_key . '.numeric'] = $field->label . ' harus berupa angka.';
            $pesan['fieldValues.' . $field->field_key . '.date'] = $field->label . ' harus berupa tanggal yang valid.';
        }

        return $pesan;
    }

    /** Nilai awal field wilayah: isian yang sudah ada, atau kosong. */
    protected function nilaiWilayahAwal(RegistrationField $field): string
    {
        return (string) ($this->fieldValues[$field->field_key] ?? '');
    }

    /**
     * Salin nilai dari properti bawaan ke $fieldValues bila belum terisi.
     *
     * Form publik menulis langsung ke $fieldValues, tapi pemanggil lain
     * (test, integrasi) menyetel propertinya seperti dulu. Tanpa ini validasi
     * melihat form kosong.
     */
    private function sinkronPropertiKeField(): void
    {
        foreach ($this->fields as $field) {
            if (! $field->builtin_source) {
                continue;
            }

            $properti = $this->{$field->builtin_source} ?? '';
            $sekarang = $this->fieldValues[$field->field_key] ?? '';

            if (($sekarang === '' || $sekarang === null) && $properti !== '' && $properti !== null) {
                $this->fieldValues[$field->field_key] = $properti;
            }
        }
    }

    /**
     * Pindahkan nilai field ke propertinya masing-masing sebelum validasi.
     *
     * Validasi selalu lewat $fieldValues (satu jalur, rule dibangun di atas
     * field_key), sedangkan field bawaan tetap butuh nilai di propertinya
     * sendiri untuk ditulis ke kolom tabel registrations.
     */
    private function sinkronFieldKeProperti(): void
    {
        foreach ($this->fields as $field) {
            if (! $field->builtin_source) {
                continue;
            }

            $properti = $field->builtin_source;
            $nilai = $this->fieldValues[$field->field_key] ?? '';

            // NPSN punya autofill dari data sekolah — jangan ditimpa kosong.
            if ($properti === 'npsn' || $nilai !== '' || $this->{$properti} === '') {
                $this->{$properti} = $nilai;
            }
        }
    }

    /** Semua nilai field, key = field_key (dipakai saat menulis registrations). */
    private function nilaiSemuaField(): array
    {
        $nilai = [];

        foreach ($this->fields as $field) {
            $nilai[$field->field_key] = $field->builtin_source
                ? ($this->{$field->builtin_source} ?? null)
                : ($this->fieldValues[$field->field_key] ?? null);
        }

        return $nilai;
    }

    /**
     * Autofill dari data sekolah begitu NPSN diisi.
     *
     * Nilai form hidup di $fieldValues (satu jalur untuk semua field), jadi
     * pemicunya hook updatedFieldValues — bukan wire:blur. Livewire 3
     * melarang lifecycle hook dipanggil langsung dari view
     * (DirectlyCallingLifecycleHooksNotAllowedException).
     */
    public function updatedFieldValues($value, $key)
    {
        if ($key === 'npsn') {
            $this->autofillSekolah($value);
        }
    }

    /**
     * Dipanggil pemanggil lain (test, integrasi) yang menyetel properti $npsn
     * langsung sebagai ganti mengisi formulir.
     */
    public function updatedNpsn()
    {
        $this->autofillSekolah($this->npsn);
    }

    /** Isi data sekolah + sinkronkan ke field yang ada di formulir event ini. */
    private function autofillSekolah($nilai): void
    {
        $npsn = trim((string) $nilai);
        if ($npsn === '') return;

        $school = School::find($npsn);
        if (!$school) return;

        $this->npsn = $npsn;
        $this->nama_sekolah = $school->nama_sekolah;
        $this->no_hp = $school->no_hp;
        $this->school_email = $school->school_email;

        // Isi hanya field yang memang ada di formulir event ini.
        foreach ($this->fields as $field) {
            if (! $field->builtin_source) continue;

            $nilai = match ($field->builtin_source) {
                'npsn' => $npsn,
                'nama_sekolah' => $school->nama_sekolah,
                'no_hp' => $school->no_hp,
                'school_email' => $school->school_email,
                default => null,
            };

            if ($nilai !== null) {
                $this->fieldValues[$field->field_key] = $nilai;
            }
        }
    }

    public function lockedTingkat()
    {
        if (empty($this->selectedCategories)) return null;

        // Hanya kategori milik event ini — $selectedCategories datang dari DOM.
        $cats = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->whereIn('id', $this->selectedCategories)
            ->get();
        $names = $cats->pluck('name')->unique()->values();

        return $names->count() === 1 ? $names->first() : null;
    }

    public function toggleCategory($catId)
    {
        // Scope ke eventner: tanpa ini id kategori event lain bisa
        // ditambahkan ke keranjang dan ikut terdaftar di submit().
        // selectable(): id induk yang dikirim langsung dari DOM (mis. halaman
        // basi) juga ditolak — pendaftaran harus mendarat di tingkat lomba.
        $cat = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->selectable()
            ->find($catId);
        if (!$cat) return;

        if (in_array($catId, $this->selectedCategories)) {
            $this->selectedCategories = array_values(array_diff($this->selectedCategories, [$catId]));
            unset($this->teamCounts[$catId]);
        } else {
            $lockedTingkat = $this->lockedTingkat();
            // Lock to same tingkat (child name), not same parent
            if ($lockedTingkat && $cat->name !== $lockedTingkat) {
                return;
            }
            $this->selectedCategories[] = $catId;
            $this->teamCounts[$catId] = 1;
        }
    }

    public function setTeamCount($catId, $count)
    {
        $this->teamCounts[$catId] = max(1, (int) $count);
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            if (empty($this->selectedCategories)) {
                $this->addError('selectedCategories', 'Pilih minimal satu kategori lomba.');
                return;
            }
        }

        if ($this->step === 2) {
            $this->sinkronPropertiKeField();
            $this->validate($this->aturanField(), $this->pesanField());
            $this->sinkronFieldKeProperti();
        }

        $this->step++;
    }

    public function prevStep()
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit()
    {
        $this->sinkronPropertiKeField();
        $this->validate($this->aturanField(), $this->pesanField());
        $this->sinkronFieldKeProperti();

        // Gerbang terakhir: apa pun yang tersisa di keranjang, hanya kategori
        // milik event ini — dan hanya yang boleh dipilih (tingkat lomba) —
        // yang boleh dibuatkan pendaftaran.
        $categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->selectable()
            ->whereIn('id', $this->selectedCategories)
            ->get();
        if ($categories->isEmpty()) {
            $this->step = 1;
            return;
        }

        $fieldBaru = $this->fields->reject(fn ($f) => $f->builtin_source !== null);

        // Shared magic token for all registrations in this booking
        $sharedToken = Str::random(16);

        $createdCategories = [];
        $firstRegistration = null;

        foreach ($categories as $cat) {
            $count = $this->teamCounts[$cat->id] ?? 1;

            $existingCount = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $cat->id)
                ->where('npsn', $this->npsn)
                ->where('status_berkas', '!=', 'dibatalkan')
                ->count();

            $perSchoolLimit = ($cat->max_registrations_per_school ?? 1) - $existingCount;
            $allowed = max(0, min($perSchoolLimit, $cat->remainingSlots() ?: PHP_INT_MAX));
            $toCreate = min($count, $allowed);

            if ($toCreate <= 0) {
                continue;
            }

            for ($i = 0; $i < $toCreate; $i++) {
                $feeAmount = $cat->registration_fee;
                $reg = Registration::create([
                    'eventner_id' => $this->eventner->id,
                    'competition_category_id' => $cat->id,
                    'label_pasukan' => $this->labelPasukan($i, $toCreate),
                    'nama_sekolah' => strip_tags($this->nama_sekolah),
                    'npsn' => strip_tags($this->npsn),
                    'nama_pelatih' => $this->nama_pelatih ? strip_tags($this->nama_pelatih) : null,
                    'no_hp' => strip_tags($this->no_hp),
                    'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
                    'status_berkas' => 'booking',
                    'magic_token' => $sharedToken,
                    'total_fee' => $feeAmount,
                    'payment_status' => $feeAmount ? 'unpaid' : 'free',
                ]);

                foreach ($fieldBaru as $field) {
                    $nilai = $this->fieldValues[$field->field_key] ?? null;
                    if ($nilai === null || $nilai === '') {
                        continue;
                    }

                    RegistrationFieldValue::create([
                        'registration_id' => $reg->id,
                        'registration_field_id' => $field->id,
                        'value' => strip_tags((string) $nilai),
                    ]);
                }

                if (!$firstRegistration) {
                    $firstRegistration = $reg;
                }
            }

            // Sync/update School data (1× per kategori, tidak perlu per iterasi)
            $dataSekolah = [
                'nama_sekolah' => strip_tags($this->nama_sekolah),
                'no_hp' => strip_tags($this->no_hp),
                'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
            ];

            School::updateOrCreate(['npsn' => strip_tags($this->npsn)], $dataSekolah);

            $createdCategories[] = [
                'name' => $cat->full_name,
                'teams' => $toCreate,
            ];
        }

        if (!$firstRegistration) {
            $this->addError('selectedCategories', 'Slot untuk semua kategori yang dipilih sudah penuh.');
            $this->step = 1;
            return;
        }

        $magicLink = route('magic.link', ['token' => $sharedToken]);

        if ($this->school_email) {
            app(MailyService::class)->sendBookingConfirmation(
                strip_tags($this->school_email),
                strip_tags($this->nama_sekolah),
                $this->eventner->nama_event,
                $magicLink,
                $createdCategories,
                strip_tags($this->npsn),
                strip_tags($this->no_hp)
            );
        }

        return redirect($magicLink)
            ->with('success', 'Booking berhasil! Detail pendaftaran dan link upload berkas telah dikirim ke email sekolah Anda.');
    }

    /**
     * Nama pasukan untuk baris ke-$i dari $total baris.
     *
     * Kalau builder meminta "Nama Pasukan" dan pendaftar mengisinya, isian itu
     * dipakai apa adanya untuk satu pasukan, atau ditambah A/B/C bila sekolah
     * mendaftarkan beberapa pasukan sekaligus. Kalau tidak, label A/B/C dipakai
     * seperti dulu. Mengembalikan null untuk satu pasukan tanpa nama — kolom
     * `label_pasukan` sengaja tetap kosong supaya nama tampil tidak berubah.
     */
    private function labelPasukan(int $i, int $total): ?string
    {
        $isian = trim((string) ($this->fieldValues['label_pasukan'] ?? ''));
        $isian = $isian !== '' ? strip_tags($isian) : '';

        if ($total > 1) {
            return $isian !== '' ? $isian . ' ' . chr(65 + $i) : chr(65 + $i);
        }

        return $isian !== '' ? $isian : null;
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()
            ->selectable()
            ->with('parent')
            ->withCount('registrations')
            ->get();

        return view('livewire.public.registration.create', [
            'categories' => $categories,
        ])->title('Booking Pendaftaran - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}

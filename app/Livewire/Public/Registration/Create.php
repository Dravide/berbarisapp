<?php

namespace App\Livewire\Public\Registration;

use App\Models\Eventner;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use App\Models\School;
use App\Services\MailyService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

#[Layout('layouts.frontend')]
class Create extends Component
{
    use WithFileUploads;

    public $eventner;
    public $slug;

    // Step tracking
    public $step = 1;

    // Step 1: Category selection (multi)
    public $selectedCategories = []; // array of category IDs
    public $teamCounts = [];         // [categoryId => count]

    // Step 2: School data
    public $npsn = '';
    public $nama_sekolah = '';
    public $nama_pelatih = '';
    public $logo_sekolah;
    public $no_hp = '';
    public $school_email = '';

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
            $this->slug = $resolved->slug;
        } else {
            $this->slug = $slug;
            $this->eventner = Eventner::where('slug', $slug)->firstOrFail();
        }

        if ($this->eventner->tanggal_pendaftaran && now()->isAfter($this->eventner->tanggal_pendaftaran)) {
            session()->flash('error', 'Pendaftaran sudah ditutup.');
        }
    }

    public function updatedNpsn()
    {
        if (empty($this->npsn)) return;

        $school = School::find($this->npsn);
        if ($school) {
            $this->nama_sekolah = $school->nama_sekolah;
            $this->no_hp = $school->no_hp;
            $this->school_email = $school->school_email;
        }
    }

    public function lockedTingkat()
    {
        if (empty($this->selectedCategories)) return null;

        $cats = \App\Models\CompetitionCategory::whereIn('id', $this->selectedCategories)->get();
        $names = $cats->pluck('name')->unique()->values();

        return $names->count() === 1 ? $names->first() : null;
    }

    public function toggleCategory($catId)
    {
        $cat = \App\Models\CompetitionCategory::find($catId);
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
            $this->validate([
                'npsn' => 'required|string|max:20',
                'nama_sekolah' => 'required|string|max:255',
                'nama_pelatih' => 'required|string|max:255',
                'no_hp' => 'required|string|max:20',
                'school_email' => 'required|email|max:255',
            ], [
                'npsn.required' => 'NPSN wajib diisi.',
                'nama_sekolah.required' => 'Nama sekolah wajib diisi.',
                'nama_pelatih.required' => 'Nama pelatih wajib diisi.',
                'no_hp.required' => 'No HP wajib diisi.',
                'school_email.required' => 'Email wajib diisi.',
                'school_email.email' => 'Format email tidak valid.',
            ]);
        }

        $this->step++;
    }

    public function prevStep()
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit()
    {
        $this->validate([
            'npsn' => 'required|string|max:20',
            'nama_sekolah' => 'required|string|max:255',
            'nama_pelatih' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'school_email' => 'required|email|max:255',
        ]);

        $categories = CompetitionCategory::whereIn('id', $this->selectedCategories)->get();
        if ($categories->isEmpty()) {
            $this->step = 1;
            return;
        }

        $logoPath = null;
        if ($this->logo_sekolah) {
            $logoPath = $this->logo_sekolah->store('logos', 'public');
        }

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
                $label = $toCreate > 1 ? chr(65 + $i) : null;
                $feeAmount = $cat->registration_fee;
                $reg = Registration::create([
                    'eventner_id' => $this->eventner->id,
                    'competition_category_id' => $cat->id,
                    'label_pasukan' => $label,
                    'nama_sekolah' => strip_tags($this->nama_sekolah),
                    'npsn' => strip_tags($this->npsn),
                    'nama_pelatih' => $this->nama_pelatih ? strip_tags($this->nama_pelatih) : null,
                    'no_hp' => strip_tags($this->no_hp),
                    'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
                    'logo_sekolah' => $logoPath,
                    'status_berkas' => 'booking',
                    'magic_token' => $sharedToken,
                    'total_fee' => $feeAmount,
                    'payment_status' => $feeAmount ? 'unpaid' : 'free',
                ]);

                if (!$firstRegistration) {
                    $firstRegistration = $reg;
                }
            }

            // Sync/update School data (1× per kategori, tidak perlu per iterasi)
            School::updateOrCreate(
                ['npsn' => strip_tags($this->npsn)],
                [
                    'nama_sekolah' => strip_tags($this->nama_sekolah),
                    'logo_sekolah' => $logoPath,
                    'no_hp' => strip_tags($this->no_hp),
                    'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
                ]
            );

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

    public function render()
    {
        $categories = $this->eventner->competitionCategories()
            ->where(function ($q) {
                // Child categories (hierarchy)
                $q->whereNotNull('parent_id');
                // OR: parent categories that have no children (old flat data)
                $q->orWhere(function ($sq) {
                    $sq->whereNull('parent_id')
                       ->whereDoesntHave('children');
                });
            })
            ->with('parent')
            ->withCount('registrations')
            ->get();

        return view('livewire.public.registration.create', [
            'categories' => $categories,
        ])->title('Booking Pendaftaran - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}

<?php

namespace App\Livewire\Public\Registration;

use App\Models\Eventner;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use Illuminate\Support\Facades\Hash;
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

    // Step 1: Category selection
    public $selectedCategories = [];
    public $teamCounts = [];

    // Step 2: School data
    public $npsn = '';
    public $nama_sekolah = '';
    public $nama_pelatih = '';
    public $logo_sekolah;
    public $no_hp = '';
    public $school_email = '';
    public $password = '';
    public $password_confirmation = '';

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();

        // Check registration deadline
        if ($this->eventner->tanggal_pendaftaran && now()->isAfter($this->eventner->tanggal_pendaftaran)) {
            session()->flash('error', 'Pendaftaran sudah ditutup.');
        }
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'selectedCategories' => 'required|array|min:1',
            ], [
                'selectedCategories.required' => 'Pilih minimal satu kategori lomba.',
            ]);

            foreach ($this->selectedCategories as $catId) {
                if (!isset($this->teamCounts[$catId]) || $this->teamCounts[$catId] < 1) {
                    $this->teamCounts[$catId] = 1;
                }
            }
        }

        if ($this->step === 2) {
            $this->validate([
                'npsn' => 'required|string|max:20',
                'nama_sekolah' => 'required|string|max:255',
                'no_hp' => 'required|string|max:20',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'npsn.required' => 'NPSN wajib diisi.',
                'nama_sekolah.required' => 'Nama sekolah wajib diisi.',
                'no_hp.required' => 'No HP wajib diisi.',
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 6 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
            ]);
        }

        $this->step++;
    }

    public function prevStep()
    {
        $this->step = max(1, $this->step - 1);
    }

    public function getMaxProperty($catId)
    {
        $cat = $this->eventner->competitionCategories->firstWhere('id', $catId);
        return $cat ? ($cat->max_registrations_per_school ?? 1) : 1;
    }

    public function submit()
    {
        $this->validate([
            'npsn' => 'required|string|max:20',
            'nama_sekolah' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $created = [];
        foreach ($this->selectedCategories as $catId) {
            $cat = CompetitionCategory::find($catId);
            if (!$cat) continue;

            $count = $this->teamCounts[$catId] ?? 1;
            $existingCount = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $catId)
                ->where('npsn', $this->npsn)
                ->where('status_berkas', '!=', 'dibatalkan')
                ->count();

            $allowed = max(0, ($cat->max_registrations_per_school ?? 1) - $existingCount);
            $toCreate = min($count, $allowed, $cat->remainingSlots());

            if ($toCreate <= 0) {
                $this->addError('selectedCategories', "Slot untuk {$cat->name} sudah penuh.");
                continue;
            }

            $logoPath = null;
            if ($this->logo_sekolah) {
                $logoPath = $this->logo_sekolah->store('logos', 'public');
            }

            for ($i = 0; $i < $toCreate; $i++) {
                $suffix = $toCreate > 1 ? ' (Pasukan ' . ($i + 1) . ')' : '';
                $created[] = Registration::create([
                    'eventner_id' => $this->eventner->id,
                    'competition_category_id' => $catId,
                    'nama_sekolah' => strip_tags($this->nama_sekolah) . $suffix,
                    'npsn' => strip_tags($this->npsn),
                    'nama_pelatih' => $this->nama_pelatih ? strip_tags($this->nama_pelatih) : null,
                    'no_hp' => strip_tags($this->no_hp),
                    'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
                    'logo_sekolah' => $logoPath,
                    'password' => Hash::make($this->password),
                    'status_berkas' => 'booking',
                ]);
            }
        }

        if (empty($created)) {
            $this->step = 1;
            return;
        }

        // Redirect to magic link of first registration
        $first = $created[0];
        return redirect()->route('magic.link', ['token' => $first->magic_token])
            ->with('success', 'Booking berhasil! Silakan kelola data pasukan Anda.');
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()->withCount('registrations')->get();

        return view('livewire.public.registration.create', [
            'categories' => $categories,
        ])->title('Booking Pendaftaran - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}

<?php

namespace App\Livewire\Public\Registration;

use App\Models\Eventner;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use App\Services\MailyService;
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
    public $selectedCategory = null;
    public $teamCount = 1;

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
                'selectedCategory' => 'required',
            ], [
                'selectedCategory.required' => 'Pilih satu kategori lomba.',
            ]);

            if ($this->teamCount < 1) {
                $this->teamCount = 1;
            }
        }

        if ($this->step === 2) {
            $this->validate([
                'npsn' => 'required|string|max:20',
                'nama_sekolah' => 'required|string|max:255',
                'no_hp' => 'required|string|max:20',
                'school_email' => 'required|email|max:255',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'npsn.required' => 'NPSN wajib diisi.',
                'nama_sekolah.required' => 'Nama sekolah wajib diisi.',
                'no_hp.required' => 'No HP wajib diisi.',
                'school_email.required' => 'Email sekolah wajib diisi.',
                'school_email.email' => 'Format email tidak valid.',
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
            'school_email' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $cat = CompetitionCategory::find($this->selectedCategory);
        if (!$cat) {
            $this->step = 1;
            return;
        }

        $count = $this->teamCount;
        $existingCount = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $cat->id)
            ->where('npsn', $this->npsn)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->count();

        $allowed = max(0, ($cat->max_registrations_per_school ?? 1) - $existingCount);
        $toCreate = min($count, $allowed, $cat->remainingSlots());

        if ($toCreate <= 0) {
            $this->addError('selectedCategory', "Slot untuk {$cat->name} sudah penuh.");
            $this->step = 1;
            return;
        }

        $logoPath = null;
        if ($this->logo_sekolah) {
            $logoPath = $this->logo_sekolah->store('logos', 'public');
        }

        $created = [];
        for ($i = 0; $i < $toCreate; $i++) {
            $suffix = $toCreate > 1 ? ' (' . chr(65 + $i) . ')' : '';
            $created[] = Registration::create([
                'eventner_id' => $this->eventner->id,
                'competition_category_id' => $cat->id,
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

        $first = $created[0];
        $magicLink = route('magic.link', ['token' => $first->magic_token]);

        app(MailyService::class)->sendBookingConfirmation(
            strip_tags($this->school_email),
            strip_tags($this->nama_sekolah),
            $this->eventner->nama_event,
            $magicLink,
            [['name' => $cat->name, 'teams' => $toCreate]],
            strip_tags($this->npsn),
            strip_tags($this->no_hp)
        );

        return redirect($magicLink)
            ->with('success', 'Booking berhasil! Detail pendaftaran dan link upload berkas telah dikirim ke email sekolah Anda.');
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

<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventners;
    public $search = '';

    // Form fields
    public $eventnerId = null;
    public $nama_event = '';
    public $diselenggarakan_oleh = '';
    public $lokasi = '';
    public $venue = '';
    public $tanggal = '';
    public $tanggal_akhir = '';
    public $tanggal_pendaftaran = '';
    public $technical_meeting = '';
    public $tingkat_perlombaan = '';
    public $saas_plan_id = '';

    // User fields
    public $username = '';
    public $email = '';

    // Boolean for update mode
    public $isEditMode = false;

    public function mount()
    {
        $this->loadEventners();
    }

    /**
     * Paket yang boleh dipasang admin. Paket "hubungi admin" (is_contact)
     * adalah jalur prospek, bukan paket yang bisa di-assign.
     */
    public function getPlansProperty()
    {
        return SaasPlan::where('is_active', true)
            ->where('is_contact', false)
            ->orderBy('sort_order')
            ->get();
    }

    public function loadEventners()
    {
        $this->eventners = Eventner::with(['user', 'saasPlan'])
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->where('nama_event', 'like', '%' . $this->search . '%')
                  ->orWhere('diselenggarakan_oleh', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.eventner.index')->title('Kelola Eventner - ' . app_name());
    }

    public function resetForm()
    {
        $this->reset(['eventnerId', 'nama_event', 'diselenggarakan_oleh', 'lokasi', 'venue', 'tanggal', 'tanggal_akhir', 'tanggal_pendaftaran', 'technical_meeting', 'tingkat_perlombaan', 'saas_plan_id', 'username', 'email', 'isEditMode']);
        $this->resetValidation();
    }

    public function save()
    {
        // Convert empty string to null for nullable date fields
        $this->tanggal_akhir = $this->tanggal_akhir ?: null;

        $rules = [
            'nama_event' => 'required|string|max:255',
            'diselenggarakan_oleh' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'venue' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'tanggal_pendaftaran' => 'nullable|string|max:255',
            'technical_meeting' => 'nullable|string|max:255',
            'tingkat_perlombaan' => 'nullable|string|max:255',
        ];

        // Specific rules depending on whether it's create or update
        if ($this->isEditMode) {
            $eventner = Eventner::findOrFail($this->eventnerId);
            $userId = $eventner->user_id;

            $rules['username'] = "required|string|max:255|unique:users,username,{$userId}";
            $rules['email'] = "required|email|max:255|unique:users,email,{$userId}";
        } else {
            $rules['username'] = 'required|string|max:255|unique:users';
            $rules['email'] = 'required|email|max:255|unique:users';
            // Paket wajib dipilih saat membuat. Pengubahannya di halaman detail.
            // Paket "hubungi admin" (is_contact) bukan paket yang bisa di-assign.
            // Kondisi where ditulis 0/1, bukan false/true: cast binding Laravel
            // mengubah false menjadi string kosong, dan SQLite tidak pernah
            // mencocokkan '' dengan kolom boolean bernilai 0 (MySQL lolos karena
            // memaksa '' jadi angka).
            $rules['saas_plan_id'] = [
                'required',
                Rule::exists('saas_plans', 'id')->where('is_contact', 0)->where('is_active', 1),
            ];
        }

        $this->validate($rules);

        if ($this->isEditMode) {
            $eventner = Eventner::findOrFail($this->eventnerId);
            
            // Update User
            $user = User::findOrFail($eventner->user_id);
            $user->update([
                'name' => $this->diselenggarakan_oleh, // Set panitia name to organizer name
                'username' => $this->username,
                'email' => $this->email,
            ]);

            // Update Eventner
            $eventner->update([
                'nama_event' => $this->nama_event,
                'diselenggarakan_oleh' => $this->diselenggarakan_oleh,
                'lokasi' => $this->lokasi,
                'venue' => $this->venue,
                'tanggal' => $this->tanggal,
                'tanggal_akhir' => $this->tanggal_akhir ?: null,
                'tanggal_pendaftaran' => $this->tanggal_pendaftaran,
                'technical_meeting' => $this->technical_meeting,
                'tingkat_perlombaan' => $this->tingkat_perlombaan,
            ]);

            session()->flash('success', 'Data Eventner berhasil diperbarui.');
        } else {
            // Create User first
            $user = User::create([
                'name' => $this->diselenggarakan_oleh,
                'username' => $this->username,
                'email' => $this->email,
                'password' => Hash::make('password'), // default password
                'role' => 'Eventner',
            ]);

            // Create Eventner — paket dipilih admin, langsung aktif tanpa QRIS
            $eventner = Eventner::create([
                'user_id' => $user->id,
                'status' => 'approved',
                'nama_event' => $this->nama_event,
                'diselenggarakan_oleh' => $this->diselenggarakan_oleh,
                'lokasi' => $this->lokasi,
                'venue' => $this->venue,
                'tanggal' => $this->tanggal,
                'tanggal_akhir' => $this->tanggal_akhir ?: null,
                'tanggal_pendaftaran' => $this->tanggal_pendaftaran,
                'technical_meeting' => $this->technical_meeting,
                'tingkat_perlombaan' => $this->tingkat_perlombaan,
            ]);

            $eventner->assignPlan(SaasPlan::findOrFail($this->saas_plan_id), 'admin');

            session()->flash('success', 'Data Eventner dan Akun User (Password default: password) berhasil dibuat.');
        }

        $this->dispatch('close-modal');
        $this->loadEventners();
        $this->resetForm();
    }

    public function edit($id)
    {
        $this->isEditMode = true;
        
        $eventner = Eventner::with('user')->findOrFail($id);
        
        $this->eventnerId = $eventner->id;
        $this->nama_event = $eventner->nama_event;
        $this->diselenggarakan_oleh = $eventner->diselenggarakan_oleh;
        $this->lokasi = $eventner->lokasi;
        $this->venue = $eventner->venue;
        $this->tanggal = $eventner->tanggal;
        $this->tanggal_akhir = $eventner->tanggal_akhir;
        $this->tanggal_pendaftaran = $eventner->tanggal_pendaftaran;
        $this->technical_meeting = $eventner->technical_meeting;
        $this->tingkat_perlombaan = $eventner->tingkat_perlombaan;
        
        $this->username = $eventner->user->username;
        $this->email = $eventner->user->email;

        $this->resetValidation();
        $this->dispatch('open-modal');
    }

    public function delete($id)
    {
        $eventner = Eventner::findOrFail($id);
        $userId = $eventner->user_id;

        // Deleting the user will cascade delete the eventner because of foreign key constraint
        User::findOrFail($userId)->delete();

        session()->flash('success', 'Data Eventner dan Akun User berhasil dihapus.');
        $this->loadEventners();
    }
}

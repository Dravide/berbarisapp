<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Kelola Eventner - BARIS APP')]
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
    public $tanggal_pendaftaran = '';
    public $technical_meeting = '';
    public $tingkat_perlombaan = '';
    
    // User fields
    public $username = '';
    public $email = '';

    // Boolean for update mode
    public $isEditMode = false;

    public function mount()
    {
        $this->loadEventners();
    }

    public function loadEventners()
    {
        $this->eventners = Eventner::with('user')
            ->where('nama_event', 'like', '%' . $this->search . '%')
            ->orWhere('diselenggarakan_oleh', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.eventner.index');
    }

    public function resetForm()
    {
        $this->reset(['eventnerId', 'nama_event', 'diselenggarakan_oleh', 'lokasi', 'venue', 'tanggal', 'tanggal_pendaftaran', 'technical_meeting', 'tingkat_perlombaan', 'username', 'email', 'isEditMode']);
        $this->resetValidation();
    }

    public function save()
    {
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
                'tanggal_pendaftaran' => $this->tanggal_pendaftaran,
                'technical_meeting' => $this->technical_meeting,
                'tingkat_perlombaan' => $this->tingkat_perlombaan,
            ]);

            session()->flash('message', 'Data Eventner berhasil diperbarui.');
        } else {
            // Create User first
            $user = User::create([
                'name' => $this->diselenggarakan_oleh,
                'username' => $this->username,
                'email' => $this->email,
                'password' => Hash::make('password'), // default password
                'role' => 'Eventner',
            ]);

            // Create Eventner
            Eventner::create([
                'user_id' => $user->id,
                'nama_event' => $this->nama_event,
                'diselenggarakan_oleh' => $this->diselenggarakan_oleh,
                'lokasi' => $this->lokasi,
                'venue' => $this->venue,
                'tanggal' => $this->tanggal,
                'tanggal_pendaftaran' => $this->tanggal_pendaftaran,
                'technical_meeting' => $this->technical_meeting,
                'tingkat_perlombaan' => $this->tingkat_perlombaan,
            ]);

            session()->flash('message', 'Data Eventner dan Akun User (Password default: password) berhasil dibuat.');
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

        session()->flash('message', 'Data Eventner dan Akun User berhasil dihapus.');
        $this->loadEventners();
    }
}

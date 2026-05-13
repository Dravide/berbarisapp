<?php

namespace App\Livewire\Admin\User;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Manajemen User - BARIS APP')]
class Index extends Component
{
    public $users;
    public $search = '';
    public $filterRole = '';

    // Form fields
    public $userId = null;
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $role = 'Eventner';
    public $is_active = true;
    public $isEditMode = false;

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('username', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterRole) {
            $query->where('role', $this->filterRole);
        }

        $this->users = $query->orderBy('id', 'desc')->get();
    }

    public function updatedSearch()
    {
        $this->loadUsers();
    }

    public function updatedFilterRole()
    {
        $this->loadUsers();
    }

    public function render()
    {
        return view('livewire.admin.user.index');
    }

    public function resetForm()
    {
        $this->reset(['userId', 'name', 'username', 'email', 'password', 'role', 'is_active', 'isEditMode']);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'role' => 'required|in:Admin,Eventner',
            'is_active' => 'boolean',
        ];

        if ($this->isEditMode) {
            $userId = $this->userId;
            $rules['username'] = "required|string|max:255|unique:users,username,{$userId}";
            $rules['email'] = "required|email|max:255|unique:users,email,{$userId}";
            $rules['password'] = 'nullable|string|min:6';
        } else {
            $rules['username'] = 'required|string|max:255|unique:users';
            $rules['email'] = 'required|email|max:255|unique:users';
            $rules['password'] = 'required|string|min:6';
        }

        $this->validate($rules);

        if ($this->isEditMode) {
            $user = User::findOrFail($this->userId);
            $data = [
                'name' => strip_tags($this->name),
                'username' => strip_tags($this->username),
                'email' => strip_tags($this->email),
                'role' => $this->role,
                'is_active' => $this->is_active,
            ];

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $user->update($data);
            session()->flash('message', 'Data user berhasil diperbarui.');
        } else {
            User::create([
                'name' => strip_tags($this->name),
                'username' => strip_tags($this->username),
                'email' => strip_tags($this->email),
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'User baru berhasil dibuat.');
        }

        $this->dispatch('close-modal');
        $this->loadUsers();
        $this->resetForm();
    }

    public function edit($id)
    {
        $this->isEditMode = true;
        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->is_active = $user->is_active;
        $this->password = '';

        $this->resetValidation();
        $this->dispatch('open-modal');
    }

    public function delete($id)
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun sendiri.');
            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('message', 'User berhasil dihapus.');
        $this->loadUsers();
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        if ($id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('message', "User {$user->name} berhasil {$status}.");
        $this->loadUsers();
    }

    public function resetPassword($id)
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'Gunakan fitur ubah password untuk akun sendiri.');
            return;
        }

        $user = User::findOrFail($id);
        $user->update(['password' => Hash::make('password')]);
        session()->flash('message', "Password user {$user->name} berhasil direset ke default.");
        $this->loadUsers();
    }
}

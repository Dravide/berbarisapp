<?php

namespace App\Livewire\Eventner\Settings;

use App\Models\EventnerBankAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Rekening Bank - BARIS APP')]
class BankAccount extends Component
{
    public $bank_name = '';
    public $account_number = '';
    public $account_name = '';
    public $is_active = true;

    public $isEditMode = false;
    public $editingId = null;

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);
        $this->eventnerId = $eventner->id;
    }

    public function getAccountsProperty()
    {
        return EventnerBankAccount::where('eventner_id', $this->eventnerId)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function save()
    {
        $this->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
        ]);

        if ($this->isEditMode && $this->editingId) {
            $acc = EventnerBankAccount::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $acc->update([
                'bank_name' => strip_tags($this->bank_name),
                'account_number' => strip_tags($this->account_number),
                'account_name' => strip_tags($this->account_name),
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', 'Rekening berhasil diperbarui.');
        } else {
            EventnerBankAccount::create([
                'eventner_id' => $this->eventnerId,
                'bank_name' => strip_tags($this->bank_name),
                'account_number' => strip_tags($this->account_number),
                'account_name' => strip_tags($this->account_name),
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', 'Rekening baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $acc = EventnerBankAccount::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $acc->id;
        $this->bank_name = $acc->bank_name;
        $this->account_number = $acc->account_number;
        $this->account_name = $acc->account_name;
        $this->is_active = $acc->is_active;
    }

    public function delete($id)
    {
        EventnerBankAccount::where('eventner_id', $this->eventnerId)->findOrFail($id)->delete();
        session()->flash('success', 'Rekening berhasil dihapus.');
    }

    public function toggleActive($id)
    {
        $acc = EventnerBankAccount::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $acc->is_active = !$acc->is_active;
        $acc->save();
    }

    public function resetForm()
    {
        $this->reset(['bank_name', 'account_number', 'account_name', 'is_active', 'isEditMode', 'editingId']);
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.eventner.settings.bank-account');
    }
}

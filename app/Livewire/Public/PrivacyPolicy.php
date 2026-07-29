<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.landing')]
#[Title('Kebijakan Privasi - BARIS APP')]
class PrivacyPolicy extends Component
{
    public function render()
    {
        return view('livewire.public.privacy-policy');
    }
}

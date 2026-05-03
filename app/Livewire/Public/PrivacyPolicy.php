<?php

namespace App\Livewire\Public;

use Livewire\Component;

class PrivacyPolicy extends Component
{
    public function render()
    {
        return view('livewire.public.privacy-policy')
            ->layout('layouts.zubaz')
            ->title('Kebijakan Privasi - ' . get_setting('site_title', 'BARIS APP'));
    }
}

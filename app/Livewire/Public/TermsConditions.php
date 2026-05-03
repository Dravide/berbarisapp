<?php

namespace App\Livewire\Public;

use Livewire\Component;

class TermsConditions extends Component
{
    public function render()
    {
        return view('livewire.public.terms-conditions')
            ->layout('layouts.zubaz')
            ->title('Syarat & Ketentuan - ' . get_setting('site_title', 'BARIS APP'));
    }
}

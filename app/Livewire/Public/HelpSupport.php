<?php

namespace App\Livewire\Public;

use Livewire\Component;

class HelpSupport extends Component
{
    public function render()
    {
        return view('livewire.public.help-support')
            ->layout('layouts.zubaz')
            ->title('Bantuan & Support - ' . get_setting('site_title', 'BARIS APP'));
    }
}

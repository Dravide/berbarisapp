<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Support\Pricing;

#[Layout('layouts.frontend')]
class PricingPage extends Component
{
    public function render()
    {
        return view('livewire.public.pricing-page', [
            'plans' => Pricing::plans(),
        ])->title('Harga & Paket - ' . app_name());
    }
}

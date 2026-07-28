<?php

namespace App\Providers;

use App\Models\Eventner;
use App\Models\Registration;
use App\Policies\EventnerPolicy;
use App\Policies\RegistrationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Eventner::class => EventnerPolicy::class,
        Registration::class => RegistrationPolicy::class,
    ];

    public function boot(): void
    {
        // Admin role bypasses all policies
        Gate::before(function ($user) {
            if ($user->role === 'Admin') {
                return true;
            }
        });
    }
}

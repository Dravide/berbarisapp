<?php

namespace App\Providers;

use App\Models\DeviceToken;
use App\Models\Registration;
use App\Models\Ticket;
use App\Observers\RegistrationObserver;
use App\Observers\TicketObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Default binding: current_eventner = null (overridden by ResolveEventnerSubdomain middleware)
        $this->app->instance('current_eventner', null);

        // FCM observers — auto-trigger push notification saat data berubah
        Registration::observe(RegistrationObserver::class);
        Ticket::observe(TicketObserver::class);
    }
}

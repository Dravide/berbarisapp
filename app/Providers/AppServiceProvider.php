<?php

namespace App\Providers;

use App\Models\DeviceToken;
use App\Models\Registration;
use App\Models\Ticket;
use App\Observers\RegistrationObserver;
use App\Observers\TicketObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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

        // Jejak login/logout ke activity_log. Bukan hanya info: admin
        // memakainya untuk melihat kapan akun panitia terakhir aktif.
        Event::listen(Login::class, function (Login $e) {
            activity()
                ->by($e->user)
                ->event('login')
                ->log('Akun berhasil masuk (login).');
        });

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                activity()
                    ->by($e->user)
                    ->event('logout')
                    ->log('Akun keluar (logout).');
            }
        });
    }
}

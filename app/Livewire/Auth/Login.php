<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

#[Layout('layouts.auth')]
#[Title('Login - BARIS APP')]
class Login extends Component
{
    public $login = '';
    public $password = '';
    public $remember = false;

    public function authenticate()
    {
        $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $throttleKey = strtolower($this->login) . '|' . request()->ip();

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.",
            ]);
        }

        // Determine if user is logging in with email or username
        $fieldType = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $this->login,
            'password' => $this->password,
        ];

        if (Auth::attempt($credentials, $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        // Increment failed attempts
        RateLimiter::hit($throttleKey, 60); // lockout for 60 seconds

        $this->addError('login', 'Username/email atau password salah.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

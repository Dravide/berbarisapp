<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

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

        // Determine if user is logging in with email or username
        $fieldType = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $this->login,
            'password' => $this->password,
        ];

        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        $this->addError('login', 'Username/email atau password salah.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

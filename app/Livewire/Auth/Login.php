<?php

namespace App\Livewire\Auth;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
        }

        if (! auth()->user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Ce compte est désactivé.']);
        }

        request()->session()->regenerate();
        ActivityLog::record('login', 'Connexion à SmartGestion');

        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')->title('Connexion');
    }
}

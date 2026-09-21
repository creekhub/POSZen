<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $password = '';
    public bool $remember = false;
    public string $error = '';

    protected array $rules = [
        'password' => ['required', 'string'],
    ];

    public function login(): void
    {
        $this->validate();

        $passwordHash = hash('sha256', $this->password);

        $user = User::query()
            ->whereRaw('LOWER("Password") = ?', [$passwordHash])
            ->where('IsEnabled', 1)
            ->first();

        if ($user) {
            Auth::login($user, $this->remember);
            request()->session()->regenerate();
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->error = 'These credentials do not match our records.';
    }

    public function render()
    {
        return view('livewire.login');
    }
}

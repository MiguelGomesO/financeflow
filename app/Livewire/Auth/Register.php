<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Criar conta · FinanceFlow')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function register(): void
    {
        $data = $this->validate(
            [
                'name' => ['required', 'string', 'max:60'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
                'passwordConfirmation' => ['required'],
            ],
            [
                'name.required' => 'Informe seu nome.',
                'name.max' => 'Use no máximo 60 caracteres.',
                'email.required' => 'Informe o e-mail.',
                'email.email' => 'E-mail inválido.',
                'email.unique' => 'Este e-mail já tem cadastro.',
                'password.required' => 'Crie uma senha.',
                'password.min' => 'Use pelo menos 8 caracteres.',
                'password.same' => 'As senhas não são iguais.',
                'passwordConfirmation.required' => 'Repita a senha.',
            ],
        );

        $user = User::create([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}

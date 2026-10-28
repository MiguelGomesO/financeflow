<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Perfil · FinanceFlow')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    /** Receber e-mail quando um orçamento chegar a 80% ou 100% do limite. */
    public bool $budgetAlerts = true;

    /** Foto escolhida (envio temporário do Livewire). */
    public $photo = null;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->name = $this->user()->name;
        $this->email = $this->user()->email;
        $this->budgetAlerts = $this->user()->budget_alerts;
    }

    public function saveProfile(): void
    {
        $data = $this->validate(
            [
                'name' => ['required', 'string', 'max:60'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            ],
            [
                'name.required' => 'Informe seu nome.',
                'name.max' => 'Use no máximo 60 caracteres.',
                'email.required' => 'Informe o e-mail.',
                'email.email' => 'E-mail inválido.',
                'email.unique' => 'Este e-mail já tem cadastro.',
            ],
        );

        $this->user()->update([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'budget_alerts' => $this->budgetAlerts,
        ]);

        $this->done('Perfil atualizado.');
    }

    /** Salva a foto assim que ela é escolhida. */
    public function updatedPhoto(): void
    {
        $this->validate(
            ['photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            [
                'photo.image' => 'Escolha uma imagem.',
                'photo.mimes' => 'Use JPG, PNG ou WebP.',
                'photo.max' => 'A imagem pode ter no máximo 2 MB.',
                'photo.uploaded' => 'Não foi possível enviar a imagem (máximo de 2 MB).',
            ],
        );

        $user = $this->user();
        $path = $this->photo->storePublicly('avatars', 'public');

        $this->deleteStoredPhoto($user);
        $user->update(['avatar_path' => $path]);

        $this->done('Foto atualizada.');
    }

    public function removePhoto(): void
    {
        $user = $this->user();

        $this->deleteStoredPhoto($user);
        $user->update(['avatar_path' => null]);

        $this->done('Foto removida.');
    }

    public function savePassword(): void
    {
        $this->validate(
            [
                'currentPassword' => ['required', 'current_password'],
                'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
                'newPasswordConfirmation' => ['required'],
            ],
            [
                'currentPassword.required' => 'Informe a senha atual.',
                'currentPassword.current_password' => 'A senha atual está incorreta.',
                'newPassword.required' => 'Crie a nova senha.',
                'newPassword.min' => 'Use pelo menos 8 caracteres.',
                'newPassword.same' => 'As senhas não são iguais.',
                'newPasswordConfirmation.required' => 'Repita a nova senha.',
            ],
        );

        $this->user()->update(['password' => $this->newPassword]);

        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');

        $this->done('Senha alterada.');
    }

    public function render()
    {
        return view('livewire.profile', [
            'user' => $this->user(),
            'stats' => [
                'Contas' => Account::count(),
                'Cartões' => CreditCard::count(),
                'Transações' => Transaction::count(),
                'Metas' => Goal::count(),
            ],
        ]);
    }

    private function user(): User
    {
        return Auth::user();
    }

    private function deleteStoredPhoto(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }

    /** Recarrega a página para o menu lateral também mostrar o nome e a foto novos. */
    private function done(string $message): void
    {
        session()->flash('status', $message);

        $this->redirect(route('profile'), navigate: true);
    }
}

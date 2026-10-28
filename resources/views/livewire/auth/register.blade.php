<div class="flex flex-col gap-6">
    <div>
        <h1 class="font-display text-[26px] font-semibold tracking-tight">Criar conta</h1>
        <p class="text-muted">Comece a controlar suas finanças</p>
    </div>

    <form wire:submit="register" class="flex flex-col gap-4">
        <x-ui.field label="Nome" for="register-name" error="name">
            <input
                id="register-name"
                type="text"
                wire:model="name"
                autocomplete="name"
                maxlength="60"
                autofocus
                class="input"
                @error('name') aria-invalid="true" aria-describedby="register-name-error" @enderror
            >
        </x-ui.field>

        <x-ui.field label="E-mail" for="register-email" error="email">
            <input
                id="register-email"
                type="email"
                wire:model="email"
                autocomplete="email"
                class="input"
                @error('email') aria-invalid="true" aria-describedby="register-email-error" @enderror
            >
        </x-ui.field>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field label="Senha" for="register-password" error="password">
                <input
                    id="register-password"
                    type="password"
                    wire:model="password"
                    autocomplete="new-password"
                    class="input"
                    @error('password') aria-invalid="true" aria-describedby="register-password-error" @enderror
                >
            </x-ui.field>

            <x-ui.field label="Repita a senha" for="register-password-confirmation" error="passwordConfirmation">
                <input
                    id="register-password-confirmation"
                    type="password"
                    wire:model="passwordConfirmation"
                    autocomplete="new-password"
                    class="input"
                    @error('passwordConfirmation') aria-invalid="true" aria-describedby="register-password-confirmation-error" @enderror
                >
            </x-ui.field>
        </div>

        <p class="-mt-2 text-[13px] text-muted">Use pelo menos 8 caracteres.</p>

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="register"
        >
            <span wire:loading.remove wire:target="register">Criar conta</span>
            <span wire:loading wire:target="register">Criando…</span>
        </button>
    </form>

    <p class="text-center text-sm text-muted">
        Já tem conta?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-fg hover:text-brand-dark-fg">Entrar</a>
    </p>
</div>

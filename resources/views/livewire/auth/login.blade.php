<div class="flex flex-col gap-6">
    <div>
        <h1 class="font-display text-[26px] font-semibold tracking-tight">Entrar</h1>
        <p class="text-muted">Acesse suas finanças</p>
    </div>

    <form wire:submit="login" class="flex flex-col gap-4">
        <x-ui.field label="E-mail" for="login-email" error="email">
            <input
                id="login-email"
                type="email"
                wire:model="email"
                autocomplete="email"
                autofocus
                class="input"
                @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror
            >
        </x-ui.field>

        <x-ui.field label="Senha" for="login-password" error="password">
            <input
                id="login-password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                class="input"
                @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror
            >
        </x-ui.field>

        <label class="flex min-h-11 items-center gap-2.5 text-sm">
            <input type="checkbox" wire:model="remember" class="size-4 accent-brand">
            Manter conectado
        </label>

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="login"
        >
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login">Entrando…</span>
        </button>
    </form>

    <p class="text-center text-sm text-muted">
        Ainda não tem conta?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand hover:text-brand-dark">Criar conta</a>
    </p>
</div>

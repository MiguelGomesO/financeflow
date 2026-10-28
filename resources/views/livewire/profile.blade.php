@use('App\Support\Dates')

<div class="mx-auto flex max-w-5xl flex-col gap-6">
    @if (session('status'))
        <p
            role="status"
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition.opacity
            class="flex items-center gap-2.5 rounded-xl bg-brand-light px-4 py-3 text-sm font-medium text-brand-dark-fg"
        >
            <x-icon name="check" class="size-[18px]" />
            {{ session('status') }}
        </p>
    @endif

    {{-- Cabeçalho: foto, nome e resumo da conta --}}
    <section class="overflow-hidden rounded-[20px] bg-ink text-white">
        <div class="h-24 bg-gradient-to-r from-brand-dark via-brand to-mint/80 sm:h-28" aria-hidden="true"></div>

        <div class="flex flex-col gap-6 px-6 pb-6 sm:px-8">
            <div class="-mt-14 flex flex-wrap items-end gap-5 sm:-mt-16">
                <div class="relative">
                    <x-avatar :user="$user" class="size-28 text-4xl ring-4 ring-ink sm:size-32 sm:text-5xl" />

                    <span
                        class="absolute inset-0 hidden items-center justify-center rounded-full bg-ink/60 text-xs font-semibold"
                        wire:loading.flex
                        wire:target="photo"
                    >
                        Enviando…
                    </span>

                    <label
                        for="profile-photo"
                        class="absolute right-0 bottom-1 flex size-10 items-center justify-center rounded-full bg-mint text-ink shadow-lg ring-4 ring-ink hover:bg-white has-[:focus-visible]:ring-white"
                        title="{{ $user->avatar_path ? 'Trocar foto' : 'Escolher foto' }}"
                    >
                        <x-icon name="camera" class="size-[18px]" />
                        <span class="sr-only">{{ $user->avatar_path ? 'Trocar foto' : 'Escolher foto' }}</span>
                        <input id="profile-photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    </label>
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-1 pb-1">
                    <h1 class="truncate font-display text-[28px] leading-tight font-semibold tracking-tight">{{ $user->name }}</h1>
                    <p class="truncate text-on-ink-muted">{{ $user->email }}</p>
                    <p class="text-[13px] text-on-ink-muted">Membro desde {{ Dates::monthLabel($user->created_at->format('Y-m')) }}</p>
                </div>

                @if ($user->avatar_path)
                    <button
                        type="button"
                        wire:click="removePhoto"
                        wire:confirm="Remover sua foto de perfil?"
                        class="min-h-11 rounded-xl border border-white/20 px-4 text-sm font-semibold text-on-ink hover:bg-ink-700 hover:text-white"
                    >
                        Remover foto
                    </button>
                @endif
            </div>

            @error('photo')
                <p id="profile-photo-error" class="rounded-xl bg-expense-light px-4 py-2.5 text-sm font-medium text-expense-dark-fg">{{ $message }}</p>
            @enderror

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($stats as $label => $count)
                    <div class="rounded-2xl bg-ink-800 px-4 py-3">
                        <dt class="text-[13px] text-on-ink-muted">{{ $label }}</dt>
                        <dd class="font-display text-2xl font-semibold tabular-nums">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>

            <p class="-mt-2 text-[13px] text-on-ink-muted">Foto em JPG, PNG ou WebP, até 2 MB. Ela é salva assim que você escolhe o arquivo.</p>
        </div>
    </section>

    {{-- Aparência: a escolha fica salva só neste navegador. --}}
    <section class="flex flex-col gap-5 rounded-[20px] border border-line bg-surface p-6" x-data="themeSwitcher">
        <header class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-light text-brand-dark-fg">
                <x-icon name="moon" />
            </span>
            <div>
                <h2 class="font-display text-[19px] font-semibold">Aparência</h2>
                <p class="text-[13px] text-muted">Tema claro ou escuro, salvo neste navegador</p>
            </div>
        </header>

        <div role="radiogroup" aria-label="Tema" class="grid gap-3 sm:grid-cols-3">
            @foreach ([
                'system' => ['Automático', 'Segue o tema do seu aparelho'],
                'light' => ['Claro', 'Fundo claro o tempo todo'],
                'dark' => ['Escuro', 'Fundo escuro, mais confortável à noite'],
            ] as $value => [$label, $hint])
                <label class="flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border border-line-strong p-3.5 has-checked:border-brand has-checked:bg-brand-light has-focus-visible:ring-2 has-focus-visible:ring-brand">
                    <input
                        type="radio"
                        name="theme"
                        value="{{ $value }}"
                        class="mt-0.5 size-4 accent-brand"
                        x-bind:checked="mode === '{{ $value }}'"
                        x-on:change="set('{{ $value }}')"
                    >
                    <span class="flex flex-col">
                        <span class="text-sm font-semibold">{{ $label }}</span>
                        <span class="text-[13px] text-muted">{{ $hint }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-2">
        {{-- Dados pessoais --}}
        <section class="flex flex-col gap-5 rounded-[20px] border border-line bg-surface p-6">
            <header class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-light text-brand-dark-fg">
                    <x-icon name="user" />
                </span>
                <div>
                    <h2 class="font-display text-[19px] font-semibold">Dados pessoais</h2>
                    <p class="text-[13px] text-muted">Como você aparece e entra no app</p>
                </div>
            </header>

            <form wire:submit="saveProfile" class="flex flex-col gap-4">
                <x-ui.field label="Nome" for="profile-name" error="name">
                    <input
                        id="profile-name"
                        type="text"
                        wire:model="name"
                        maxlength="60"
                        autocomplete="name"
                        class="input"
                        @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror
                    >
                </x-ui.field>

                <x-ui.field label="E-mail" for="profile-email" error="email">
                    <input
                        id="profile-email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        class="input"
                        @error('email') aria-invalid="true" aria-describedby="profile-email-error" @enderror
                    >
                </x-ui.field>

                <label class="flex min-h-11 cursor-pointer items-start gap-3 text-sm">
                    <input type="checkbox" wire:model="budgetAlerts" class="mt-0.5 size-5 shrink-0 accent-brand">
                    <span>
                        <span class="font-medium">Avisos de orçamento por e-mail</span>
                        <span class="block text-[13px] text-muted">Receba um e-mail ao chegar a 80% e a 100% do limite de uma categoria.</span>
                    </span>
                </label>

                <button
                    type="submit"
                    class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                    wire:loading.attr="disabled"
                    wire:target="saveProfile"
                >
                    <span wire:loading.remove wire:target="saveProfile">Salvar alterações</span>
                    <span wire:loading wire:target="saveProfile">Salvando…</span>
                </button>
            </form>
        </section>

        {{-- Segurança --}}
        <section class="flex flex-col gap-5 rounded-[20px] border border-line bg-surface p-6">
            <header class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-expense-light text-expense-dark-fg">
                    <x-icon name="lock" />
                </span>
                <div>
                    <h2 class="font-display text-[19px] font-semibold">Segurança</h2>
                    <p class="text-[13px] text-muted">Troque sua senha quando quiser</p>
                </div>
            </header>

            <form
                wire:submit="savePassword"
                class="flex flex-col gap-4"
                x-data="{
                    current: false,
                    next: false,
                    password: '',
                    get score() {
                        const p = this.password;
                        return [p.length >= 8, p.length >= 12, /[a-z]/.test(p) && /[A-Z]/.test(p), /\d/.test(p), /[^A-Za-z0-9]/.test(p)]
                            .filter(Boolean).length;
                    },
                    get level() { return this.password === '' ? 0 : Math.min(4, Math.max(1, this.score - (this.password.length < 8 ? 1 : 0))); },
                    labels: ['', 'Fraca', 'Razoável', 'Boa', 'Forte'],
                    colors: ['', 'bg-expense', 'bg-expense-soft', 'bg-brand/60', 'bg-brand'],
                }"
            >
                <x-ui.field label="Senha atual" for="profile-current-password" error="currentPassword">
                    <div class="relative">
                        <input
                            id="profile-current-password"
                            x-bind:type="current ? 'text' : 'password'"
                            wire:model="currentPassword"
                            autocomplete="current-password"
                            class="input pr-12"
                            @error('currentPassword') aria-invalid="true" aria-describedby="profile-current-password-error" @enderror
                        >
                        <button type="button" x-on:click="current = !current" class="absolute top-0 right-0 flex size-11 items-center justify-center rounded-xl text-muted hover:text-strong" x-bind:aria-label="current ? 'Ocultar senha' : 'Mostrar senha'">
                            <x-icon name="eye" class="size-[18px]" x-show="!current" />
                            <x-icon name="eye-off" class="size-[18px]" x-show="current" x-cloak />
                        </button>
                    </div>
                </x-ui.field>

                <x-ui.field label="Nova senha" for="profile-new-password" error="newPassword">
                    <div class="relative">
                        <input
                            id="profile-new-password"
                            x-bind:type="next ? 'text' : 'password'"
                            wire:model="newPassword"
                            x-model="password"
                            autocomplete="new-password"
                            class="input pr-12"
                            @error('newPassword') aria-invalid="true" aria-describedby="profile-new-password-error" @enderror
                        >
                        <button type="button" x-on:click="next = !next" class="absolute top-0 right-0 flex size-11 items-center justify-center rounded-xl text-muted hover:text-strong" x-bind:aria-label="next ? 'Ocultar senha' : 'Mostrar senha'">
                            <x-icon name="eye" class="size-[18px]" x-show="!next" />
                            <x-icon name="eye-off" class="size-[18px]" x-show="next" x-cloak />
                        </button>
                    </div>

                    <div class="mt-1 flex items-center gap-3" x-show="password !== ''" x-cloak>
                        <div class="grid flex-1 grid-cols-4 gap-1.5" aria-hidden="true">
                            <template x-for="i in 4" :key="i">
                                <span class="h-1.5 rounded-full bg-mist transition-colors" x-bind:class="i <= level ? colors[level] : ''"></span>
                            </template>
                        </div>
                        <span class="w-16 text-right text-[13px] font-medium text-muted" x-text="labels[level]" aria-live="polite"></span>
                    </div>
                </x-ui.field>

                <x-ui.field label="Repita a nova senha" for="profile-new-password-confirmation" error="newPasswordConfirmation">
                    <input
                        id="profile-new-password-confirmation"
                        x-bind:type="next ? 'text' : 'password'"
                        wire:model="newPasswordConfirmation"
                        autocomplete="new-password"
                        class="input"
                        @error('newPasswordConfirmation') aria-invalid="true" aria-describedby="profile-new-password-confirmation-error" @enderror
                    >
                </x-ui.field>

                <p class="-mt-2 text-[13px] text-muted">Use pelo menos 8 caracteres. Misturar letras, números e símbolos deixa a senha mais forte.</p>

                <button
                    type="submit"
                    class="min-h-12 rounded-xl border border-line-strong font-semibold hover:bg-mist disabled:opacity-60"
                    wire:loading.attr="disabled"
                    wire:target="savePassword"
                >
                    <span wire:loading.remove wire:target="savePassword">Alterar senha</span>
                    <span wire:loading wire:target="savePassword">Alterando…</span>
                </button>
            </form>
        </section>
    </div>
</div>

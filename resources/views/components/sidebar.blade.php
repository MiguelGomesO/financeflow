{{-- Fundo escurecido do menu no mobile --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    x-on:click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-ink/50 lg:hidden"
    x-cloak
></div>

{{-- No desktop o menu encolhe para só os ícones (sidebarCollapsed), animando a largura. --}}
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full flex-col gap-7 overflow-hidden bg-ink px-4 py-6 text-on-ink transition-[width,padding,transform] duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    x-bind:class="{ '-translate-x-full': !sidebarOpen, 'lg:w-[4.75rem] lg:px-3': sidebarCollapsed }"
>
    <div
        class="flex items-center justify-between px-2"
        x-bind:class="{ 'lg:justify-center lg:px-0': sidebarCollapsed }"
    >
        <div x-bind:class="{ 'lg:hidden': sidebarCollapsed }">
            <x-logo />
        </div>
        <button
            type="button"
            class="flex size-11 items-center justify-center rounded-xl text-on-ink hover:bg-ink-700 lg:hidden"
            x-on:click="sidebarOpen = false"
            aria-label="Fechar menu"
        >
            <x-icon name="close" />
        </button>
        <button
            type="button"
            class="hidden size-11 shrink-0 items-center justify-center rounded-xl text-on-ink hover:bg-ink-700 lg:flex"
            x-on:click="sidebarCollapsed = !sidebarCollapsed"
            x-bind:aria-label="sidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
            x-bind:title="sidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
            x-bind:aria-expanded="!sidebarCollapsed"
            aria-controls="sidebar"
        >
            <x-icon name="chevron-left" x-show="!sidebarCollapsed" />
            <x-icon name="chevron-right" x-show="sidebarCollapsed" x-cloak />
        </button>
    </div>

    <nav aria-label="Menu do app" class="flex flex-col gap-1">
        <x-sidebar.link :href="route('dashboard')" icon="overview" :active="request()->routeIs('dashboard')">Visão geral</x-sidebar.link>
        <x-sidebar.link :href="route('transactions')" icon="transactions" :active="request()->routeIs('transactions')">Transações</x-sidebar.link>
        <x-sidebar.link :href="route('reports')" icon="trend" :active="request()->routeIs('reports')">Relatórios</x-sidebar.link>
        <x-sidebar.link :href="route('recurring')" icon="repeat" :active="request()->routeIs('recurring')">Recorrentes</x-sidebar.link>
        <x-sidebar.link :href="route('budgets')" icon="budget" :active="request()->routeIs('budgets')">Orçamentos</x-sidebar.link>
        <x-sidebar.link :href="route('goals')" icon="goal" :active="request()->routeIs('goals')">Metas</x-sidebar.link>
        <x-sidebar.link :href="route('accounts')" icon="card" :active="request()->routeIs('accounts')">Contas e cartões</x-sidebar.link>
    </nav>

    {{-- Alterna claro/escuro (a escolha "automático" fica no Perfil). --}}
    <button
        type="button"
        x-data="themeSwitcher"
        x-on:click="toggle()"
        class="mt-auto flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-on-ink transition-colors hover:bg-ink-800"
        x-bind:class="{ 'lg:justify-center lg:px-0': sidebarCollapsed }"
        x-bind:aria-label="dark ? 'Mudar para o modo claro' : 'Mudar para o modo escuro'"
        x-bind:title="sidebarCollapsed ? (dark ? 'Modo claro' : 'Modo escuro') : null"
    >
        {{-- Ícone e texto seguem a classe "dark" do <html> (posta antes da primeira pintura), sem esperar o Alpine. --}}
        <span class="flex dark:hidden"><x-icon name="moon" /></span>
        <span class="hidden dark:flex"><x-icon name="sun" /></span>
        <span x-bind:class="{ 'lg:hidden': sidebarCollapsed }">
            <span class="dark:hidden">Modo escuro</span>
            <span class="hidden dark:inline">Modo claro</span>
        </span>
    </button>

    {{-- Menu aberto: foto e nome. Recolhido: só a foto. Ao clicar abre o menu do perfil (renderizado fora do menu lateral, que corta o que passa da borda). --}}
    <div class="flex" x-bind:class="{ 'lg:justify-center': sidebarCollapsed }">
        <button
            type="button"
            data-profile
            class="flex min-w-0 items-center gap-3 rounded-2xl p-1.5 text-left transition-colors hover:bg-ink-800 focus-visible:bg-ink-800 focus:outline-none"
            x-bind:class="{ 'lg:w-auto lg:rounded-full lg:p-0 lg:hover:bg-transparent': sidebarCollapsed, 'w-full': !sidebarCollapsed }"
            aria-label="Menu do perfil de {{ auth()->user()?->name }}"
            aria-haspopup="menu"
            x-bind:aria-expanded="profileOpen"
            x-bind:title="sidebarCollapsed ? @js(auth()->user()?->name) : null"
            x-on:click="
                profileOpen = !profileOpen;
                if (profileOpen) {
                    const rect = $event.currentTarget.getBoundingClientRect();
                    profileStyle = `left: ${rect.right + 12}px; bottom: ${window.innerHeight - rect.bottom}px`;
                }
            "
        >
            <x-avatar :user="auth()->user()" class="size-11 text-sm" />

            <span class="flex min-w-0 flex-1 flex-col" x-bind:class="{ 'lg:hidden': sidebarCollapsed }">
                <span class="truncate font-semibold text-white">{{ auth()->user()?->name }}</span>
                <span class="truncate text-[13px] text-on-ink-muted">{{ auth()->user()?->email }}</span>
            </span>
        </button>
    </div>
</aside>

<div
    x-show="profileOpen"
    x-cloak
    x-transition.opacity.duration.100ms
    x-bind:style="profileStyle"
    x-on:click.window="if (profileOpen && !$event.target.closest('[data-profile]')) profileOpen = false"
    data-profile
    role="menu"
    aria-label="Perfil"
    class="fixed z-50 flex w-60 flex-col gap-1 rounded-2xl border border-line bg-surface p-2 text-strong shadow-xl"
>
    <div class="flex items-center gap-3 px-2 py-2">
        <x-avatar :user="auth()->user()" class="size-10 text-sm" />
        <div class="flex min-w-0 flex-col">
            <span class="truncate font-semibold">{{ auth()->user()?->name }}</span>
            <span class="truncate text-[13px] text-muted">{{ auth()->user()?->email }}</span>
        </div>
    </div>

    <hr class="border-line">

    <a
        href="{{ route('profile') }}"
        wire:navigate
        role="menuitem"
        x-on:click="profileOpen = false; sidebarOpen = false"
        class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium no-underline hover:bg-mist"
    >
        <x-icon name="user" class="size-[18px] text-muted" />
        Editar perfil
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button
            type="submit"
            role="menuitem"
            class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-left text-sm font-medium text-expense-dark-fg hover:bg-expense-light"
        >
            <x-icon name="logout" class="size-[18px]" />
            Sair
        </button>
    </form>
</div>

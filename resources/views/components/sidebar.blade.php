{{-- Fundo escurecido do menu no mobile --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    x-on:click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-ink/50 lg:hidden"
    x-cloak
></div>

<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full flex-col gap-7 bg-ink px-4 py-6 text-on-ink transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    x-bind:class="{ '-translate-x-full': !sidebarOpen }"
>
    <div class="flex items-center justify-between px-2">
        <x-logo />
        <button
            type="button"
            class="flex size-11 items-center justify-center rounded-xl text-on-ink hover:bg-ink-700 lg:hidden"
            x-on:click="sidebarOpen = false"
            aria-label="Fechar menu"
        >
            <x-icon name="close" />
        </button>
    </div>

    <nav aria-label="Menu do app" class="flex flex-col gap-1">
        <x-sidebar.link :href="route('dashboard')" icon="overview" :active="request()->routeIs('dashboard')">Visão geral</x-sidebar.link>
        <x-sidebar.link :href="route('transactions')" icon="transactions" :active="request()->routeIs('transactions')">Transações</x-sidebar.link>
        <x-sidebar.link :href="route('budgets')" icon="budget" :active="request()->routeIs('budgets')">Orçamentos</x-sidebar.link>
        <x-sidebar.link href="#" icon="goal">Metas</x-sidebar.link>
        <x-sidebar.link href="#" icon="card">Contas e cartões</x-sidebar.link>
    </nav>

    <div class="mt-auto flex items-center gap-3 rounded-2xl bg-ink-800 p-4">
        <span class="flex size-10 items-center justify-center rounded-full bg-mint font-bold text-ink">
            {{ str(auth()->user()?->name ?? 'Você')->substr(0, 2)->upper() }}
        </span>
        <div class="flex min-w-0 flex-col">
            <span class="truncate font-semibold text-white">{{ auth()->user()?->name ?? 'Você' }}</span>
            <span class="text-[13px] text-on-ink-muted">Controle pessoal</span>
        </div>
    </div>
</aside>

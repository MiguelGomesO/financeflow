@use('App\Support\Money')

<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Transações</h1>
            <p class="text-muted">Entradas e saídas do mês</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <label for="month" class="text-sm text-muted">Mês</label>
            <x-ui.select id="month" wire:model.live="month" class="input w-auto">
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>

            <livewire:import />

            <a
                href="#nova-transacao"
                x-on:click="$nextTick(() => document.getElementById('tx-description')?.focus())"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-[18px] font-semibold text-white no-underline hover:bg-brand-dark xl:hidden"
            >
                <x-icon name="plus" stroke-width="2.4" class="size-[18px]" />
                Nova transação
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card size="sm" tone="income" label="Entradas" :value="Money::brl($totals['income'])" />
        <x-ui.stat-card size="sm" tone="expense" label="Saídas" :value="Money::brl($totals['expense'])" />
        <x-ui.stat-card size="sm" tone="dark" label="Resultado" :value="Money::brl($totals['net'])" />
    </div>

    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <x-ui.card class="min-w-0">
            <x-transactions.filters :types="$types" :categories="$categories" :has-filters="$hasFilters" />
            <x-transactions.table :rows="$rows" :has-filters="$hasFilters" />
        </x-ui.card>

        <livewire:create-transaction inline />
    </div>

    <livewire:edit-transaction />
</div>

@use('App\Support\Money')

<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Visão geral</h1>
            <p class="text-muted first-letter:uppercase">{{ $rangeLabel }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <label for="period" class="text-sm text-muted">Período</label>
            <select id="period" wire:model.live="period" class="input w-auto">
                @foreach ($periods as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <button
                type="button"
                x-on:click="$dispatch('open-modal', { name: 'new-transaction' })"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark"
            >
                <x-icon name="plus" stroke-width="2.4" class="size-[18px]" />
                Nova transação
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="period">
        <x-ui.stat-card
            tone="dark"
            label="Saldo total"
            :value="Money::brl($stats['balance'])"
            :hint="$stats['accounts'].' contas'"
        />
        <x-ui.stat-card tone="income" label="Entradas" :value="Money::brl($stats['income'])" :hint="$periods[$period]" />
        <x-ui.stat-card tone="expense" label="Saídas" :value="Money::brl($stats['expense'])" :hint="$periods[$period]" />
        <x-ui.stat-card label="Economia" :value="$stats['savingsRate'].'%'" hint="da renda guardada" />
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-dashboard.cash-flow-chart :months="$months" />
        <x-dashboard.budget-list :budgets="$budgets" />
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-dashboard.recent-transactions :transactions="$transactions" />
        <x-dashboard.goals-list :goals="$goals" />
    </div>

    <x-dashboard.new-transaction-modal />
</div>

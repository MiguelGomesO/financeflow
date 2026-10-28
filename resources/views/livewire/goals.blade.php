@use('App\Support\Money')

<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Metas</h1>
            <p class="text-muted">Seus objetivos de economia</p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark"
        >
            <x-icon name="plus" stroke-width="2.4" class="size-[18px]" />
            Nova meta
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card size="sm" tone="dark" label="Total guardado" :value="Money::brl($totals['saved'], decimals: 0)" />
        <x-ui.stat-card size="sm" label="Falta para concluir tudo" :value="Money::brl($totals['remaining'], decimals: 0)" />
        <x-ui.stat-card size="sm" tone="income" label="Guardar por mês" :value="Money::brl($totals['monthly'], decimals: 0)" />
    </div>

    @if (empty($items))
        <div class="flex flex-col items-center gap-3 rounded-[20px] border border-dashed border-line-strong bg-surface px-6 py-12 text-center">
            <p class="text-muted">Você ainda não tem metas.</p>
            <button type="button" wire:click="create" class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist">
                Criar a primeira
            </button>
        </div>
    @else
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-4">
            @foreach ($items as $goal)
                <x-goals.card wire:key="goal-{{ $goal['id'] }}" :goal="$goal" />
            @endforeach
        </div>
    @endif

    <x-goals.deposits-table :deposits="$depositRows" />

    <x-goals.goal-form-modal :editing="$editing" />
    <x-goals.deposit-modal :goal="$depositGoal" :accounts="$accounts" />
</div>

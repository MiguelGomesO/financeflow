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

            {{-- Exporta o mês com os filtros atuais. --}}
            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <button
                    type="button"
                    x-on:click="open = !open"
                    x-bind:aria-expanded="open"
                    aria-haspopup="menu"
                    class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line-strong bg-white px-[18px] font-semibold text-ink hover:bg-mist"
                >
                    <x-icon name="download" class="size-[18px] rotate-180" />
                    Exportar
                </button>

                <div x-show="open" x-cloak role="menu" class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-line-strong bg-white py-1 shadow-lg">
                    @foreach (['csv' => 'CSV (.csv)', 'xlsx' => 'Excel (.xlsx)', 'pdf' => 'PDF (.pdf)'] as $format => $label)
                        <button
                            type="button"
                            role="menuitem"
                            wire:click="export('{{ $format }}')"
                            x-on:click="open = false"
                            class="block min-h-11 w-full px-4 text-left text-sm font-medium hover:bg-mist"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

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
            <x-transactions.filters :types="$types" :categories="$categories" :tags="$tags" :has-filters="$hasFilters" />
            <x-transactions.bulk-bar :count="$selectedCount" :total="$rows->total()" :type="$bulkType" :categories="$bulkCategories" :notice="$notice" />
            <x-transactions.table :rows="$rows" :has-filters="$hasFilters" :selected="$selected" :sort="$sort" :direction="$direction" />
        </x-ui.card>

        <livewire:create-transaction inline />
    </div>

    <livewire:edit-transaction />
</div>

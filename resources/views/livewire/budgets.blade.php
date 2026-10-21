<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Orçamentos</h1>
            <p class="text-muted">Limites de gasto por categoria</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <label for="month" class="text-sm text-muted">Mês</label>
            <select id="month" wire:model.live="month" class="input w-auto">
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark"
            >
                <x-icon name="plus" stroke-width="2.4" class="size-[18px]" />
                Novo orçamento
            </button>
        </div>
    </div>

    <div class="flex flex-col gap-6" wire:loading.class="opacity-60" wire:target="month,copyPrevious">
        <x-budgets.summary :totals="$totals" :pace="$pace" />

        @if ($items->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-[20px] border border-dashed border-line-strong bg-white px-6 py-12 text-center">
                <p class="text-muted">Nenhum orçamento definido para este mês.</p>
                <div class="flex flex-wrap justify-center gap-2.5">
                    @if ($canCopyPrevious)
                        <button
                            type="button"
                            wire:click="copyPrevious"
                            class="min-h-11 rounded-xl bg-brand px-4 text-sm font-semibold text-white hover:bg-brand-dark"
                        >
                            Copiar limites de {{ $previousLabel }}
                        </button>
                    @endif
                    <button
                        type="button"
                        wire:click="openCreate"
                        class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist"
                    >
                        Criar um orçamento
                    </button>
                </div>
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-4">
                @foreach ($items as $budget)
                    <x-budgets.card wire:key="budget-{{ $budget->id }}" :budget="$budget" :pace="$pace" />
                @endforeach
            </div>
        @endif
    </div>

    <x-budgets.form-modal :available="$available" :editing-name="$editingName" />
</div>

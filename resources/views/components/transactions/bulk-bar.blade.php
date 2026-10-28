@use('App\Livewire\Transactions')
@props(['count', 'total', 'type', 'categories', 'notice' => null])

@if ($notice)
    <p role="status" class="mb-3 rounded-xl bg-brand-light px-4 py-2.5 text-sm font-medium text-brand-dark">{{ $notice }}</p>
@endif

@if ($count > 0)
    <div role="region" aria-label="Ações em massa" class="mb-3 flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl bg-ink px-4 py-3 text-sm text-white">
            <span class="font-semibold">{{ $count }} {{ $count === 1 ? 'marcada' : 'marcadas' }}</span>

            @if ($count < min($total, Transactions::MAX_SELECTION))
                <button type="button" wire:click="selectFiltered" class="font-semibold text-mint hover:underline">
                    Marcar {{ $total > Transactions::MAX_SELECTION ? 'as primeiras '.Transactions::MAX_SELECTION : 'todas as '.$total }} do filtro
                </button>
            @endif

            <button type="button" wire:click="clearSelection" class="text-on-ink-muted hover:underline">Limpar seleção</button>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                @if ($type === null)
                    <span class="text-on-ink-muted">Para trocar a categoria, marque só despesas ou só receitas.</span>
                @else
                    <label for="bulk-category" class="sr-only">Nova categoria</label>
                    <x-ui.select id="bulk-category" wire:model="bulkCategory" class="input w-auto text-ink">
                        <option value="">Nova categoria…</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>
                    <button
                        type="button"
                        wire:click="categorizeSelected"
                        class="min-h-11 rounded-xl bg-brand px-4 font-semibold text-white hover:bg-brand-dark"
                    >
                        Trocar categoria
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="deleteSelected"
                    wire:confirm="Excluir {{ $count }} {{ $count === 1 ? 'transação' : 'transações' }}? Isso não pode ser desfeito."
                    class="min-h-11 rounded-xl bg-expense px-4 font-semibold text-white hover:opacity-90"
                >
                    Excluir
                </button>
            </div>
        </div>

        @error('bulkCategory')
            <p class="text-[13px] font-medium text-expense-dark">{{ $message }}</p>
        @enderror
    </div>
@endif
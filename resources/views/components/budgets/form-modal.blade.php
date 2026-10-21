@props(['available', 'editingName' => null])

@php($editing = $editingName !== null)

<x-ui.modal name="budget-form" :title="$editing ? 'Orçamento de '.$editingName : 'Novo orçamento'">
    @if (! $editing && $available->isEmpty())
        <p class="text-body">Todas as categorias de despesa já têm orçamento neste mês.</p>
    @else
        <form wire:submit="save" class="flex flex-col gap-4">
            @unless ($editing)
                <x-ui.field label="Categoria" for="budget-category" error="form.categoryId">
                    <select
                        id="budget-category"
                        wire:model="form.categoryId"
                        class="input"
                        @error('form.categoryId') aria-invalid="true" aria-describedby="budget-category-error" @enderror
                    >
                        <option value="">Escolha uma categoria</option>
                        @foreach ($available as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
            @endunless

            <x-ui.field label="Limite do mês (R$)" for="budget-limit" error="form.limit">
                <input
                    id="budget-limit"
                    type="text"
                    inputmode="decimal"
                    wire:model="form.limit"
                    placeholder="0,00"
                    class="input font-display text-xl"
                    @error('form.limit') aria-invalid="true" aria-describedby="budget-limit-error" @enderror
                >
            </x-ui.field>

            <button
                type="submit"
                class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                {{ $editing ? 'Salvar limite' : 'Criar orçamento' }}
            </button>

            @if ($editing)
                <button
                    type="button"
                    wire:click="delete"
                    wire:confirm="Excluir este orçamento? As transações não são afetadas."
                    class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark hover:bg-expense-light"
                >
                    Excluir orçamento
                </button>
            @endif
        </form>
    @endif
</x-ui.modal>

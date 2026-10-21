@props(['available'])

<x-ui.modal name="new-budget" title="Novo orçamento">
    @if (empty($available))
        <p class="text-body">Todas as categorias já têm orçamento neste mês.</p>
    @else
        <form wire:submit="save" class="flex flex-col gap-4">
            <x-ui.field label="Categoria" for="budget-category" error="form.category">
                <select
                    id="budget-category"
                    wire:model="form.category"
                    class="input"
                    @error('form.category') aria-invalid="true" aria-describedby="budget-category-error" @enderror
                >
                    <option value="">Escolha uma categoria</option>
                    @foreach ($available as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </x-ui.field>

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
                Criar orçamento
            </button>
        </form>
    @endif
</x-ui.modal>

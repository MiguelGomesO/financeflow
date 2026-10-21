@props(['editing' => false])

<x-ui.modal name="goal-form" :title="$editing ? 'Editar meta' : 'Nova meta'">
    <form wire:submit="saveGoal" class="flex flex-col gap-4">
        <x-ui.field label="Nome da meta" for="goal-name" error="form.name">
            <input
                id="goal-name"
                type="text"
                wire:model="form.name"
                placeholder="Ex.: Reserva de emergência"
                maxlength="60"
                class="input"
                @error('form.name') aria-invalid="true" aria-describedby="goal-name-error" @enderror
            >
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Quanto juntar (R$)" for="goal-target" error="form.target">
                <input
                    id="goal-target"
                    type="text"
                    inputmode="decimal"
                    wire:model="form.target"
                    placeholder="0,00"
                    class="input font-display text-lg"
                    @error('form.target') aria-invalid="true" aria-describedby="goal-target-error" @enderror
                >
            </x-ui.field>

            <x-ui.field label="Prazo" for="goal-due" error="form.due">
                <input
                    id="goal-due"
                    type="month"
                    wire:model="form.due"
                    class="input px-2.5"
                    @error('form.due') aria-invalid="true" aria-describedby="goal-due-error" @enderror
                >
            </x-ui.field>
        </div>

        @unless ($editing)
            <x-ui.field label="Já guardado (R$, opcional)" for="goal-saved" error="form.saved">
                <input id="goal-saved" type="text" inputmode="decimal" wire:model="form.saved" placeholder="0,00" class="input">
            </x-ui.field>
        @endunless

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveGoal"
        >
            {{ $editing ? 'Salvar alterações' : 'Criar meta' }}
        </button>

        @if ($editing)
            <button
                type="button"
                wire:click="deleteGoal"
                wire:confirm="Excluir esta meta e os depósitos dela?"
                class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark hover:bg-expense-light"
            >
                Excluir meta
            </button>
        @endif
    </form>
</x-ui.modal>

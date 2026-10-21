@props(['categories', 'accounts'])

<x-ui.card id="nova-transacao" title="Nova transação" class="scroll-mt-20">
    <form
        wire:submit="save"
        class="flex flex-col gap-4"
        x-data="{ saved: false }"
        x-on:transaction-saved.window="saved = true; setTimeout(() => saved = false, 2500); $refs.description.focus()"
    >
        <x-ui.segmented label="Tipo de transação" model="form.type" :options="['expense' => 'Despesa', 'income' => 'Receita']" />

        <x-ui.field label="Descrição" for="tx-description" error="form.description">
            <input
                id="tx-description"
                x-ref="description"
                type="text"
                wire:model="form.description"
                placeholder="Ex.: Padaria"
                maxlength="120"
                class="input"
                @error('form.description') aria-invalid="true" aria-describedby="tx-description-error" @enderror
            >
        </x-ui.field>

        <x-ui.field label="Valor (R$)" for="tx-amount" error="form.amount">
            <input
                id="tx-amount"
                type="text"
                inputmode="decimal"
                wire:model="form.amount"
                placeholder="0,00"
                class="input font-display text-xl"
                @error('form.amount') aria-invalid="true" aria-describedby="tx-amount-error" @enderror
            >
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Categoria" for="tx-category" error="form.category">
                <select id="tx-category" wire:model="form.category" class="input">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Conta" for="tx-account" error="form.account">
                <select id="tx-account" wire:model="form.account" class="input">
                    @foreach ($accounts as $account)
                        <option value="{{ $account }}">{{ $account }}</option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>

        <x-ui.field label="Data" for="tx-date" error="form.date">
            <input id="tx-date" type="date" wire:model="form.date" class="input">
        </x-ui.field>

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            <span wire:loading.remove wire:target="save">Salvar transação</span>
            <span wire:loading wire:target="save">Salvando…</span>
        </button>

        <p x-show="saved" x-transition.opacity x-cloak role="status" class="text-center text-sm font-medium text-brand">
            Transação adicionada.
        </p>
    </form>
</x-ui.card>

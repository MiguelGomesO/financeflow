@props(['types', 'isCard' => false])

<x-ui.modal name="new-account" title="Adicionar conta">
    <form wire:submit="saveAccount" class="flex flex-col gap-4">
        <x-ui.segmented label="O que adicionar" model="form.kind" :options="['account' => 'Conta', 'card' => 'Cartão de crédito']" live />

        <x-ui.field label="Nome" for="account-name" error="form.name">
            <input
                id="account-name"
                type="text"
                wire:model="form.name"
                placeholder="{{ $isCard ? 'Ex.: Cartão do dia a dia' : 'Ex.: Conta salário' }}"
                maxlength="40"
                class="input"
                @error('form.name') aria-invalid="true" aria-describedby="account-name-error" @enderror
            >
        </x-ui.field>

        @if ($isCard)
            <div class="grid grid-cols-2 gap-3">
                <x-ui.field label="4 últimos dígitos" for="card-last4" error="form.last4">
                    <input id="card-last4" type="text" inputmode="numeric" maxlength="4" wire:model="form.last4" placeholder="0000" class="input tabular-nums">
                </x-ui.field>

                <x-ui.field label="Limite (R$)" for="card-limit" error="form.limit">
                    <input id="card-limit" type="text" inputmode="decimal" wire:model="form.limit" placeholder="0,00" class="input">
                </x-ui.field>

                <x-ui.field label="Dia do fechamento" for="card-close" error="form.closeDay">
                    <input id="card-close" type="number" min="1" max="31" wire:model="form.closeDay" class="input">
                </x-ui.field>

                <x-ui.field label="Dia do vencimento" for="card-due" error="form.dueDay">
                    <input id="card-due" type="number" min="1" max="31" wire:model="form.dueDay" class="input">
                </x-ui.field>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3">
                <x-ui.field label="Tipo" for="account-type" error="form.type">
                    <select id="account-type" wire:model="form.type" class="input px-2.5">
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Saldo atual (R$)" for="account-balance" error="form.balance">
                    <input id="account-balance" type="text" inputmode="decimal" wire:model="form.balance" placeholder="0,00" class="input">
                </x-ui.field>
            </div>

            <x-ui.field label="Banco ou instituição (opcional)" for="account-institution" error="form.institution">
                <input id="account-institution" type="text" wire:model="form.institution" maxlength="40" class="input">
            </x-ui.field>
        @endif

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveAccount"
        >
            {{ $isCard ? 'Adicionar cartão' : 'Adicionar conta' }}
        </button>
    </form>
</x-ui.modal>

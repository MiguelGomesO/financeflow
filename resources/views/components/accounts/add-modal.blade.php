@props(['types', 'isCard' => false, 'editingKind' => null, 'deleteError' => null])

@php
    $editing = $editingKind !== null;
    $title = match ($editingKind) {
        'account' => 'Editar conta',
        'card' => 'Editar cartão',
        default => 'Adicionar conta',
    };
@endphp

<x-ui.modal name="account-form" :title="$title">
    <form wire:submit="saveAccount" class="flex flex-col gap-4">
        @unless ($editing)
            <x-ui.segmented label="O que adicionar" model="form.kind" :options="['account' => 'Conta', 'card' => 'Cartão de crédito']" live />
        @endunless

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

                <x-ui.field label="Limite usado (R$)" for="card-used" error="form.usedLimit" class="col-span-2">
                    <input id="card-used" type="text" inputmode="decimal" wire:model="form.usedLimit" placeholder="Opcional, ex.: 377,30" class="input">
                    <p class="text-[13px] text-muted">Informe o que o banco ou a loja mostra. Em branco, usa a fatura em aberto.</p>
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
                    <x-ui.select id="account-type" wire:model="form.type" class="input px-2.5">
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field :label="$editing ? 'Saldo inicial (R$)' : 'Saldo atual (R$)'" for="account-balance" error="form.balance">
                    <input id="account-balance" type="text" inputmode="decimal" wire:model="form.balance" placeholder="0,00" class="input">
                </x-ui.field>
            </div>

            @if ($editing)
                <p class="-mt-2 text-[13px] text-muted">O saldo inicial é o valor antes das transações registradas no app.</p>
            @endif

            <x-ui.field label="Banco ou instituição (opcional)" for="account-institution" error="form.institution">
                <input id="account-institution" type="text" wire:model="form.institution" maxlength="40" list="bank-names" class="input">
                <datalist id="bank-names">
                    @foreach (\App\Support\Banks::names() as $bankName)
                        <option value="{{ $bankName }}"></option>
                    @endforeach
                </datalist>
            </x-ui.field>

            <x-ui.field label="Observação (opcional)" for="account-note" error="form.note">
                <input id="account-note" type="text" wire:model="form.note" maxlength="120" placeholder="Ex.: Recebe o salário" class="input">
            </x-ui.field>
        @endif

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveAccount"
        >
            @if ($editing)
                Salvar alterações
            @else
                {{ $isCard ? 'Adicionar cartão' : 'Adicionar conta' }}
            @endif
        </button>

        @if ($editing)
            @if ($deleteError)
                <p role="alert" class="rounded-xl bg-expense-light px-3.5 py-2.5 text-sm text-expense-dark-fg">{{ $deleteError }}</p>
            @endif

            <button
                type="button"
                wire:click="deleteEditing"
                wire:confirm="{{ $editingKind === 'card' ? 'Excluir este cartão?' : 'Excluir esta conta?' }}"
                class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark-fg hover:bg-expense-light"
            >
                {{ $editingKind === 'card' ? 'Excluir cartão' : 'Excluir conta' }}
            </button>
        @endif
    </form>
</x-ui.modal>

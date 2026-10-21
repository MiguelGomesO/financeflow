@use('App\Support\Money')
@props(['goal' => null, 'accounts'])

<x-ui.modal name="deposit" :title="$goal ? 'Depositar em '.$goal['name'] : 'Depositar'">
    @if ($goal)
        <p class="text-sm text-body">
            Guardado: <strong class="tabular-nums">{{ Money::brl($goal['saved'], decimals: 0) }}</strong>
            de {{ Money::brl($goal['target'], decimals: 0) }}
        </p>
    @endif

    <form wire:submit="saveDeposit" class="flex flex-col gap-4">
        <x-ui.field label="Valor (R$)" for="deposit-amount" error="deposit.amount">
            <input
                id="deposit-amount"
                type="text"
                inputmode="decimal"
                wire:model="deposit.amount"
                placeholder="0,00"
                class="input font-display text-xl"
                @error('deposit.amount') aria-invalid="true" aria-describedby="deposit-amount-error" @enderror
            >
        </x-ui.field>

        <x-ui.field label="Sai da conta" for="deposit-account" error="deposit.account">
            <select id="deposit-account" wire:model="deposit.account" class="input">
                @foreach ($accounts as $account)
                    <option value="{{ $account }}">{{ $account }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveDeposit"
        >
            Confirmar depósito
        </button>
    </form>
</x-ui.modal>

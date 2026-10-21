@use('App\Support\Money')
@props(['accounts'])

<x-ui.modal name="transfer" title="Transferir entre contas">
    <form wire:submit="saveTransfer" class="flex flex-col gap-4">
        <x-ui.field label="De" for="transfer-from" error="transfer.from">
            <select id="transfer-from" wire:model="transfer.from" class="input">
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }} — {{ Money::brl($account->balance) }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field label="Para" for="transfer-to" error="transfer.to">
            <select id="transfer-to" wire:model="transfer.to" class="input">
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Valor (R$)" for="transfer-amount" error="transfer.amount">
                <input
                    id="transfer-amount"
                    type="text"
                    inputmode="decimal"
                    wire:model="transfer.amount"
                    placeholder="0,00"
                    class="input font-display text-lg"
                    @error('transfer.amount') aria-invalid="true" aria-describedby="transfer-amount-error" @enderror
                >
            </x-ui.field>

            <x-ui.field label="Data" for="transfer-date" error="transfer.date">
                <input id="transfer-date" type="date" wire:model="transfer.date" class="input px-2.5">
            </x-ui.field>
        </div>

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveTransfer"
        >
            Transferir
        </button>
    </form>
</x-ui.modal>

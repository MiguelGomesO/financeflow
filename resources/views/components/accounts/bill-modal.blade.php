@props(['card' => null])

<x-ui.modal name="card-bill" :title="$card ? 'Fatura atual de '.$card->name : 'Fatura atual'">
    <form wire:submit="saveBill" class="flex flex-col gap-4">
        <x-ui.field label="Valor da fatura (R$)" for="card-bill" error="billAmount">
            <input
                id="card-bill"
                type="text"
                inputmode="decimal"
                wire:model="billAmount"
                placeholder="0,00"
                class="input font-display text-lg"
                @error('billAmount') aria-invalid="true" aria-describedby="card-bill-error" @enderror
            >
        </x-ui.field>

        <p class="-mt-2 text-[13px] text-muted">
            Informe o valor que o banco ou a loja mostra. Vale até o fechamento da fatura; depois o app volta a somar as compras.
            Em branco, usa a soma das compras.
        </p>

        <button
            type="submit"
            class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveBill"
        >
            Salvar fatura
        </button>
    </form>
</x-ui.modal>

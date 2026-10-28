@use('App\Support\Money')
@props(['transfers'])

<x-ui.card title="Transferências recentes">
    <ul class="-mx-2 flex flex-col">
        @foreach ($transfers as $transfer)
            <li wire:key="transfer-{{ $transfer->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-mist px-2 py-2 first:border-t-0">
                <span class="w-12 text-sm text-muted tabular-nums">{{ $transfer->date->format('d/m') }}</span>
                <span class="min-w-0 flex-1 text-sm">
                    <span class="font-medium">{{ $transfer->fromAccount->name }}</span>
                    <span class="text-muted" aria-label="para">→</span>
                    <span class="font-medium">{{ $transfer->toAccount->name }}</span>
                </span>
                <span class="text-sm font-semibold tabular-nums">{{ Money::brl($transfer->amount) }}</span>
                <button
                    type="button"
                    wire:click="deleteTransfer({{ $transfer->id }})"
                    wire:confirm="Desfazer esta transferência?"
                    class="min-h-11 rounded-xl px-3 text-sm font-semibold text-expense-dark-fg hover:bg-expense-light"
                >
                    Desfazer
                </button>
            </li>
        @endforeach
    </ul>
</x-ui.card>

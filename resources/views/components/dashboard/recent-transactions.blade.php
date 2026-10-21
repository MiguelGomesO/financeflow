@use('App\Support\Money')
@use('Illuminate\Support\Carbon')
@props(['transactions'])

<x-ui.card title="Transações recentes" action="Ver todas" :action-href="route('transactions')">
    <div class="-mx-2 overflow-x-auto">
        <table class="w-full min-w-[460px] border-collapse text-sm">
            <thead>
                <tr class="text-left text-[13px] text-muted">
                    <th scope="col" class="px-2 py-2.5 font-medium">Descrição</th>
                    <th scope="col" class="px-2 py-2.5 font-medium">Categoria</th>
                    <th scope="col" class="px-2 py-2.5 font-medium">Data</th>
                    <th scope="col" class="px-2 py-2.5 text-right font-medium">Valor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr wire:key="tx-{{ $transaction['id'] }}" class="border-t border-mist">
                        <td class="px-2 py-3 font-medium">{{ $transaction['description'] }}</td>
                        <td class="px-2 py-3">
                            <span class="rounded-full bg-mist px-2.5 py-0.5 text-[13px] text-soft">{{ $transaction['category'] }}</span>
                        </td>
                        <td class="px-2 py-3 text-muted tabular-nums">{{ Carbon::parse($transaction['date'])->format('d/m') }}</td>
                        <td @class([
                            'px-2 py-3 text-right font-semibold tabular-nums',
                            'text-brand' => $transaction['amount'] > 0,
                        ])>
                            {{ Money::brl($transaction['amount'], signed: true) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-2 py-8 text-center text-muted">Nenhuma transação ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

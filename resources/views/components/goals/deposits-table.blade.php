@use('App\Support\Money')
@use('Illuminate\Support\Carbon')
@props(['deposits'])

<x-ui.card title="Últimos depósitos">
    @if (empty($deposits))
        <p class="py-6 text-center text-muted">Nenhum depósito ainda.</p>
    @else
        <div class="-mx-2 overflow-x-auto">
            <table class="w-full min-w-[520px] border-collapse text-sm">
                <thead>
                    <tr class="text-left text-[13px] text-muted">
                        <th scope="col" class="px-2 py-2.5 font-medium">Data</th>
                        <th scope="col" class="px-2 py-2.5 font-medium">Meta</th>
                        <th scope="col" class="px-2 py-2.5 font-medium">Guardado em</th>
                        <th scope="col" class="px-2 py-2.5 text-right font-medium">Valor</th>
                        <th scope="col" class="w-24 px-2 py-2.5"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deposits as $deposit)
                        <tr wire:key="deposit-{{ $deposit['id'] }}" class="border-t border-mist">
                            <td class="px-2 py-3 text-muted tabular-nums">{{ Carbon::parse($deposit['date'])->format('d/m') }}</td>
                            <td class="px-2 py-3 font-medium">{{ $deposit['goal'] }}</td>
                            <td class="px-2 py-3 text-muted">{{ $deposit['account'] ?? '—' }}</td>
                            <td class="px-2 py-3 text-right font-semibold whitespace-nowrap text-brand tabular-nums">
                                {{ Money::brl($deposit['amount'], signed: true) }}
                            </td>
                            <td class="px-2 py-1 text-right">
                                <button
                                    type="button"
                                    wire:click="deleteDeposit({{ $deposit['id'] }})"
                                    wire:confirm="Desfazer este depósito?"
                                    class="min-h-11 rounded-xl px-3 text-sm font-semibold text-expense-dark hover:bg-expense-light"
                                >
                                    Desfazer
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>

@use('App\Support\Money')
@props(['forecast', 'show' => 5])

@php
    $end = $forecast['end']->format('d/m');
    $negative = $forecast['lowest']['amount'] < 0;
    $events = $forecast['events'];
@endphp

<x-ui.card title="Previsão do mês">
    <x-slot:header>
        <span class="text-[13px] text-muted">saldo das contas até {{ $end }}</span>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-0.5">
                <span class="text-sm text-muted">Saldo previsto em {{ $end }}</span>
                <span @class([
                    'font-display text-[30px] font-semibold tabular-nums leading-tight',
                    'text-expense-fg' => $forecast['projected'] < 0,
                ])>{{ Money::brl($forecast['projected']) }}</span>
            </div>

            <dl class="flex flex-col gap-2 text-sm tabular-nums">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Saldo hoje</dt>
                    <dd class="font-medium">{{ Money::brl($forecast['balance']) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Entradas previstas</dt>
                    <dd class="font-medium text-brand-fg">+ {{ Money::brl($forecast['income']) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Saídas previstas</dt>
                    <dd class="font-medium">− {{ Money::brl($forecast['expense']) }}</dd>
                </div>
            </dl>

            @if ($negative)
                <p role="status" class="rounded-xl bg-expense-light px-4 py-3 text-sm font-medium text-expense-dark-fg">
                    O saldo deve ficar negativo a partir de {{ $forecast['lowest']['date']->format('d/m') }}
                    (até {{ Money::brl($forecast['lowest']['amount']) }}).
                </p>
            @endif

            @if ($forecast['card'] > 0)
                <p class="text-[13px] text-muted">
                    Mais {{ Money::brl($forecast['card']) }} em recorrentes no cartão até {{ $end }}: entram na fatura, não no saldo.
                </p>
            @endif
        </div>

        <div class="flex flex-col gap-2">
            <h3 class="text-sm font-semibold">Próximos lançamentos</h3>

            @if ($events === [])
                <p class="py-4 text-sm text-muted">Nada previsto nas contas até o fim do mês.</p>
            @else
                <ul class="flex flex-col divide-y divide-mist text-sm">
                    @foreach (array_slice($events, 0, $show) as $event)
                        <li wire:key="forecast-{{ $loop->index }}" class="flex items-center gap-3 py-2">
                            <span class="w-11 shrink-0 text-muted tabular-nums">{{ $event['date']->format('d/m') }}</span>
                            <span class="min-w-0 flex-1 truncate">
                                {{ $event['description'] }}
                                @if ($event['recurring'])
                                    <span class="ml-1 rounded-full bg-mist px-2 py-0.5 text-xs text-soft">recorrente</span>
                                @endif
                            </span>
                            <span @class([
                                'shrink-0 font-semibold tabular-nums',
                                'text-brand-fg' => $event['amount'] > 0,
                            ])>{{ Money::brl($event['amount'], signed: true) }}</span>
                        </li>
                    @endforeach
                </ul>

                @if (count($events) > $show)
                    <p class="text-[13px] text-muted">e mais {{ count($events) - $show }} até {{ $end }}</p>
                @endif
            @endif
        </div>
    </div>
</x-ui.card>

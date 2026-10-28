@use('App\Support\Money')
@props(['alerts'])

@if ($alerts !== [])
    <section aria-label="Alertas de orçamento" class="flex flex-col gap-2">
        @foreach ($alerts as $alert)
            @php($over = $alert['level'] >= 100)

            <div
                wire:key="budget-alert-{{ $alert['name'] }}"
                role="status"
                @class([
                    'flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-2xl px-5 py-3.5 text-sm',
                    'bg-expense-light text-expense-dark-fg' => $over,
                    'bg-expense-faint text-expense-dark-fg' => ! $over,
                ])
            >
                <span class="font-semibold">
                    {{ $over ? 'Limite atingido' : 'Perto do limite' }} em {{ $alert['name'] }}
                </span>
                <span class="tabular-nums">
                    {{ Money::brl($alert['spent']) }} de {{ Money::brl($alert['limit']) }}
                    ({{ (int) floor($alert['spent'] / $alert['limit'] * 100) }}%)
                </span>
            </div>
        @endforeach
    </section>
@endif

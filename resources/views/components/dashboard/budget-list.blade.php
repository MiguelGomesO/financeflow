@use('App\Support\Money')
@props(['budgets'])

<x-ui.card title="Orçamentos" action="Editar">
    <ul class="flex flex-col gap-[18px]">
        @foreach ($budgets as $budget)
            @php
                $ratio = $budget['limit'] > 0 ? $budget['spent'] / $budget['limit'] : 0;
                $color = match (true) {
                    $ratio > 1 => 'bg-expense',
                    $ratio >= 0.9 => 'bg-expense-soft',
                    default => 'bg-brand',
                };
            @endphp

            <li wire:key="budget-{{ $budget['name'] }}" class="flex flex-col gap-2">
                <div class="flex justify-between gap-3 text-sm">
                    <span class="font-medium">
                        {{ $budget['name'] }}
                        @if ($ratio > 1)
                            <span class="ml-1 rounded-full bg-expense-light px-2 py-0.5 text-xs font-semibold text-expense-dark">Estourado</span>
                        @endif
                    </span>
                    <span class="text-muted tabular-nums">
                        {{ Money::brl($budget['spent'], decimals: 0) }} de {{ Money::brl($budget['limit'], decimals: 0) }}
                    </span>
                </div>
                <x-ui.progress-bar :value="$ratio * 100" :color="$color" :label="'Orçamento de '.$budget['name']" />
            </li>
        @endforeach
    </ul>
</x-ui.card>

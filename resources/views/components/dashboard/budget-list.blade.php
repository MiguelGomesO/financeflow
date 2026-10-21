@use('App\Support\BudgetStatus')
@use('App\Support\Money')
@props(['budgets'])

<x-ui.card title="Orçamentos" action="Editar" :action-href="route('budgets')">
    <ul class="flex flex-col gap-[18px]">
        @foreach ($budgets as $budget)
            @php($status = BudgetStatus::for($budget['spent'], $budget['limit']))

            <li wire:key="budget-{{ $budget['name'] }}" class="flex flex-col gap-2">
                <div class="flex justify-between gap-3 text-sm">
                    <span class="font-medium">
                        {{ $budget['name'] }}
                        @if ($status['key'] === 'over')
                            <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $status['tag'] }}">{{ $status['label'] }}</span>
                        @endif
                    </span>
                    <span class="text-muted tabular-nums">
                        {{ Money::brl($budget['spent'], decimals: 0) }} de {{ Money::brl($budget['limit'], decimals: 0) }}
                    </span>
                </div>
                <x-ui.progress-bar :value="$status['percent']" :color="$status['bar']" :label="'Orçamento de '.$budget['name']" />
            </li>
        @endforeach
    </ul>
</x-ui.card>

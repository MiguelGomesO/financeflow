@use('App\Support\Money')
@props(['categories', 'expense'])

<x-ui.card title="Gastos por categoria">
    <x-slot:header>
        <span class="text-[13px] text-muted">Total: <span class="font-semibold tabular-nums text-strong">{{ Money::brl($expense) }}</span></span>
    </x-slot:header>

    @if ($categories === [])
        <p class="py-6 text-center text-muted">Nenhum gasto neste período.</p>
    @else
        @php($top = max(array_column($categories, 'current')) ?: 1)

        <ul class="flex flex-col gap-[18px]">
            @foreach ($categories as $category)
                <li wire:key="category-{{ $category['name'] }}" class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 text-sm">
                        <span class="font-medium">{{ $category['name'] }}</span>
                        <span class="flex items-baseline gap-2 tabular-nums">
                            <span class="text-muted">{{ number_format($category['share'], 0) }}%</span>
                            <span class="font-semibold">{{ Money::brl($category['current']) }}</span>
                            @if ($category['delta'] === null)
                                <span class="text-[13px] text-muted">novo</span>
                            @elseif (round($category['delta']) == 0)
                                <span class="text-[13px] text-muted">igual</span>
                            @else
                                <span @class([
                                    'text-[13px]',
                                    'text-expense-dark-fg' => $category['delta'] > 0,
                                    'text-brand-dark-fg' => $category['delta'] < 0,
                                ])>
                                    {{ $category['delta'] > 0 ? '▲' : '▼' }} {{ round(abs($category['delta'])) }}%
                                    <span class="sr-only">{{ $category['delta'] > 0 ? 'a mais' : 'a menos' }} que no período anterior</span>
                                </span>
                            @endif
                        </span>
                    </div>
                    <x-ui.progress-bar
                        :value="$category['current'] / $top * 100"
                        color="bg-expense-soft"
                        :label="$category['name'].': '.Money::brl($category['current'])"
                    />
                </li>
            @endforeach
        </ul>

        <p class="text-[13px] text-muted">▲▼ comparam com o mesmo trecho do período anterior.</p>
    @endif
</x-ui.card>

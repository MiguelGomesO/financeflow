@use('App\Support\Money')
@props(['months', 'title' => 'Entradas x saídas'])

@php
    $max = max(array_merge(array_column($months, 'income'), array_column($months, 'expense'))) ?: 1;
@endphp

<x-ui.card :title="$title">
    <x-slot:header>
        <div class="flex gap-4 text-[13px] text-soft">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-[3px] bg-brand"></span>Entradas</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-[3px] bg-expense-soft"></span>Saídas</span>
        </div>
    </x-slot:header>

    <div>
        <div class="grid h-56 items-end gap-3 border-b border-line" style="grid-template-columns: repeat({{ count($months) }}, minmax(0, 1fr))">
            @foreach ($months as $month)
                @php($edge = $loop->first ? 'left-0' : ($loop->last ? 'right-0' : 'left-1/2 -translate-x-1/2'))
                <button
                    type="button"
                    wire:key="month-{{ $month['label'] }}"
                    x-data="{ show: false }"
                    x-on:mouseenter="show = true"
                    x-on:mouseleave="show = false"
                    x-on:focus="show = true"
                    x-on:blur="show = false"
                    aria-label="{{ ucfirst($month['name']) }}: entradas {{ Money::brl($month['income']) }}, saídas {{ Money::brl($month['expense']) }}"
                    @class([
                        'relative flex h-full cursor-default items-end justify-center gap-1 rounded-t-lg transition-opacity focus-visible:outline-2 focus-visible:outline-brand',
                        'opacity-35' => ! $month['in_period'],
                    ])
                >
                    <span class="w-2/5 max-w-6 rounded-t-md bg-brand" style="height: {{ $month['income'] / $max * 100 }}%"></span>
                    <span class="w-2/5 max-w-6 rounded-t-md bg-expense-soft" style="height: {{ $month['expense'] / $max * 100 }}%"></span>

                    <span
                        x-show="show"
                        x-transition.opacity
                        x-cloak
                        class="pointer-events-none absolute top-0 z-10 {{ $edge }} whitespace-nowrap rounded-lg bg-ink px-3 py-2 text-left text-xs text-white shadow-lg"
                    >
                        <span class="block font-semibold capitalize">{{ $month['name'] }}</span>
                        <span class="block text-mint tabular-nums">+ {{ Money::brl($month['income']) }}</span>
                        <span class="block text-expense-on-ink tabular-nums">− {{ Money::brl($month['expense']) }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <div class="mt-2 grid gap-3 text-center text-[13px] text-muted" style="grid-template-columns: repeat({{ count($months) }}, minmax(0, 1fr))" aria-hidden="true">
            @foreach ($months as $month)
                <span>{{ $month['label'] }}</span>
            @endforeach
        </div>
    </div>
</x-ui.card>

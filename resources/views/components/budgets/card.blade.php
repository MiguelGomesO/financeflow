@use('App\Support\BudgetStatus')
@use('App\Support\Money')
@props(['budget', 'pace'])

@php
    $name = $budget->category->name;
    $spent = (float) $budget->spent;
    $status = BudgetStatus::for($spent, $budget->limit);
    $left = $budget->limit - $spent;
@endphp

<article {{ $attributes->class(['flex flex-col gap-3.5 rounded-[20px] border border-line bg-white p-[22px]']) }}>
    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-mist font-bold text-soft" aria-hidden="true">
                {{ mb_substr($name, 0, 1) }}
            </span>
            <h2 class="truncate font-display text-lg font-semibold">{{ $name }}</h2>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $status['tag'] }}">
                {{ $status['label'] }}
            </span>
            <x-ui.edit-button wire:click="edit({{ $budget->id }})" :label="'Editar orçamento de '.$name" class="-mr-2" />
        </div>
    </div>

    <div class="flex items-baseline justify-between gap-2">
        <span class="font-display text-2xl font-semibold tabular-nums">{{ Money::brl($spent, decimals: 0) }}</span>
        <span class="text-sm text-muted tabular-nums">de {{ Money::brl($budget->limit, decimals: 0) }}</span>
    </div>

    <x-ui.progress-bar :value="$status['percent']" :color="$status['bar']" :label="'Orçamento de '.$name" />

    <div class="flex justify-between gap-2 text-[13px]">
        <span class="text-soft">
            @if ($left < 0)
                {{ Money::brl(abs($left), decimals: 0) }} acima do limite
            @else
                {{ Money::brl($left, decimals: 0) }} disponíveis
            @endif
        </span>
        <span class="text-muted">
            @if ($pace['state'] === 'closed')
                Mês encerrado
            @elseif ($left <= 0 || $pace['daysLeft'] === 0)
                Sem saldo
            @else
                {{ Money::brl($left / $pace['daysLeft'], decimals: 0) }}/dia
            @endif
        </span>
    </div>
</article>

@use('App\Support\Money')
@props(['card'])

@php
    $used = $card['limit'] > 0 ? $card['bill'] / $card['limit'] * 100 : 0;
    // Alterna as cores dos cartões para diferenciá-los.
    $colors = ['bg-ink', 'bg-brand', 'bg-ink-600', 'bg-brand-dark'];
    $color = $colors[($card['id'] - 1) % count($colors)];
@endphp

<article {{ $attributes->class(['flex flex-wrap gap-[22px] rounded-[20px] border border-line bg-white p-[22px]']) }}>
    <div class="flex h-28 w-full max-w-[180px] flex-[0_0_180px] flex-col justify-between rounded-[14px] px-4 py-3.5 text-white {{ $color }}">
        <h3 class="font-display text-[15px] font-semibold">{{ $card['name'] }}</h3>
        <span class="text-[13px] tracking-[0.1em] tabular-nums">
            <span aria-hidden="true">••••</span><span class="sr-only">final</span> {{ $card['last4'] }}
        </span>
    </div>

    <div class="flex min-w-0 flex-[1_1_200px] flex-col gap-2.5">
        <div class="flex items-baseline justify-between gap-2">
            <span class="text-sm text-muted">Fatura atual</span>
            <span class="font-display text-[22px] font-semibold tabular-nums">{{ Money::brl($card['bill']) }}</span>
        </div>

        <x-ui.progress-bar
            :value="$used"
            :color="$used >= 90 ? 'bg-expense' : 'bg-expense-soft'"
            :label="'Limite usado do '.$card['name']"
        />

        <p class="text-[13px] text-muted tabular-nums">
            {{ (int) round($used) }}% do limite de {{ Money::brl($card['limit']) }}
            · {{ Money::brl(max(0, $card['limit'] - $card['bill'])) }} livres
        </p>

        <div class="flex flex-wrap gap-2 text-[13px]">
            <span class="rounded-full bg-mist px-2.5 py-1 text-soft">Fecha dia {{ $card['close_day'] }}</span>
            <span class="rounded-full bg-expense-light px-2.5 py-1 font-semibold text-expense-dark">Vence dia {{ $card['due_day'] }}</span>
        </div>
    </div>
</article>

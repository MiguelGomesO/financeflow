@use('App\Support\Money')
@props(['card', 'bill', 'used', 'upcoming' => []])

@php
    $usedPercent = $card->limit > 0 ? max(0, $used) / $card->limit * 100 : 0;
    // Alterna as cores dos cartões para diferenciá-los.
    $colors = ['bg-ink', 'bg-brand', 'bg-ink-600', 'bg-brand-dark'];
    $color = $colors[($card->id - 1) % count($colors)];
@endphp

<article {{ $attributes->class(['relative flex flex-wrap gap-[22px] rounded-[20px] border border-line bg-white p-[22px]']) }}>
    <div class="flex h-28 w-full max-w-[180px] flex-[0_0_180px] flex-col justify-between rounded-[14px] px-4 py-3.5 text-white {{ $color }}">
        <div class="flex items-center gap-2">
            <x-bank-logo :name="$card->name" class="size-7 rounded-lg text-[11px]" />
            <h3 class="font-display text-[15px] font-semibold">{{ $card->name }}</h3>
        </div>
        <span class="text-[13px] tracking-[0.1em] tabular-nums">
            <span aria-hidden="true">••••</span><span class="sr-only">final</span> {{ $card->last4 }}
        </span>
    </div>

    {{-- pt-9 deixa o espaço dos botões do canto, para o conteúdo usar a largura toda. --}}
    <div class="flex min-w-0 flex-[1_1_200px] flex-col gap-2.5 pt-9">
        <p class="text-[13px] text-muted tabular-nums">
            {{ (int) round($usedPercent) }}% do limite de {{ Money::brl($card->limit) }}
            · {{ Money::brl(max(0, $card->limit - $used)) }} livres
        </p>

        <x-ui.progress-bar
            :value="$usedPercent"
            :color="$usedPercent >= 90 ? 'bg-expense' : 'bg-expense-soft'"
            :label="'Limite usado do '.$card->name"
        />

        <div class="flex flex-wrap items-center gap-2 text-[13px]">
            <span class="rounded-full bg-mist px-2.5 py-1 text-soft">Fecha dia {{ $card->close_day }}</span>
            <span class="rounded-full bg-expense-light px-2.5 py-1 font-semibold text-expense-dark">Vence dia {{ $card->due_day }}</span>
            <span class="ml-auto font-display text-[22px] font-semibold tabular-nums">
                <span class="sr-only">Fatura atual:</span>{{ Money::brl($bill) }}
            </span>
        </div>

        @if ($upcoming !== [])
            <p class="text-[13px] text-muted">
                Próximas faturas:
                @foreach ($upcoming as $next)
                    <span class="tabular-nums">{{ $next['due']->format('d/m') }} · {{ Money::brl($next['amount']) }}</span>{{ $loop->last ? '' : ' · ' }}
                @endforeach
            </p>
        @endif
    </div>

    <div class="absolute top-3 right-3 flex">
        <x-ui.edit-button wire:click="addBill({{ $card->id }})" icon="plus" :label="'Informar fatura atual de '.$card->name" title="Informar fatura atual" />
        <x-ui.edit-button wire:click="editCard({{ $card->id }})" :label="'Editar '.$card->name" />
    </div>
</article>

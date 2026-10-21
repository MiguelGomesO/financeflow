@use('App\Support\Dates')
@use('App\Support\Money')
@props(['goal'])

@php($progress = $goal['progress'])

<article {{ $attributes->class(['flex flex-col gap-[18px] rounded-[20px] border border-line bg-white p-6']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-0.5">
            <h2 class="font-display text-[19px] font-semibold">{{ $goal['name'] }}</h2>
            <span class="text-[13px] text-muted">Prazo: {{ Dates::shortMonth($goal['due']) }}</span>
        </div>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $progress['tag'] }}">
            {{ $progress['status'] }}
        </span>
    </div>

    <div class="flex items-center gap-[18px]">
        <x-goals.progress-ring
            :percent="$progress['percent']"
            :label="$goal['name'].': '.(int) floor($progress['percent']).'% concluída'"
        />
        <div class="flex min-w-0 flex-col gap-1">
            <span class="font-display text-2xl font-semibold tabular-nums">{{ Money::brl($goal['saved'], decimals: 0) }}</span>
            <span class="text-sm text-muted tabular-nums">de {{ Money::brl($goal['target'], decimals: 0) }}</span>
            <span class="text-[13px] text-soft">
                @if ($progress['remaining'] <= 0)
                    Meta atingida
                @else
                    Guardar {{ Money::brl($progress['monthly'], decimals: 0) }} por mês
                @endif
            </span>
        </div>
    </div>

    <div class="flex gap-2">
        <button
            type="button"
            wire:click="startDeposit({{ $goal['id'] }})"
            class="min-h-11 flex-1 rounded-xl bg-brand-light text-sm font-semibold text-brand-dark hover:bg-brand hover:text-white"
            aria-label="Depositar em {{ $goal['name'] }}"
        >
            Depositar
        </button>
        <button
            type="button"
            wire:click="edit({{ $goal['id'] }})"
            class="min-h-11 flex-1 rounded-xl border border-line-strong bg-white text-sm font-semibold text-ink hover:bg-mist"
            aria-label="Editar {{ $goal['name'] }}"
        >
            Editar
        </button>
    </div>
</article>

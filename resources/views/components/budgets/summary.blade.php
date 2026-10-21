@use('App\Support\Money')
@props(['totals', 'pace'])

@php($used = (int) round($totals['percent']))

<section class="flex flex-col gap-[18px] rounded-[20px] bg-ink p-6 text-white" aria-label="Resumo do mês">
    <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div>
            <dt class="text-sm text-on-ink-muted">Orçado no mês</dt>
            <dd class="font-display text-[28px] font-semibold tabular-nums">{{ Money::brl($totals['limit'], decimals: 0) }}</dd>
        </div>
        <div>
            <dt class="text-sm text-on-ink-muted">{{ $pace['state'] === 'closed' ? 'Gasto no mês' : 'Gasto até agora' }}</dt>
            <dd class="font-display text-[28px] font-semibold tabular-nums text-expense-on-ink">{{ Money::brl($totals['spent'], decimals: 0) }}</dd>
        </div>
        <div>
            <dt class="text-sm text-on-ink-muted">{{ $totals['left'] >= 0 ? 'Ainda disponível' : 'Acima do orçado' }}</dt>
            <dd @class([
                'font-display text-[28px] font-semibold tabular-nums',
                'text-mint' => $totals['left'] >= 0,
                'text-expense-on-ink' => $totals['left'] < 0,
            ])>{{ Money::brl(abs($totals['left']), decimals: 0) }}</dd>
        </div>
        <div>
            <dt class="text-sm text-on-ink-muted">Dias restantes</dt>
            <dd class="font-display text-[28px] font-semibold tabular-nums">{{ $pace['state'] === 'closed' ? '—' : $pace['daysLeft'] }}</dd>
        </div>
    </dl>

    <div class="flex flex-col gap-2">
        <div class="relative">
            <x-ui.progress-bar
                :value="$totals['percent']"
                :color="$totals['percent'] > 100 ? 'bg-expense-on-ink' : 'bg-mint'"
                track="bg-ink-700"
                size="h-3"
                label="Orçamento do mês usado"
            />
            @if ($pace['state'] === 'current')
                <span
                    class="absolute -top-1 -bottom-1 w-0.5 bg-white"
                    style="left: {{ $pace['percent'] }}%"
                    aria-hidden="true"
                ></span>
            @endif
        </div>
        <div class="flex flex-wrap justify-between gap-2 text-[13px] text-on-ink-muted">
            <span>{{ $used }}% do orçamento usado</span>
            @switch($pace['state'])
                @case('current')
                    <span>Marca branca: ponto do mês (dia {{ $pace['day'] }} de {{ $pace['days'] }})</span>
                    @break
                @case('closed')
                    <span>Mês encerrado</span>
                    @break
                @default
                    <span>O mês ainda não começou</span>
            @endswitch
        </div>
    </div>
</section>

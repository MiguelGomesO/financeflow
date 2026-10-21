@use('App\Support\Money')
@props(['account'])

<article {{ $attributes->class(['flex flex-col gap-3.5 rounded-[20px] border border-line bg-white p-[22px]']) }}>
    <div class="flex items-center gap-3">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-light font-bold text-brand-dark" aria-hidden="true">
            {{ mb_substr($account['name'], 0, 1) }}
        </span>
        <div class="flex min-w-0 flex-col">
            <h3 class="truncate font-semibold">{{ $account['name'] }}</h3>
            <span class="truncate text-[13px] text-muted">{{ $account['institution'] }}</span>
        </div>
    </div>

    <span @class([
        'font-display text-[26px] font-semibold tabular-nums',
        'text-expense' => $account['balance'] < 0,
    ])>{{ Money::brl($account['balance']) }}</span>

    @if ($account['note'])
        <span class="text-[13px] text-muted">{{ $account['note'] }}</span>
    @endif
</article>

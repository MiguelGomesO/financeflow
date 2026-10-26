@use('App\Support\Money')
@props(['account'])

<article {{ $attributes->class(['flex flex-col gap-3.5 rounded-[20px] border border-line bg-white p-[22px]']) }}>
    <div class="flex items-center gap-3">
        <x-bank-logo :name="$account->institution" :fallback="$account->name" class="size-11 rounded-xl text-[13px]">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-light font-bold text-brand-dark" aria-hidden="true">
                {{ mb_substr($account->name, 0, 1) }}
            </span>
        </x-bank-logo>
        <div class="flex min-w-0 flex-1 flex-col">
            <h3 class="truncate font-semibold">{{ $account->name }}</h3>
            <span class="truncate text-[13px] text-muted">{{ $account->institution ?? $account->type }}</span>
        </div>
        <x-ui.edit-button wire:click="editAccount({{ $account->id }})" :label="'Editar '.$account->name" class="-mr-2 -mt-1" />
    </div>

    <span @class([
        'font-display text-[26px] font-semibold tabular-nums',
        'text-expense' => $account->balance < 0,
    ])>{{ Money::brl($account->balance) }}</span>

    @if ($account->note)
        <span class="text-[13px] text-muted">{{ $account->note }}</span>
    @endif
</article>

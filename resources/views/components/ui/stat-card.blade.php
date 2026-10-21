@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

@php
    $dark = $tone === 'dark';
    $valueColor = match ($tone) {
        'income' => 'text-brand',
        'expense' => 'text-expense',
        default => '',
    };
@endphp

<div {{ $attributes->class([
    'flex flex-col gap-1.5 rounded-[20px] p-[22px]',
    'bg-ink text-white' => $dark,
    'border border-line bg-white' => ! $dark,
]) }}>
    <span @class(['text-sm', 'text-on-ink-muted' => $dark, 'text-muted' => ! $dark])>{{ $label }}</span>
    <span @class(['font-display text-[30px] font-semibold tabular-nums leading-tight', $valueColor])>{{ $value }}</span>
    @if ($hint)
        <span @class(['text-[13px]', 'text-mint' => $dark, 'text-muted' => ! $dark])>{{ $hint }}</span>
    @endif
</div>

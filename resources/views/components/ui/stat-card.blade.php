@props(['label', 'value', 'hint' => null, 'tone' => 'default', 'size' => 'md'])

@php
    $dark = $tone === 'dark';
    $valueColor = match ($tone) {
        'income' => 'text-brand-fg',
        'expense' => 'text-expense-fg',
        default => '',
    };
@endphp

<div {{ $attributes->class([
    'flex flex-col',
    'gap-1.5 rounded-[20px] p-[22px]' => $size === 'md',
    'gap-0.5 rounded-[18px] px-5 py-[18px]' => $size === 'sm',
    'bg-ink text-white' => $dark,
    'border border-line bg-surface' => ! $dark,
]) }}>
    <span @class(['text-sm', 'text-on-ink-muted' => $dark, 'text-muted' => ! $dark])>{{ $label }}</span>
    <span @class([
        'font-display font-semibold tabular-nums leading-tight',
        'text-[30px]' => $size === 'md',
        'text-[26px]' => $size === 'sm',
        $valueColor,
    ])>{{ $value }}</span>
    @if ($hint)
        <span @class(['text-[13px]', 'text-mint' => $dark, 'text-muted' => ! $dark])>{{ $hint }}</span>
    @endif
</div>

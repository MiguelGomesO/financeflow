@props(['percent', 'label', 'size' => 96])

@php
    $circumference = 2 * M_PI * 40;
    $filled = $circumference * max(0, min(100, $percent)) / 100;
@endphp

<svg {{ $attributes->class(['shrink-0']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 96 96" role="img" aria-label="{{ $label }}">
    <circle cx="48" cy="48" r="40" fill="none" class="stroke-mist" stroke-width="10" />
    <circle
        cx="48" cy="48" r="40" fill="none"
        class="stroke-brand transition-[stroke-dasharray] duration-500"
        stroke-width="10"
        stroke-linecap="round"
        stroke-dasharray="{{ round($filled, 1) }} {{ round($circumference, 1) }}"
        transform="rotate(-90 48 48)"
    />
    <text x="48" y="54" text-anchor="middle" class="fill-ink font-display text-[20px] font-bold">{{ (int) floor($percent) }}%</text>
</svg>

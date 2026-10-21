@props(['name', 'strokeWidth' => 2])

<svg
    {{ $attributes->merge(['class' => 'size-5 shrink-0']) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('trend')
            <path d="M3 17l6-6 4 4 8-8" /><path d="M15 7h6v6" />
            @break
        @case('overview')
            <rect x="3" y="3" width="7" height="9" rx="1.5" /><rect x="14" y="3" width="7" height="5" rx="1.5" /><rect x="14" y="12" width="7" height="9" rx="1.5" /><rect x="3" y="16" width="7" height="5" rx="1.5" />
            @break
        @case('transactions')
            <path d="M7 7h13l-3-3M17 17H4l3 3" />
            @break
        @case('budget')
            <path d="M21 12a9 9 0 1 1-9-9v9z" /><path d="M21 12h-9" />
            @break
        @case('goal')
            <circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1" />
            @break
        @case('card')
            <rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('close')
            <path d="M18 6 6 18M6 6l12 12" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
            @break
        @case('trash')
            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" /><path d="M10 11v6M14 11v6" />
            @break
    @endswitch
</svg>

@props(['href', 'icon', 'active' => false])

<a
    href="{{ $href }}"
    @if ($href !== '#') wire:navigate @endif
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex min-h-11 items-center gap-3 rounded-xl px-3 py-3 no-underline transition-colors',
        'bg-ink-700 font-semibold text-white' => $active,
        'text-on-ink hover:bg-ink-800 hover:text-white' => ! $active,
    ]) }}
>
    <x-icon :name="$icon" />
    {{ $slot }}
</a>

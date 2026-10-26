@props(['href', 'icon', 'active' => false])

{{-- Com o menu recolhido (desktop) só o ícone aparece; o nome vira dica ao passar o mouse. --}}
<a
    href="{{ $href }}"
    @if ($href !== '#') wire:navigate @endif
    @if ($active) aria-current="page" @endif
    x-bind:title="sidebarCollapsed ? @js(trim(strip_tags((string) $slot))) : null"
    x-bind:class="{ 'lg:justify-center lg:px-0': sidebarCollapsed }"
    {{ $attributes->class([
        'flex min-h-11 items-center gap-3 rounded-xl px-3 py-3 no-underline transition-colors',
        'bg-ink-700 font-semibold text-white' => $active,
        'text-on-ink hover:bg-ink-800 hover:text-white' => ! $active,
    ]) }}
>
    <x-icon :name="$icon" />
    <span class="truncate whitespace-nowrap" x-bind:class="{ 'lg:sr-only': sidebarCollapsed }">{{ $slot }}</span>
</a>

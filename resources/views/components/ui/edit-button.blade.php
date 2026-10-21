@props(['label'])

<button
    type="button"
    {{ $attributes->class(['flex size-11 shrink-0 items-center justify-center rounded-xl text-muted hover:bg-mist hover:text-ink']) }}
    aria-label="{{ $label }}"
>
    <x-icon name="pencil" class="size-[18px]" />
</button>

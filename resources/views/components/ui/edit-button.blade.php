@props(['label', 'icon' => 'pencil'])

<button
    type="button"
    {{ $attributes->class(['flex size-11 shrink-0 items-center justify-center rounded-xl text-muted hover:bg-mist hover:text-strong']) }}
    aria-label="{{ $label }}"
>
    <x-icon :name="$icon" class="size-[18px]" />
</button>

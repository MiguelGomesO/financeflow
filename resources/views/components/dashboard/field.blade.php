@props(['label', 'for'])

<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    <label for="{{ $for }}" class="text-sm font-medium">{{ $label }}</label>
    {{ $slot }}
</div>

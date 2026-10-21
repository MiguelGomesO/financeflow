@props(['value', 'color' => 'bg-brand', 'track' => 'bg-mist', 'size' => 'h-2', 'label' => null])

@php($percent = max(0, min(100, (int) round($value))))

<div
    {{ $attributes->class(['w-full overflow-hidden rounded-full', $track, $size]) }}
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-valuenow="{{ $percent }}"
    @if ($label) aria-label="{{ $label }}" @endif
>
    <div @class(['h-full rounded-full transition-[width] duration-500', $color]) style="width: {{ $percent }}%"></div>
</div>

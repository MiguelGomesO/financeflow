@props(['label', 'for', 'error' => null])

<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    <label for="{{ $for }}" class="text-sm font-medium">{{ $label }}</label>
    {{ $slot }}
    @if ($error)
        @error($error)
            <p id="{{ $for }}-error" class="text-[13px] font-medium text-expense-dark">{{ $message }}</p>
        @enderror
    @endif
</div>

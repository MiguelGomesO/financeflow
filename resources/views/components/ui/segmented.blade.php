{{--
    Botões segmentados feitos com rádios reais (acessíveis pelo teclado).
    Uso: <x-ui.segmented label="Tipo" model="type" :options="[...]" live />
--}}
@props(['label', 'model', 'options', 'live' => false])

<div role="radiogroup" aria-label="{{ $label }}" {{ $attributes->class(['grid auto-cols-fr grid-flow-col gap-1 rounded-xl bg-mist p-1']) }}>
    @foreach ($options as $value => $text)
        <label class="flex min-h-9 cursor-pointer items-center justify-center rounded-[9px] px-3.5 text-sm font-semibold whitespace-nowrap text-body transition-colors has-checked:bg-white has-checked:text-ink has-checked:shadow-sm has-focus-visible:ring-2 has-focus-visible:ring-brand">
            <input
                type="radio"
                name="{{ $model }}"
                value="{{ $value }}"
                class="sr-only"
                @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif
            >
            {{ $text }}
        </label>
    @endforeach
</div>

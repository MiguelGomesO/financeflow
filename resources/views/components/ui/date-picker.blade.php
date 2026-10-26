{{--
    Calendário do projeto. Uso: <x-ui.date-picker id="x" wire:model="form.date" /> (mode="month" para mês e ano).
    O valor vai ao Livewire como "Y-m-d" (ou "Y-m" no modo mês).
--}}
@props(['id', 'mode' => 'date'])

<div
    x-data="datePicker(@js($mode))"
    x-modelable="value"
    {{ $attributes->whereStartsWith('wire:model') }}
    x-on:click.outside="open = false"
    x-on:keydown.escape="open = false"
    class="relative"
>
    <button
        type="button"
        id="{{ $id }}"
        x-ref="trigger"
        x-on:click="toggle()"
        aria-haspopup="dialog"
        x-bind:aria-expanded="open"
        class="input flex items-center justify-between gap-2 text-left"
    >
        <span x-text="label" x-bind:class="value ? '' : 'text-muted'"></span>
        <x-icon name="calendar" class="size-[18px] text-muted" />
    </button>

    <div
        x-show="open"
        x-transition.opacity.duration.100ms
        x-cloak
        role="dialog"
        aria-label="Escolher {{ $mode === 'month' ? 'mês' : 'data' }}"
        x-bind:class="up ? 'bottom-full mb-2' : 'top-full mt-2'"
        class="absolute left-0 z-50 w-[19rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-line bg-white p-3 shadow-xl"
    >
        <div class="mb-2 flex items-center justify-between">
            <button type="button" x-on:click="prev()" class="flex size-10 items-center justify-center rounded-xl text-muted hover:bg-mist hover:text-ink" aria-label="Anterior">
                <x-icon name="chevron-left" />
            </button>
            <span class="font-display text-[15px] font-semibold" x-text="title" aria-live="polite"></span>
            <button type="button" x-on:click="next()" class="flex size-10 items-center justify-center rounded-xl text-muted hover:bg-mist hover:text-ink" aria-label="Próximo">
                <x-icon name="chevron-right" />
            </button>
        </div>

        @if ($mode === 'month')
            <div class="grid grid-cols-3 gap-1">
                <template x-for="m in months" :key="m.iso">
                    <button
                        type="button"
                        x-on:click="pick(m.iso)"
                        x-text="m.name"
                        class="min-h-11 rounded-xl text-sm font-medium hover:bg-mist"
                        x-bind:class="{
                            'bg-brand text-white hover:bg-brand-dark': value === m.iso,
                            'ring-1 ring-brand/40': value !== m.iso && isToday(m.iso),
                        }"
                    ></button>
                </template>
            </div>
        @else
            <div class="grid grid-cols-7 text-center text-xs font-medium text-muted" aria-hidden="true">
                @foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $weekday)
                    <span class="py-1">{{ $weekday }}</span>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-y-1 text-center">
                <template x-for="(cell, index) in days" :key="index">
                    <div class="flex justify-center">
                        <template x-if="cell">
                            <button
                                type="button"
                                x-on:click="pick(cell.iso)"
                                x-text="cell.day"
                                class="size-10 rounded-full text-sm tabular-nums hover:bg-mist"
                                x-bind:class="{
                                    'bg-brand font-semibold text-white hover:bg-brand-dark': value === cell.iso,
                                    'font-semibold text-brand ring-1 ring-brand/40': value !== cell.iso && isToday(cell.iso),
                                }"
                            ></button>
                        </template>
                    </div>
                </template>
            </div>
        @endif

        <div class="mt-2 flex justify-end border-t border-line pt-2">
            <button type="button" x-on:click="pickToday()" class="min-h-10 rounded-xl px-3 text-sm font-semibold text-brand hover:bg-brand-light">
                {{ $mode === 'month' ? 'Este mês' : 'Hoje' }}
            </button>
        </div>
    </div>
</div>

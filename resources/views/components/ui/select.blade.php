{{--
    Select do projeto. Uso igual ao <select>: <x-ui.select id="x" wire:model="form.x" class="px-2.5"> <option>…</option> </x-ui.select>
    Aceita <optgroup>. Classes de layout (w-*, col-*, row-*, margens…) ficam no contêiner; as demais, no botão.
--}}
@props(['disabled' => false])

@php
    [$layout, $own] = collect(preg_split('/\s+/', trim((string) $attributes->get('class'))))
        ->filter()
        ->partition(fn ($class) => preg_match('/^(?:[a-z-]+:)*-?(?:col|row|w|max-w|min-w|flex|grow|shrink|basis|self|justify-self|place-self|order|m[trblxyse]?|z)(?:-|$)/', $class));
@endphp

<div
    x-data="selectBox"
    x-modelable="value"
    {{ $attributes->whereStartsWith('wire:model') }}
    x-on:click.outside="close()"
    x-on:scroll.window.capture="onScroll($event)"
    x-on:resize.window="close()"
    class="relative {{ $layout->implode(' ') }}"
>
    <select x-ref="source" hidden tabindex="-1" aria-hidden="true">{{ $slot }}</select>

    <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        x-on:keydown="onKey($event)"
        aria-haspopup="listbox"
        x-bind:aria-expanded="open"
        @disabled($disabled)
        {{ $attributes->only(['id', 'aria-label', 'aria-invalid', 'aria-describedby']) }}
        class="input flex items-center justify-between gap-2 text-left disabled:opacity-60 {{ $own->implode(' ') }}"
    >
        <span class="truncate" x-text="label"></span>
        <svg
            class="size-[18px] shrink-0 text-muted transition-transform"
            x-bind:class="{ 'rotate-180': open }"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
        ><path d="m6 9 6 6 6-6" /></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        x-ref="list"
        data-select-list
        role="listbox"
        x-bind:style="listStyle"
        class="fixed z-[60] flex flex-col gap-0.5 overflow-y-auto rounded-xl border border-line bg-surface p-1.5 text-strong shadow-xl"
    >
        <template x-for="(group, g) in groups" x-bind:key="g">
            <div class="flex flex-col gap-0.5">
                <div
                    x-show="group.label"
                    x-text="group.label"
                    class="px-2.5 pt-2 pb-1 text-xs font-semibold tracking-wide text-muted uppercase"
                ></div>

                <template x-for="option in group.items" x-bind:key="option.index">
                    <div
                        role="option"
                        x-bind:data-index="option.index"
                        x-bind:aria-selected="isSelected(option)"
                        x-bind:aria-disabled="option.disabled"
                        x-on:click="choose(option)"
                        x-on:mousemove="active = option.index"
                        class="flex min-h-10 cursor-pointer items-center justify-between gap-3 rounded-lg px-2.5 text-sm"
                        x-bind:class="{
                            'bg-mist': active === option.index,
                            'font-semibold text-brand-dark-fg': isSelected(option),
                            'cursor-not-allowed opacity-50': option.disabled,
                        }"
                    >
                        <span class="truncate" x-text="option.text"></span>
                        <svg
                            x-show="isSelected(option)"
                            class="size-4 shrink-0 text-brand-fg"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                        ><path d="M20 6 9 17l-5-5" /></svg>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

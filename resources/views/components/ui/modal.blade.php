{{--
    Modal genérico (Alpine).
    Abrir:  $dispatch('open-modal', { name: 'meu-modal' })
    Fechar: $dispatch('close-modal', { name: 'meu-modal' })  — ou $this->dispatch('close-modal', name: 'meu-modal') no Livewire
--}}
@props(['name', 'title'])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail.name === @js($name)) open = true"
    x-on:close-modal.window="if ($event.detail.name === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
>
    <div
        x-show="open"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-4 sm:items-center"
        x-on:click.self="open = false"
    >
        <div
            x-show="open"
            x-transition
            x-trap.noscroll="open"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $name }}-title"
            {{ $attributes->class(['flex w-full max-w-md flex-col gap-4 rounded-3xl bg-white p-6 shadow-xl']) }}
        >
            <div class="flex items-center justify-between">
                <h2 id="{{ $name }}-title" class="font-display text-xl font-semibold">{{ $title }}</h2>
                <button type="button" x-on:click="open = false" class="flex size-11 items-center justify-center rounded-xl text-muted hover:bg-mist" aria-label="Fechar">
                    <x-icon name="close" />
                </button>
            </div>

            {{ $slot }}
        </div>
    </div>
</div>

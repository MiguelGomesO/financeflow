@props(['types', 'categories', 'tags' => [], 'hasFilters' => false])

<div class="flex flex-wrap items-center gap-3">
    <label for="search" class="sr-only">Buscar transação</label>
    <div class="relative min-w-0 flex-[1_1_160px]">
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-[18px] -translate-y-1/2 text-muted" />
        <input
            id="search"
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar"
            class="input bg-canvas pl-10"
        >
    </div>

    <x-ui.segmented label="Tipo" model="type" :options="$types" live />

    <label for="category" class="sr-only">Categoria</label>
    <x-ui.select id="category" wire:model.live="category" class="input w-auto">
        <option value="">Todas as categorias</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-ui.select>

    @if (count($tags) > 0)
        <label for="tag" class="sr-only">Etiqueta</label>
        <x-ui.select id="tag" wire:model.live="tag" class="input w-auto">
            <option value="">Todas as etiquetas</option>
            @foreach ($tags as $tag)
                <option value="{{ $tag->id }}">#{{ $tag->name }}</option>
            @endforeach
        </x-ui.select>
    @endif

    @if ($hasFilters)
        <button type="button" wire:click="clearFilters" class="min-h-11 rounded-xl px-3 text-sm font-semibold text-brand hover:bg-brand-light">
            Limpar filtros
        </button>
    @endif
</div>

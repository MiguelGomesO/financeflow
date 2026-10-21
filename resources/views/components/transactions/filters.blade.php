@props(['types', 'categories', 'hasFilters' => false])

<div class="flex flex-wrap items-center gap-3">
    <label for="search" class="sr-only">Buscar transação</label>
    <div class="relative min-w-0 flex-[1_1_220px]">
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-[18px] -translate-y-1/2 text-muted" />
        <input
            id="search"
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por descrição"
            class="input bg-canvas pl-10"
        >
    </div>

    <x-ui.segmented label="Tipo" model="type" :options="$types" live />

    <label for="category" class="sr-only">Categoria</label>
    <select id="category" wire:model.live="category" class="input w-auto">
        <option value="">Todas as categorias</option>
        @foreach ($categories as $category)
            <option value="{{ $category }}">{{ $category }}</option>
        @endforeach
    </select>

    @if ($hasFilters)
        <button type="button" wire:click="clearFilters" class="min-h-11 rounded-xl px-3 text-sm font-semibold text-brand hover:bg-brand-light">
            Limpar filtros
        </button>
    @endif
</div>

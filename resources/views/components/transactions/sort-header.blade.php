@props(['column', 'label', 'sort', 'direction', 'align' => 'left'])

@php($active = $sort === $column)

<th
    scope="col"
    aria-sort="{{ $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    @class(['px-2 py-1.5 font-medium', 'text-right' => $align === 'right'])
>
    <button
        type="button"
        wire:click="sortBy('{{ $column }}')"
        @class([
            '-mx-1.5 inline-flex min-h-9 items-center gap-1 rounded-lg px-1.5 hover:bg-mist hover:text-ink',
            'font-semibold text-ink' => $active,
        ])
    >
        {{ $label }}
        <span aria-hidden="true" @class(['text-[11px]', 'text-line-strong' => ! $active])>{{ $active ? ($direction === 'asc' ? '▲' : '▼') : '↕' }}</span>
        <span class="sr-only">{{ $active ? ($direction === 'asc' ? ', em ordem crescente' : ', em ordem decrescente') : ', ordenar' }}</span>
    </button>
</th>
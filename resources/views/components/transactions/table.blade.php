@use('App\Support\Money')
@props(['rows', 'hasFilters' => false, 'selected' => [], 'sort' => 'date', 'direction' => 'desc'])

<div class="-mx-2 overflow-x-auto" wire:loading.class="opacity-60" wire:target="month,search,type,category,tag,sortBy,clearFilters,gotoPage,nextPage,previousPage">
    <table class="w-full min-w-[560px] border-collapse text-sm">
        <thead>
            <tr class="text-left text-[13px] text-muted">
                <th scope="col" class="w-10 px-2 py-2.5" x-on:click.stop>
                    {{-- Os ids da página vão num atributo para continuarem certos depois de trocar de página. --}}
                    <input
                        type="checkbox"
                        aria-label="Marcar todas desta página"
                        class="size-[18px] cursor-pointer accent-brand"
                        data-ids="{{ $rows->pluck('id')->map(fn ($id) => (string) $id)->toJson() }}"
                        x-bind:checked="JSON.parse($el.dataset.ids).length > 0 && JSON.parse($el.dataset.ids).every((id) => $wire.selected.includes(id))"
                        x-on:change="
                            const ids = JSON.parse($el.dataset.ids);
                            $wire.$set('selected', $el.checked
                                ? [...new Set([...$wire.selected, ...ids])]
                                : $wire.selected.filter((id) => ! ids.includes(id)))
                        "
                    >
                </th>
                <x-transactions.sort-header column="date" label="Data" :sort="$sort" :direction="$direction" />
                <x-transactions.sort-header column="description" label="Descrição" :sort="$sort" :direction="$direction" />
                <x-transactions.sort-header column="category" label="Categoria" :sort="$sort" :direction="$direction" />
                <th scope="col" class="px-2 py-2.5 font-medium">Conta</th>
                <x-transactions.sort-header column="amount" label="Valor" align="right" :sort="$sort" :direction="$direction" />
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                {{-- A linha inteira abre a edição; o botão da descrição serve ao teclado e a leitores de tela. --}}
                <tr
                    wire:key="tx-{{ $row->id }}"
                    x-on:click="$dispatch('edit-transaction', { id: {{ $row->id }} })"
                    data-tooltip="Clique para editar"
                    @class([
                        'cursor-pointer border-t border-mist hover:bg-mist/60',
                        'bg-brand-light/50' => in_array((string) $row->id, $selected, true),
                    ])
                >
                    <td class="px-2 py-3" x-on:click.stop>
                        <input
                            type="checkbox"
                            wire:model.live="selected"
                            value="{{ $row->id }}"
                            aria-label="Marcar {{ $row->description }}"
                            class="size-[18px] cursor-pointer accent-brand"
                        >
                    </td>
                    <td class="px-2 py-3 text-muted tabular-nums">{{ $row->date->format('d/m') }}</td>
                    <td class="px-2 py-1.5">
                        <button
                            type="button"
                            x-on:click.stop="$dispatch('edit-transaction', { id: {{ $row->id }} })"
                            class="-mx-1.5 min-h-9 rounded-lg px-1.5 text-left font-medium"
                            aria-label="Editar {{ $row->description }}"
                        >
                            {{ $row->description }}
                            @if ($row->installment_label)
                                <span class="ml-1 text-[13px] font-normal text-muted tabular-nums">{{ $row->installment_label }}</span>
                            @endif
                        </button>
                        @if ($row->attachment_path)
                            <a
                                href="{{ route('receipts.show', $row) }}"
                                target="_blank"
                                rel="noopener"
                                x-on:click.stop
                                title="Ver comprovante"
                                aria-label="Ver comprovante de {{ $row->description }}"
                                class="ml-1 inline-flex align-middle text-muted hover:text-brand-fg"
                            >
                                <x-icon name="paperclip" class="size-4" />
                            </a>
                        @endif
                        @if ($row->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1 pt-0.5">
                                @foreach ($row->tags as $tag)
                                    <span class="rounded-full bg-brand-light px-2 py-0.5 text-xs text-brand-dark-fg">#{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="px-2 py-3">
                        <span class="rounded-full bg-mist px-2.5 py-0.5 text-[13px] text-soft">{{ $row->category->name }}</span>
                    </td>
                    <td class="px-2 py-3 text-muted">{{ $row->source_name }}</td>
                    <td @class([
                        'px-2 py-3 text-right font-semibold tabular-nums whitespace-nowrap',
                        'text-brand-fg' => $row->amount > 0,
                    ])>
                        {{ Money::brl($row->amount, signed: true) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($rows->isEmpty())
        <div class="flex flex-col items-center gap-3 px-2 py-10 text-center">
            <p class="text-muted">
                {{ $hasFilters ? 'Nenhuma transação encontrada com esses filtros.' : 'Nenhuma transação neste mês.' }}
            </p>
            @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist">
                    Limpar filtros
                </button>
            @endif
        </div>
    @endif
</div>

@if ($rows->hasPages())
    <div class="pt-2">
        {{ $rows->links() }}
    </div>
@endif

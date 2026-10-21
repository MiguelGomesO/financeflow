@use('App\Support\Money')
@use('Illuminate\Support\Carbon')
@props(['rows', 'hasFilters' => false])

<div class="-mx-2 overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,type,category,clearFilters">
    <table class="w-full min-w-[560px] border-collapse text-sm">
        <thead>
            <tr class="text-left text-[13px] text-muted">
                <th scope="col" class="px-2 py-2.5 font-medium">Data</th>
                <th scope="col" class="px-2 py-2.5 font-medium">Descrição</th>
                <th scope="col" class="px-2 py-2.5 font-medium">Categoria</th>
                <th scope="col" class="px-2 py-2.5 font-medium">Conta</th>
                <th scope="col" class="px-2 py-2.5 text-right font-medium">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr wire:key="tx-{{ $row['id'] }}" class="border-t border-mist">
                    <td class="px-2 py-3 text-muted tabular-nums">{{ Carbon::parse($row['date'])->format('d/m') }}</td>
                    <td class="px-2 py-3 font-medium">{{ $row['description'] }}</td>
                    <td class="px-2 py-3">
                        <span class="rounded-full bg-mist px-2.5 py-0.5 text-[13px] text-soft">{{ $row['category'] }}</span>
                    </td>
                    <td class="px-2 py-3 text-muted">{{ $row['account'] }}</td>
                    <td @class([
                        'px-2 py-3 text-right font-semibold tabular-nums whitespace-nowrap',
                        'text-brand' => $row['amount'] > 0,
                    ])>
                        {{ Money::brl($row['amount'], signed: true) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (empty($rows))
        <div class="flex flex-col items-center gap-3 px-2 py-10 text-center">
            <p class="text-muted">
                {{ $hasFilters ? 'Nenhuma transação encontrada com esses filtros.' : 'Nenhuma transação registrada ainda.' }}
            </p>
            @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist">
                    Limpar filtros
                </button>
            @endif
        </div>
    @endif
</div>

@use('App\Support\Money')

<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Relatórios</h1>
            <p class="text-muted first-letter:uppercase">{{ $rangeLabel }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <label for="period" class="text-sm text-muted">Período</label>
            <x-ui.select id="period" wire:model.live="period" class="input w-auto">
                @foreach ($periods as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <div wire:loading.class="opacity-60" wire:target="period" class="flex flex-col gap-6">
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($summary as $card)
                <x-ui.stat-card
                    wire:key="summary-{{ $card['label'] }}"
                    :tone="$card['tone']"
                    :label="$card['label']"
                    :value="Money::brl($card['value'])"
                    :hint="$card['hint']"
                />
            @endforeach
        </div>

        <p class="-mt-3 text-[13px] text-muted">
            Comparado a {{ $comparedTo }}, o mesmo trecho do período anterior.
        </p>

        <x-dashboard.cash-flow-chart :months="$months" title="Evolução mensal" />

        <details class="-mt-3 rounded-2xl border border-line bg-white px-5 py-3 text-sm">
            <summary class="cursor-pointer font-semibold text-brand hover:text-brand-dark">Ver evolução em tabela</summary>
            <div class="-mx-2 mt-3 overflow-x-auto">
                <table class="w-full min-w-[420px] border-collapse">
                    <thead>
                        <tr class="text-left text-[13px] text-muted">
                            <th scope="col" class="px-2 py-2 font-medium">Mês</th>
                            <th scope="col" class="px-2 py-2 text-right font-medium">Entradas</th>
                            <th scope="col" class="px-2 py-2 text-right font-medium">Saídas</th>
                            <th scope="col" class="px-2 py-2 text-right font-medium">Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($months as $month)
                            <tr wire:key="table-{{ $month['key'] }}" class="border-t border-mist tabular-nums">
                                <td class="px-2 py-2 capitalize">{{ $month['name'] }}</td>
                                <td class="px-2 py-2 text-right">{{ Money::brl($month['income']) }}</td>
                                <td class="px-2 py-2 text-right">{{ Money::brl($month['expense']) }}</td>
                                <td class="px-2 py-2 text-right font-semibold">{{ Money::brl($month['income'] - $month['expense']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

        <x-reports.categories :categories="$categories" :expense="$expense" />
    </div>
</div>

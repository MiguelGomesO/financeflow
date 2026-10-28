@use('App\Support\Money')

<div>
    <button
        type="button"
        wire:click="open"
        class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line-strong bg-surface px-[18px] font-semibold text-strong hover:bg-mist"
    >
        <x-icon name="download" class="size-[18px]" />
        Importar extrato
    </button>

    <x-ui.modal name="import" title="Importar extrato" class="max-h-[92vh] max-w-3xl! overflow-y-auto">
        @if ($accounts->isEmpty() && $cards->isEmpty())
            <p class="text-body">Cadastre uma conta ou cartão antes de importar um extrato.</p>
        @elseif ($done)
            <div class="flex flex-col items-start gap-3 rounded-2xl bg-brand-light p-5 text-brand-dark-fg" role="status">
                <p class="font-display text-xl font-semibold">
                    {{ $done['imported'] }} {{ $done['imported'] === 1 ? 'lançamento importado' : 'lançamentos importados' }}
                </p>
                @if ($done['skipped'] > 0)
                    <p class="text-sm">
                        {{ $done['skipped'] }} {{ $done['skipped'] === 1 ? 'lançamento foi ignorado' : 'lançamentos foram ignorados' }} por já existir{{ $done['skipped'] === 1 ? '' : 'em' }} nesta conta.
                    </p>
                @endif
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'import' })" class="min-h-11 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark">
                    Concluir
                </button>
            </div>
        @elseif ($rows === [])
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field label="Importar para" for="import-source" error="source">
                    <x-ui.select id="import-source" wire:model.live="source" class="input px-2.5">
                        <optgroup label="Contas">
                            @foreach ($accounts as $account)
                                <option value="account:{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        @if ($cards->isNotEmpty())
                            <optgroup label="Cartões">
                                @foreach ($cards as $card)
                                    <option value="card:{{ $card->id }}">{{ $card->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Arquivo (Excel, PDF ou OFX)" for="import-file" error="file">
                    <input
                        id="import-file"
                        type="file"
                        wire:model="file"
                        accept=".xlsx,.xls,.pdf,.ofx,.qfx"
                        class="input py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-dark-fg"
                        @error('file') aria-invalid="true" aria-describedby="import-file-error" @enderror
                    >
                </x-ui.field>
            </div>

            <p class="text-[13px] text-muted">
                Planilha com colunas Data e Valor (ou Crédito e Débito), PDF de extrato ou fatura com texto selecionável (não vale foto ou digitalização), ou OFX. Nada é salvo antes de você confirmar.
            </p>

            <button
                type="button"
                wire:click="read"
                class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                wire:loading.attr="disabled"
                wire:target="read,file"
            >
                <span wire:loading.remove wire:target="read,file">Ler arquivo</span>
                <span wire:loading wire:target="read,file">Lendo…</span>
            </button>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-ui.select wire:model.live="source" class="input w-auto px-2.5" aria-label="Importar para">
                    <optgroup label="Contas">
                        @foreach ($accounts as $account)
                            <option value="account:{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </optgroup>
                    @if ($cards->isNotEmpty())
                        <optgroup label="Cartões">
                            @foreach ($cards as $card)
                                <option value="card:{{ $card->id }}">{{ $card->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </x-ui.select>

                <label class="flex min-h-11 items-center gap-2 text-sm font-medium">
                    <input type="checkbox" wire:model.live="invert" class="size-4 accent-brand">
                    Valores positivos são despesas
                </label>
            </div>

            @if ($unreadable > 0)
                <p class="rounded-xl bg-mist px-4 py-3 text-sm text-body">
                    {{ $unreadable }} {{ $unreadable === 1 ? 'linha foi ignorada' : 'linhas foram ignoradas' }} por não ter data ou valor.
                </p>
            @endif

            @php($flagged = collect($rows)->filter(fn ($row) => $row['duplicate'] || $row['similarTo'] !== null)->count())
            @if ($flagged > 0)
                <p class="rounded-xl bg-expense-faint px-4 py-3 text-sm text-expense-dark-fg" role="status">
                    <strong>{{ $flagged }}</strong> {{ $flagged === 1 ? 'linha parece já ter sido lançada e foi desmarcada' : 'linhas parecem já ter sido lançadas e foram desmarcadas' }}.
                    Marque de volta as que forem compras diferentes.
                </p>
            @endif

            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                <button type="button" wire:click="toggleAll(true)" class="font-semibold text-brand-fg hover:text-brand-dark-fg">Marcar todos</button>
                <button type="button" wire:click="toggleAll(false)" class="font-semibold text-brand-fg hover:text-brand-dark-fg">Desmarcar todos</button>
            </div>

            <ul class="flex max-h-[45vh] flex-col divide-y divide-line overflow-y-auto">
                @foreach ($rows as $i => $row)
                    @php($type = $row['amount'] > 0 ? 'income' : 'expense')
                    <li wire:key="row-{{ $i }}" @class(['grid grid-cols-[auto_1fr_auto] items-center gap-x-3 gap-y-2 py-3 sm:grid-cols-[auto_5.5rem_1fr_10rem_auto]', 'opacity-60' => ! $row['selected']])>
                        <input
                            type="checkbox"
                            wire:model.live="rows.{{ $i }}.selected"
                            :disabled="$row['error'] !== null"
                            class="size-5 accent-brand"
                            aria-label="Importar {{ $row['description'] }}"
                        >

                        <span class="text-sm tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</span>

                        <div class="col-span-3 col-start-1 row-start-2 flex min-w-0 flex-col sm:col-span-1 sm:col-start-auto sm:row-start-auto">
                            <span class="truncate font-medium" title="{{ $row['description'] }}">{{ $row['description'] }}</span>
                            @if ($row['duplicate'])
                                <span class="text-xs font-semibold text-expense-dark-fg">Parece já lançado</span>
                            @elseif ($row['similarTo'])
                                <span class="truncate text-xs font-semibold text-expense-dark-fg" title="{{ $row['similarTo'] }}">Parecido com “{{ $row['similarTo'] }}”</span>
                            @elseif ($row['error'])
                                <span class="text-xs font-semibold text-expense-dark-fg">{{ $row['error'] }}</span>
                            @endif
                        </div>

                        <x-ui.select
                            wire:model="rows.{{ $i }}.categoryId"
                            class="input col-span-2 col-start-1 row-start-3 min-h-10 px-2.5 text-sm sm:col-span-1 sm:col-start-auto sm:row-start-auto"
                            aria-label="Categoria de {{ $row['description'] }}"
                            :disabled="$row['error'] !== null"
                        >
                            @foreach ($categories[$type] ?? [] as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>

                        <span @class([
                            'justify-self-end font-display font-semibold tabular-nums whitespace-nowrap',
                            'text-brand-fg' => $row['amount'] > 0,
                            'text-expense-fg' => $row['amount'] < 0,
                        ])>{{ Money::brl($row['amount'], signed: true) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                <p class="text-sm text-body">
                    <strong>{{ $summary['count'] }}</strong> selecionados ·
                    <span class="text-brand-fg">{{ Money::brl($summary['income']) }}</span> de entradas ·
                    <span class="text-expense-fg">{{ Money::brl($summary['expense']) }}</span> de saídas
                </p>

                <div class="flex gap-2">
                    <button type="button" wire:click="discard" class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist">
                        Voltar
                    </button>
                    <button
                        type="button"
                        wire:click="import"
                        @disabled($summary['count'] === 0)
                        class="min-h-11 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="import"
                    >
                        Importar {{ $summary['count'] }}
                    </button>
                </div>
            </div>
        @endif
    </x-ui.modal>
</div>

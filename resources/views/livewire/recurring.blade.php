@use('App\Support\Money')

<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Recorrentes</h1>
            <p class="text-muted">Lançamentos que se repetem todo mês, como salário, aluguel e assinaturas</p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark"
        >
            <x-icon name="plus" stroke-width="2.4" class="size-[18px]" />
            Nova recorrência
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card size="sm" tone="income" label="Entradas fixas por mês" :value="Money::brl($totals['income'])" />
        <x-ui.stat-card size="sm" tone="expense" label="Saídas fixas por mês" :value="Money::brl($totals['expense'])" />
        <x-ui.stat-card size="sm" tone="dark" label="Saldo fixo do mês" :value="Money::brl($totals['income'] - $totals['expense'])" />
    </div>

    @if ($rules->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-[20px] border border-dashed border-line-strong bg-surface px-6 py-12 text-center">
            <p class="text-muted">Você ainda não tem lançamentos recorrentes.</p>
            <button type="button" wire:click="create" class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist">
                Criar o primeiro
            </button>
        </div>
    @else
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,320px),1fr))] gap-4">
            @foreach ($rules as $rule)
                @php($next = $rule->active ? $rule->nextDate() : null)
                <article
                    wire:key="rule-{{ $rule->id }}"
                    @class([
                        'flex h-full flex-col gap-4 rounded-[20px] border border-line bg-surface p-6',
                        'opacity-70' => ! $rule->active,
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <h2 class="truncate font-display text-[19px] font-semibold" title="{{ $rule->description }}">{{ $rule->description }}</h2>
                            <span class="truncate text-[13px] text-muted">{{ $rule->category->name }} · {{ $rule->source_name }}</span>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap',
                            'bg-brand-light text-brand-dark-fg' => $rule->active,
                            'bg-mist text-soft' => ! $rule->active,
                        ])>{{ $rule->active ? 'Ativa' : 'Pausada' }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <span @class([
                            'font-display text-2xl font-semibold tabular-nums',
                            'text-brand-fg' => $rule->amount > 0,
                            'text-expense-fg' => $rule->amount < 0,
                        ])>{{ Money::brl($rule->amount, signed: true) }}</span>
                        <span class="text-sm text-muted">Todo dia {{ $rule->day_of_month }}</span>
                        <span class="text-[13px] text-soft">
                            @if (! $rule->active)
                                Pausada, não lança novos valores
                            @elseif ($next)
                                Próximo: {{ $next->format('d/m/Y') }}
                            @else
                                Encerrada em {{ $rule->ends_on->format('d/m/Y') }}
                            @endif
                            @if ($rule->ends_on && $next)
                                · até {{ $rule->ends_on->format('d/m/Y') }}
                            @endif
                        </span>
                    </div>

                    <div class="mt-auto flex gap-2">
                        <button
                            type="button"
                            wire:click="toggle({{ $rule->id }})"
                            class="min-h-11 flex-1 rounded-xl bg-brand-light text-sm font-semibold text-brand-dark-fg hover:bg-brand hover:text-white"
                        >
                            {{ $rule->active ? 'Pausar' : 'Retomar' }}
                        </button>
                        <button
                            type="button"
                            wire:click="edit({{ $rule->id }})"
                            class="min-h-11 flex-1 rounded-xl border border-line-strong bg-surface text-sm font-semibold text-strong hover:bg-mist"
                            aria-label="Editar {{ $rule->description }}"
                        >
                            Editar
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <x-recurring.form-modal
        :editing="$editing"
        :type="$form->type"
        :categories="$categories"
        :accounts="$accounts"
        :cards="$cards"
        :ends-on="$form->endsOn"
    />
</div>

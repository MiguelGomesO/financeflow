{{--
    Modal de nova transação — por enquanto só front-end (Alpine).
    Abre com: $dispatch('open-new-transaction')
    O envio será ligado a um componente Livewire quando o back-end existir.
--}}
<div
    x-data="{ open: false, kind: 'expense' }"
    x-on:open-new-transaction.window="open = true; $nextTick(() => $refs.description.focus())"
    x-on:keydown.escape.window="open = false"
>
    <div
        x-show="open"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-4 sm:items-center"
        x-on:click.self="open = false"
    >
        <form
            x-show="open"
            x-transition
            x-trap.noscroll="open"
            x-on:submit.prevent="open = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="new-transaction-title"
            class="flex w-full max-w-md flex-col gap-4 rounded-3xl bg-white p-6 shadow-xl"
        >
            <div class="flex items-center justify-between">
                <h2 id="new-transaction-title" class="font-display text-xl font-semibold">Nova transação</h2>
                <button type="button" x-on:click="open = false" class="flex size-11 items-center justify-center rounded-xl text-muted hover:bg-mist" aria-label="Fechar">
                    <x-icon name="close" />
                </button>
            </div>

            <div role="group" aria-label="Tipo de transação" class="grid grid-cols-2 gap-1 rounded-xl bg-mist p-1">
                <button type="button" x-on:click="kind = 'expense'" x-bind:aria-pressed="kind === 'expense'"
                    class="min-h-10 rounded-[9px] text-sm font-semibold transition-colors"
                    x-bind:class="kind === 'expense' ? 'bg-white text-ink shadow-sm' : 'text-body'">Despesa</button>
                <button type="button" x-on:click="kind = 'income'" x-bind:aria-pressed="kind === 'income'"
                    class="min-h-10 rounded-[9px] text-sm font-semibold transition-colors"
                    x-bind:class="kind === 'income' ? 'bg-white text-ink shadow-sm' : 'text-body'">Receita</button>
            </div>

            <x-dashboard.field label="Descrição" for="tx-description">
                <input id="tx-description" x-ref="description" type="text" placeholder="Ex.: Padaria" class="input">
            </x-dashboard.field>

            <x-dashboard.field label="Valor (R$)" for="tx-amount">
                <input id="tx-amount" type="text" inputmode="decimal" placeholder="0,00" class="input font-display text-xl">
            </x-dashboard.field>

            <div class="grid grid-cols-2 gap-3">
                <x-dashboard.field label="Categoria" for="tx-category">
                    <select id="tx-category" class="input">
                        <option>Mercado</option>
                        <option>Moradia</option>
                        <option>Transporte</option>
                        <option>Lazer</option>
                        <option>Saúde</option>
                        <option>Renda</option>
                    </select>
                </x-dashboard.field>

                <x-dashboard.field label="Conta" for="tx-account">
                    <select id="tx-account" class="input">
                        <option>Conta corrente</option>
                        <option>Cartão de crédito</option>
                        <option>Carteira</option>
                    </select>
                </x-dashboard.field>
            </div>

            <x-dashboard.field label="Data" for="tx-date">
                <input id="tx-date" type="date" value="{{ now()->toDateString() }}" class="input">
            </x-dashboard.field>

            <button type="submit" class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark">
                Salvar transação
            </button>
        </form>
    </div>
</div>

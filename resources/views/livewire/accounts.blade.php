@use('App\Support\Money')

<div class="mx-auto flex max-w-7xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-[30px] font-semibold tracking-tight">Contas e cartões</h1>
            <p class="text-muted">Onde está o seu dinheiro</p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            <button
                type="button"
                wire:click="openTransfer"
                @disabled($accounts->count() < 2)
                @if ($accounts->count() < 2) title="Cadastre ao menos duas contas" @endif
                class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line-strong bg-white px-[18px] font-semibold text-ink hover:bg-mist disabled:cursor-not-allowed disabled:opacity-50"
            >
                <x-icon name="transactions" class="size-[18px]" />
                Transferir
            </button>
            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-[18px] font-semibold text-white hover:bg-brand-dark"
            >
                <x-icon name="plus" stroke-width="2.4" class="size-[18px]" />
                Adicionar conta
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card size="sm" tone="dark" label="Patrimônio líquido" :value="Money::brl($totals['net'])" />
        <x-ui.stat-card size="sm" tone="income" label="Saldo em contas" :value="Money::brl($totals['balance'])" />
        <x-ui.stat-card size="sm" tone="expense" label="Faturas em aberto" :value="Money::brl($totals['bills'])" />
    </div>

    <section class="flex flex-col gap-3.5" aria-labelledby="accounts-title">
        <h2 id="accounts-title" class="font-display text-xl font-semibold">Contas</h2>
        @if ($accounts->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-[20px] border border-dashed border-line-strong bg-white px-6 py-10 text-center">
                <p class="text-muted">Nenhuma conta cadastrada. Comece pela sua conta principal.</p>
                <button type="button" wire:click="openCreate" class="min-h-11 rounded-xl border border-line-strong px-4 text-sm font-semibold hover:bg-mist">
                    Adicionar conta
                </button>
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-4">
                @foreach ($accounts as $account)
                    <x-accounts.account-card wire:key="account-{{ $account->id }}" :account="$account" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="flex flex-col gap-3.5" aria-labelledby="cards-title">
        <h2 id="cards-title" class="font-display text-xl font-semibold">Cartões de crédito</h2>
        @if ($cards->isEmpty())
            <p class="rounded-[20px] border border-dashed border-line-strong bg-white px-6 py-8 text-center text-muted">
                Nenhum cartão cadastrado.
            </p>
        @else
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,400px),1fr))] gap-4">
                @foreach ($cards as $card)
                    <x-accounts.credit-card wire:key="card-{{ $card->id }}" :card="$card" :bill="$bills[$card->id]" :used="$used[$card->id]" />
                @endforeach
            </div>
        @endif
    </section>

    @if ($transfers->isNotEmpty())
        <x-accounts.transfers-list :transfers="$transfers" />
    @endif

    <x-accounts.add-modal
        :types="$accountTypes"
        :is-card="$form->isCard()"
        :editing-kind="$editingKind"
        :delete-error="$deleteError"
    />
    <x-accounts.transfer-modal :accounts="$accounts" />
    <x-accounts.bill-modal :card="$cards->firstWhere('id', $billCardId)" />
</div>

@php($noSource = $accounts->isEmpty() && $cards->isEmpty())

<div>
    @if ($inline)
        <x-ui.card id="nova-transacao" title="Nova transação" class="scroll-mt-20">
            @if ($noSource)
                <p class="text-body">Cadastre uma conta antes de lançar transações.</p>
            @else
                <form
                    wire:submit="save"
                    class="flex flex-col gap-4"
                    x-data="{ saved: false }"
                    x-on:transaction-saved.window="saved = true; setTimeout(() => saved = false, 2500); document.getElementById('tx-description')?.focus()"
                >
                    <x-transactions.form-fields installments prefix="tx" :type="$form->type" :categories="$categories" :accounts="$accounts" :cards="$cards" :tag-suggestions="$tagSuggestions" />

                    <button
                        type="submit"
                        class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">Salvar transação</span>
                        <span wire:loading wire:target="save">Salvando…</span>
                    </button>

                    <p x-show="saved" x-transition.opacity x-cloak role="status" class="text-center text-sm font-medium text-brand-fg">
                        Transação adicionada.
                    </p>
                </form>
            @endif
        </x-ui.card>
    @else
        <x-ui.modal name="new-transaction" title="Nova transação">
            @if ($noSource)
                <p class="text-body">Cadastre uma conta antes de lançar transações.</p>
            @else
                <form wire:submit="save" class="flex flex-col gap-4">
                    <x-transactions.form-fields installments prefix="new-tx" :type="$form->type" :categories="$categories" :accounts="$accounts" :cards="$cards" :tag-suggestions="$tagSuggestions" />

                    <button
                        type="submit"
                        class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        Salvar transação
                    </button>
                </form>
            @endif
        </x-ui.modal>
    @endif
</div>

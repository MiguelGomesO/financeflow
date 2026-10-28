<div>
    <x-ui.modal name="edit-transaction" title="Editar transação">
        <form wire:submit="save" class="flex flex-col gap-4">
            @if ($installmentInfo)
                <p class="rounded-xl bg-mist px-4 py-3 text-sm text-soft">
                    <span class="font-semibold text-ink">{{ $installmentInfo }}.</span>
                    As alterações valem só para esta parcela.
                </p>
            @endif

            <x-transactions.form-fields prefix="edit-tx" :type="$form->type" :categories="$categories" :accounts="$accounts" :cards="$cards" />

            <button
                type="submit"
                class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                Salvar alterações
            </button>

            <button
                type="button"
                wire:click="delete"
                wire:confirm="{{ $installmentInfo ? 'Excluir só esta parcela?' : 'Excluir esta transação?' }}"
                class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark hover:bg-expense-light"
            >
                {{ $installmentInfo ? 'Excluir só esta parcela' : 'Excluir transação' }}
            </button>

            @if ($installmentInfo)
                <button
                    type="button"
                    wire:click="deleteInstallments('following')"
                    wire:confirm="Excluir esta parcela e as próximas?"
                    class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark hover:bg-expense-light"
                >
                    Excluir esta e as próximas
                </button>
                <button
                    type="button"
                    wire:click="deleteInstallments('all')"
                    wire:confirm="Excluir todas as parcelas desta compra?"
                    class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark hover:bg-expense-light"
                >
                    Excluir todas as parcelas
                </button>
            @endif
        </form>
    </x-ui.modal>
</div>

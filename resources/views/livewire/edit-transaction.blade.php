<div>
    <x-ui.modal name="edit-transaction" title="Editar transação">
        <form wire:submit="save" class="flex flex-col gap-4">
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
                wire:confirm="Excluir esta transação?"
                class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark hover:bg-expense-light"
            >
                Excluir transação
            </button>
        </form>
    </x-ui.modal>
</div>

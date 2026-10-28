<div>
    <x-ui.modal name="edit-transaction" title="Editar transação">
        <form wire:submit="save" class="flex flex-col gap-4">
            @if ($installmentInfo)
                <p class="rounded-xl bg-mist px-4 py-3 text-sm text-soft">
                    <span class="font-semibold text-strong">{{ $installmentInfo }}.</span>
                    As alterações valem só para esta parcela.
                </p>
            @endif

            <x-transactions.form-fields prefix="edit-tx" :type="$form->type" :categories="$categories" :accounts="$accounts" :cards="$cards" :tag-suggestions="$tagSuggestions" />

            @if ($hasAttachment && ! $removeAttachment)
                <div class="-mt-2 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-mist px-4 py-2.5 text-sm">
                    <a href="{{ route('receipts.show', $transactionId) }}" target="_blank" rel="noopener" class="font-semibold text-brand-fg hover:text-brand-dark-fg">
                        Ver comprovante atual
                    </a>
                    <button type="button" wire:click="removeReceipt" class="font-semibold text-expense-dark-fg hover:underline">Remover</button>
                </div>
            @elseif ($hasAttachment)
                <p class="-mt-2 text-[13px] text-muted">O comprovante atual será removido ao salvar.</p>
            @endif

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
                class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark-fg hover:bg-expense-light"
            >
                {{ $installmentInfo ? 'Excluir só esta parcela' : 'Excluir transação' }}
            </button>

            @if ($installmentInfo)
                <button
                    type="button"
                    wire:click="deleteInstallments('following')"
                    wire:confirm="Excluir esta parcela e as próximas?"
                    class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark-fg hover:bg-expense-light"
                >
                    Excluir esta e as próximas
                </button>
                <button
                    type="button"
                    wire:click="deleteInstallments('all')"
                    wire:confirm="Excluir todas as parcelas desta compra?"
                    class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark-fg hover:bg-expense-light"
                >
                    Excluir todas as parcelas
                </button>
            @endif
        </form>
    </x-ui.modal>
</div>

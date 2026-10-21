<div>
    <x-ui.modal name="new-transaction" title="Nova transação">
        @if ($accounts->isEmpty() && $cards->isEmpty())
            <p class="text-body">Cadastre uma conta antes de lançar transações.</p>
        @else
            <form wire:submit="save" class="flex flex-col gap-4">
                <x-ui.segmented label="Tipo de transação" model="type" :options="['expense' => 'Despesa', 'income' => 'Receita']" live />

                <x-ui.field label="Descrição" for="new-tx-description" error="description">
                    <input
                        id="new-tx-description"
                        type="text"
                        wire:model="description"
                        placeholder="Ex.: Padaria"
                        maxlength="120"
                        class="input"
                        @error('description') aria-invalid="true" aria-describedby="new-tx-description-error" @enderror
                    >
                </x-ui.field>

                <x-ui.field label="Valor (R$)" for="new-tx-amount" error="amount">
                    <input
                        id="new-tx-amount"
                        type="text"
                        inputmode="decimal"
                        wire:model="amount"
                        placeholder="0,00"
                        class="input font-display text-xl"
                        @error('amount') aria-invalid="true" aria-describedby="new-tx-amount-error" @enderror
                    >
                </x-ui.field>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Categoria" for="new-tx-category" error="categoryId">
                        <select id="new-tx-category" wire:model="categoryId" class="input px-2.5">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="{{ $type === 'income' ? 'Entra em' : 'Pago com' }}" for="new-tx-source" error="source">
                        <select id="new-tx-source" wire:model="source" class="input px-2.5">
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
                        </select>
                    </x-ui.field>
                </div>

                <x-ui.field label="Data" for="new-tx-date" error="date">
                    <input id="new-tx-date" type="date" wire:model="date" class="input">
                </x-ui.field>

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
</div>

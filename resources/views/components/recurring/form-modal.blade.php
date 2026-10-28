@props(['editing' => false, 'type', 'categories', 'accounts', 'cards', 'endsOn' => ''])

<x-ui.modal name="recurring-form" :title="$editing ? 'Editar recorrência' : 'Nova recorrência'">
    @if ($accounts->isEmpty() && $cards->isEmpty())
        <p class="text-body">Cadastre uma conta antes de criar recorrências.</p>
    @else
        <form wire:submit="save" class="flex flex-col gap-4">
            <x-ui.segmented label="Tipo" model="form.type" :options="['expense' => 'Despesa', 'income' => 'Receita']" live />

            <x-ui.field label="Descrição" for="rec-description" error="form.description">
                <input
                    id="rec-description"
                    type="text"
                    wire:model="form.description"
                    placeholder="Ex.: Aluguel"
                    maxlength="120"
                    class="input"
                    @error('form.description') aria-invalid="true" aria-describedby="rec-description-error" @enderror
                >
            </x-ui.field>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.field label="Valor (R$)" for="rec-amount" error="form.amount">
                    <input
                        id="rec-amount"
                        type="text"
                        inputmode="decimal"
                        wire:model="form.amount"
                        placeholder="0,00"
                        class="input font-display text-lg"
                        @error('form.amount') aria-invalid="true" aria-describedby="rec-amount-error" @enderror
                    >
                </x-ui.field>

                <x-ui.field label="Todo dia" for="rec-day" error="form.day">
                    <input
                        id="rec-day"
                        type="number"
                        min="1"
                        max="31"
                        inputmode="numeric"
                        wire:model="form.day"
                        class="input"
                        @error('form.day') aria-invalid="true" aria-describedby="rec-day-error" @enderror
                    >
                </x-ui.field>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.field label="Categoria" for="rec-category" error="form.categoryId">
                    <x-ui.select id="rec-category" wire:model="form.categoryId" class="input px-2.5">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field :label="$type === 'income' ? 'Entra em' : 'Pago com'" for="rec-source" error="form.source">
                    <x-ui.select id="rec-source" wire:model="form.source" class="input px-2.5">
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
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.field label="Começa em" for="rec-starts" error="form.startsOn">
                    <x-ui.date-picker id="rec-starts" wire:model="form.startsOn" />
                </x-ui.field>

                <x-ui.field label="Termina em (opcional)" for="rec-ends" error="form.endsOn">
                    <x-ui.date-picker id="rec-ends" wire:model="form.endsOn" />
                    @if ($endsOn !== '')
                        <button type="button" wire:click="clearEnd" class="self-start text-[13px] font-semibold text-brand-fg hover:text-brand-dark-fg">
                            Sem data final
                        </button>
                    @endif
                </x-ui.field>
            </div>

            <p class="-mt-2 text-[13px] text-muted">
                O app lança o valor no dia escolhido, todo mês. No dia 29, 30 ou 31, meses mais curtos usam o último dia.
            </p>

            <button
                type="submit"
                class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                {{ $editing ? 'Salvar alterações' : 'Criar recorrência' }}
            </button>

            @if ($editing)
                <button
                    type="button"
                    wire:click="delete"
                    wire:confirm="Excluir esta recorrência? Os lançamentos que ela já gerou continuam."
                    class="min-h-11 rounded-xl text-sm font-semibold text-expense-dark-fg hover:bg-expense-light"
                >
                    Excluir recorrência
                </button>
            @endif
        </form>
    @endif
</x-ui.modal>

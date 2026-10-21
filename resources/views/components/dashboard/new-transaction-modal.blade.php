@use('App\Support\SampleData')

{{--
    Modal de nova transação — por enquanto só front-end (Alpine).
    Abre com: $dispatch('open-modal', { name: 'new-transaction' })
    O envio será ligado a um componente Livewire quando o back-end existir.
--}}
<x-ui.modal name="new-transaction" title="Nova transação">
    <form
        class="flex flex-col gap-4"
        x-data="{ kind: 'expense' }"
        x-on:submit.prevent="$dispatch('close-modal', { name: 'new-transaction' })"
    >
        <div role="group" aria-label="Tipo de transação" class="grid grid-cols-2 gap-1 rounded-xl bg-mist p-1">
            <button type="button" x-on:click="kind = 'expense'" x-bind:aria-pressed="kind === 'expense'"
                class="min-h-10 rounded-[9px] text-sm font-semibold transition-colors"
                x-bind:class="kind === 'expense' ? 'bg-white text-ink shadow-sm' : 'text-body'">Despesa</button>
            <button type="button" x-on:click="kind = 'income'" x-bind:aria-pressed="kind === 'income'"
                class="min-h-10 rounded-[9px] text-sm font-semibold transition-colors"
                x-bind:class="kind === 'income' ? 'bg-white text-ink shadow-sm' : 'text-body'">Receita</button>
        </div>

        <x-ui.field label="Descrição" for="modal-tx-description">
            <input id="modal-tx-description" type="text" placeholder="Ex.: Padaria" class="input">
        </x-ui.field>

        <x-ui.field label="Valor (R$)" for="modal-tx-amount">
            <input id="modal-tx-amount" type="text" inputmode="decimal" placeholder="0,00" class="input font-display text-xl">
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Categoria" for="modal-tx-category">
                <select id="modal-tx-category" class="input px-2.5">
                    @foreach (SampleData::CATEGORIES as $category)
                        <option>{{ $category }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Conta" for="modal-tx-account">
                <select id="modal-tx-account" class="input px-2.5">
                    @foreach (SampleData::ACCOUNTS as $account)
                        <option>{{ $account }}</option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>

        <x-ui.field label="Data" for="modal-tx-date">
            <input id="modal-tx-date" type="date" value="{{ SampleData::TODAY }}" class="input">
        </x-ui.field>

        <button type="submit" class="min-h-12 rounded-xl bg-brand font-semibold text-white hover:bg-brand-dark">
            Salvar transação
        </button>
    </form>
</x-ui.modal>

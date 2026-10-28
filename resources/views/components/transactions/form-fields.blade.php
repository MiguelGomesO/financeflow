{{--
    Campos do formulário de transação (Livewire Form em "form").
    $prefix evita ids repetidos quando há mais de um formulário na página.
--}}
@props(['categories', 'accounts', 'cards', 'type', 'prefix', 'installments' => false])

<x-ui.segmented label="Tipo de transação" model="form.type" :options="['expense' => 'Despesa', 'income' => 'Receita']" live />

<x-ui.field label="Descrição" for="{{ $prefix }}-description" error="form.description">
    <input
        id="{{ $prefix }}-description"
        type="text"
        wire:model="form.description"
        placeholder="Ex.: Padaria"
        maxlength="120"
        class="input"
        @error('form.description') aria-invalid="true" aria-describedby="{{ $prefix }}-description-error" @enderror
    >
</x-ui.field>

<x-ui.field label="Valor (R$)" for="{{ $prefix }}-amount" error="form.amount">
    <input
        id="{{ $prefix }}-amount"
        type="text"
        inputmode="decimal"
        wire:model="form.amount"
        placeholder="0,00"
        class="input font-display text-xl"
        @error('form.amount') aria-invalid="true" aria-describedby="{{ $prefix }}-amount-error" @enderror
    >
</x-ui.field>

<div class="grid grid-cols-2 gap-3">
    <x-ui.field label="Categoria" for="{{ $prefix }}-category" error="form.categoryId">
        <x-ui.select id="{{ $prefix }}-category" wire:model="form.categoryId" class="input px-2.5">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field :label="$type === 'income' ? 'Entra em' : 'Pago com'" for="{{ $prefix }}-source" error="form.source">
        <x-ui.select id="{{ $prefix }}-source" wire:model="form.source" class="input px-2.5">
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

@if ($installments)
    {{-- Só despesa no cartão parcela. $wire lê o valor já escolhido, sem esperar o servidor. --}}
    <div x-data x-show="$wire.form.type === 'expense' && $wire.form.source.startsWith('card:')" x-cloak>
        <x-ui.field label="Parcelas" for="{{ $prefix }}-installments" error="form.installments">
            <x-ui.select id="{{ $prefix }}-installments" wire:model="form.installments" class="input px-2.5">
                <option value="1">À vista</option>
                @foreach (range(2, \App\Support\Installments::MAX) as $n)
                    <option value="{{ $n }}">{{ $n }}x</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <p x-show="Number($wire.form.installments) > 1" x-cloak class="mt-2 text-[13px] text-muted">
            O valor acima é o total da compra. Ele é dividido em partes iguais, uma em cada fatura, a partir da data escolhida.
        </p>
    </div>
@endif

<x-ui.field label="Data" for="{{ $prefix }}-date" error="form.date">
    <x-ui.date-picker id="{{ $prefix }}-date" wire:model="form.date" />
</x-ui.field>

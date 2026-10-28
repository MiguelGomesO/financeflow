@use('App\Support\Money')
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Transações · {{ $monthLabel }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c2321; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .sub { color: #6b7570; margin: 0 0 14px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 14px; }
        .summary td { border: 1px solid #dfe5e2; border-radius: 6px; padding: 8px 10px; }
        .summary .label { color: #6b7570; font-size: 9px; }
        .summary .value { font-size: 13px; font-weight: bold; margin-top: 2px; }
        table.rows { width: 100%; border-collapse: collapse; }
        table.rows th { text-align: left; color: #6b7570; font-weight: normal; padding: 6px 4px; border-bottom: 1px solid #1c2321; }
        table.rows td { padding: 5px 4px; border-bottom: 1px solid #e9eeec; }
        .right { text-align: right; white-space: nowrap; }
        .income { color: #0f7a4f; }
        .empty { text-align: center; color: #6b7570; padding: 24px 0; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>Transações · {{ $monthLabel }}</h1>
    <p class="sub">
        {{ $filters ?: 'Sem filtros' }} · gerado em {{ now()->format('d/m/Y H:i') }}
    </p>

    <table class="summary">
        <tr>
            <td><div class="label">Entradas</div><div class="value income">{{ Money::brl($income) }}</div></td>
            <td><div class="label">Saídas</div><div class="value">{{ Money::brl($expense) }}</div></td>
            <td><div class="label">Resultado</div><div class="value">{{ Money::brl($income - $expense) }}</div></td>
        </tr>
    </table>

    <table class="rows">
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Conta/Cartão</th>
                <th class="right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->date->format('d/m/Y') }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td>{{ $transaction->category->name }}</td>
                    <td>{{ $transaction->source_name }}</td>
                    <td class="right {{ $transaction->amount > 0 ? 'income' : '' }}">{{ Money::brl($transaction->amount, signed: true) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Nenhuma transação encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Dados de exemplo usados pelo front-end enquanto o back-end não existe.
 * Centralizados aqui para que todas as telas mostrem os mesmos números.
 */
class SampleData
{
    /** "Hoje" fixo dos dados de exemplo, para as telas ficarem coerentes entre si. */
    public const TODAY = '2026-10-21';

    public const CATEGORIES = ['Mercado', 'Moradia', 'Transporte', 'Lazer', 'Saúde', 'Assinaturas', 'Renda'];

    /** Categorias de entrada não recebem orçamento. */
    public const INCOME_CATEGORIES = ['Renda'];

    public const ACCOUNTS = ['Conta corrente', 'Poupança', 'Carteira', 'Cartão de crédito'];

    public static function today(): Carbon
    {
        return Carbon::parse(self::TODAY);
    }

    public static function currentMonth(): string
    {
        return self::today()->format('Y-m');
    }

    public static function accounts(): array
    {
        return [
            ['id' => 1, 'name' => 'Conta corrente', 'type' => 'Conta corrente', 'institution' => 'Banco principal', 'balance' => 4180.55, 'note' => 'Recebe o salário'],
            ['id' => 2, 'name' => 'Poupança', 'type' => 'Poupança', 'institution' => 'Banco principal', 'balance' => 8420.00, 'note' => 'Reserva de emergência'],
            ['id' => 3, 'name' => 'Carteira', 'type' => 'Carteira', 'institution' => 'Dinheiro em espécie', 'balance' => 240.00, 'note' => 'Atualizado em 19/10'],
        ];
    }

    /** Cartões de crédito; "bill" é a fatura atual em aberto. */
    public static function cards(): array
    {
        return [
            ['id' => 1, 'name' => 'Cartão principal', 'last4' => '4821', 'bill' => 1386.40, 'limit' => 5000, 'close_day' => 3, 'due_day' => 10],
            ['id' => 2, 'name' => 'Cartão reserva', 'last4' => '0937', 'bill' => 512.90, 'limit' => 2500, 'close_day' => 15, 'due_day' => 22],
        ];
    }

    /** Orçamentos por mês ("Y-m"), do mais recente para o mais antigo. */
    public static function budgets(): array
    {
        $limits = ['Mercado' => 1400, 'Moradia' => 2000, 'Transporte' => 600, 'Lazer' => 450, 'Saúde' => 400, 'Assinaturas' => 120];

        $spent = [
            '2026-10' => ['Mercado' => 1120, 'Moradia' => 1972, 'Transporte' => 410, 'Lazer' => 520, 'Saúde' => 180, 'Assinaturas' => 95],
            '2026-09' => ['Mercado' => 1350, 'Moradia' => 1985, 'Transporte' => 640, 'Lazer' => 380, 'Saúde' => 220, 'Assinaturas' => 95],
            '2026-08' => ['Mercado' => 1290, 'Moradia' => 1960, 'Transporte' => 520, 'Lazer' => 610, 'Saúde' => 90, 'Assinaturas' => 95],
        ];

        return array_map(
            fn (array $month) => array_map(
                fn (string $name) => ['name' => $name, 'spent' => $month[$name], 'limit' => $limits[$name]],
                array_keys($month),
            ),
            $spent,
        );
    }

    /** Metas de economia; "due" é o mês-limite ("Y-m"). */
    public static function goals(): array
    {
        return [
            ['id' => 1, 'name' => 'Reserva de emergência', 'saved' => 9000, 'target' => 15000, 'due' => '2027-06'],
            ['id' => 2, 'name' => 'Viagem de férias', 'saved' => 2400, 'target' => 6000, 'due' => '2027-12'],
            ['id' => 3, 'name' => 'Notebook novo', 'saved' => 3600, 'target' => 4500, 'due' => '2027-01'],
            ['id' => 4, 'name' => 'Entrada do apartamento', 'saved' => 12000, 'target' => 60000, 'due' => '2030-12'],
        ];
    }

    /** Depósitos nas metas, mais recentes primeiro. */
    public static function goalDeposits(): array
    {
        return [
            ['id' => 4, 'goal_id' => 1, 'date' => '2026-10-20', 'account' => 'Conta corrente', 'amount' => 750],
            ['id' => 3, 'goal_id' => 3, 'date' => '2026-10-15', 'account' => 'Conta corrente', 'amount' => 450],
            ['id' => 2, 'goal_id' => 4, 'date' => '2026-10-10', 'account' => 'Conta corrente', 'amount' => 900],
            ['id' => 1, 'goal_id' => 2, 'date' => '2026-10-05', 'account' => 'Poupança', 'amount' => 300],
        ];
    }
}

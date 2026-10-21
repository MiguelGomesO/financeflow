<?php

namespace App\Support;

/**
 * Dados de exemplo usados pelo front-end enquanto o back-end não existe.
 * Centralizados aqui para que todas as telas mostrem os mesmos números.
 */
class SampleData
{
    public const CATEGORIES = ['Mercado', 'Moradia', 'Transporte', 'Lazer', 'Saúde', 'Renda'];

    public const ACCOUNTS = ['Conta corrente', 'Poupança', 'Carteira', 'Cartão de crédito'];

    public static function accounts(): array
    {
        return [
            ['name' => 'Conta corrente', 'balance' => 4180.55],
            ['name' => 'Poupança', 'balance' => 8420.00],
            ['name' => 'Carteira', 'balance' => 240.00],
        ];
    }

    /** Transações mais recentes primeiro. */
    public static function transactions(): array
    {
        return [
            ['id' => 10, 'description' => 'Padaria', 'category' => 'Mercado', 'account' => 'Cartão de crédito', 'date' => '2026-10-21', 'amount' => -24.50],
            ['id' => 9, 'description' => 'Cinema', 'category' => 'Lazer', 'account' => 'Cartão de crédito', 'date' => '2026-10-19', 'amount' => -64.00],
            ['id' => 8, 'description' => 'Supermercado', 'category' => 'Mercado', 'account' => 'Cartão de crédito', 'date' => '2026-10-18', 'amount' => -286.40],
            ['id' => 7, 'description' => 'Combustível', 'category' => 'Transporte', 'account' => 'Conta corrente', 'date' => '2026-10-16', 'amount' => -210.00],
            ['id' => 6, 'description' => 'Freelance', 'category' => 'Renda', 'account' => 'Conta corrente', 'date' => '2026-10-14', 'amount' => 750.00],
            ['id' => 5, 'description' => 'Farmácia', 'category' => 'Saúde', 'account' => 'Carteira', 'date' => '2026-10-12', 'amount' => -58.90],
            ['id' => 4, 'description' => 'Aluguel', 'category' => 'Moradia', 'account' => 'Conta corrente', 'date' => '2026-10-10', 'amount' => -1800.00],
            ['id' => 3, 'description' => 'Conta de luz', 'category' => 'Moradia', 'account' => 'Conta corrente', 'date' => '2026-10-08', 'amount' => -172.30],
            ['id' => 2, 'description' => 'Restaurante', 'category' => 'Lazer', 'account' => 'Cartão de crédito', 'date' => '2026-10-06', 'amount' => -96.00],
            ['id' => 1, 'description' => 'Salário', 'category' => 'Renda', 'account' => 'Conta corrente', 'date' => '2026-10-05', 'amount' => 7500.00],
        ];
    }
}

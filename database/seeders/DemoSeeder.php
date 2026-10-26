<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Dados de demonstração dos últimos 6 meses, relativos à data de hoje.
 * Uso: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@financeflow.test';

    public const PASSWORD = 'demo1234';

    public function run(): void
    {
        // Os dados nascem do usuário de demonstração (login: demo@financeflow.test / demo1234).
        $demo = User::firstOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Demo', 'password' => self::PASSWORD],
        );

        Auth::login($demo);

        $category = Category::pluck('id', 'name');

        $checking = Account::create(['name' => 'Conta corrente', 'type' => 'Conta corrente', 'institution' => 'Banco principal', 'note' => 'Recebe o salário', 'opening_balance' => 1500]);
        $savings = Account::create(['name' => 'Poupança', 'type' => 'Poupança', 'institution' => 'Banco principal', 'note' => 'Reserva de emergência', 'opening_balance' => 8420]);
        $wallet = Account::create(['name' => 'Carteira', 'type' => 'Carteira', 'institution' => 'Dinheiro em espécie', 'opening_balance' => 400]);

        $mainCard = CreditCard::create(['name' => 'Cartão principal', 'last4' => '4821', 'limit' => 5000, 'close_day' => 3, 'due_day' => 10]);
        $spareCard = CreditCard::create(['name' => 'Cartão reserva', 'last4' => '0937', 'limit' => 2500, 'close_day' => 15, 'due_day' => 22]);

        // [dia, descrição, categoria, origem, valor base]; valores variam um pouco por mês.
        $template = [
            [5, 'Salário', 'Renda', $checking, 7500, false],
            [2, 'Streaming', 'Assinaturas', $spareCard, -55.90, false],
            [2, 'Academia', 'Saúde', $spareCard, -99.00, false],
            [3, 'Supermercado', 'Mercado', $mainCard, -540.00, true],
            [6, 'Restaurante', 'Lazer', $mainCard, -96.00, true],
            [8, 'Conta de luz', 'Moradia', $checking, -172.30, true],
            [10, 'Aluguel', 'Moradia', $checking, -1800.00, false],
            [12, 'Farmácia', 'Saúde', $wallet, -58.90, true],
            [16, 'Combustível', 'Transporte', $checking, -210.00, true],
            [18, 'Supermercado', 'Mercado', $mainCard, -286.40, true],
            [19, 'Cinema', 'Lazer', $mainCard, -64.00, false],
            [21, 'Padaria', 'Mercado', $mainCard, -24.50, true],
            [24, 'Uber', 'Transporte', $mainCard, -38.70, true],
        ];

        $variation = [1.12, 1.25, 1.3, 1.05, 0.92, 1.0];
        $today = now()->startOfDay();

        foreach (range(5, 0) as $i => $monthsAgo) {
            $month = $today->copy()->startOfMonth()->subMonths($monthsAgo);

            $rows = $template;
            if ($monthsAgo % 2 === 0) {
                $rows[] = [14, 'Freelance', 'Renda', $checking, 750, false];
            }

            // Guarda parte do salário na poupança todo mês.
            $transferDate = $month->copy()->day(6);
            if ($transferDate->lessThanOrEqualTo($today)) {
                Transfer::create(['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 1500, 'date' => $transferDate->toDateString()]);
            }

            foreach ($rows as [$day, $description, $categoryName, $source, $amount, $varies]) {
                $date = $month->copy()->day(min($day, $month->daysInMonth));

                if ($date->greaterThan($today)) {
                    continue;
                }

                Transaction::create([
                    'description' => $description,
                    'amount' => round($varies ? $amount * $variation[$i] : $amount, 2),
                    'date' => $date->toDateString(),
                    'category_id' => $category[$categoryName],
                    'account_id' => $source instanceof Account ? $source->id : null,
                    'credit_card_id' => $source instanceof CreditCard ? $source->id : null,
                ]);
            }
        }

        $limits = ['Mercado' => 1400, 'Moradia' => 2000, 'Transporte' => 600, 'Lazer' => 450, 'Saúde' => 400, 'Assinaturas' => 120];

        foreach (range(0, 2) as $monthsAgo) {
            foreach ($limits as $name => $limit) {
                Budget::create([
                    'category_id' => $category[$name],
                    'month' => $today->copy()->startOfMonth()->subMonths($monthsAgo)->format('Y-m'),
                    'limit' => $limit,
                ]);
            }
        }

        // [nome, alvo, meses até o prazo, depósito inicial, depósito recente, conta]
        $goals = [
            ['Reserva de emergência', 15000, 8, 8250, 750, $savings],
            ['Viagem de férias', 6000, 14, 2100, 300, $savings],
            ['Notebook novo', 4500, 3, 3150, 450, $checking],
            ['Entrada do apartamento', 60000, 50, 11100, 900, $checking],
        ];

        foreach ($goals as [$name, $target, $months, $initial, $recent, $account]) {
            $goal = Goal::create(['name' => $name, 'target' => $target, 'due_month' => $today->copy()->addMonths($months)->format('Y-m')]);

            $goal->deposits()->createMany([
                ['account_id' => $account->id, 'amount' => $initial, 'date' => $today->copy()->subMonths(4)->toDateString()],
                ['account_id' => $account->id, 'amount' => $recent, 'date' => $today->copy()->subDays(rand(1, 15))->toDateString()],
            ]);
        }
    }
}

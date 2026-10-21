<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Conta corrente', 'Poupança', 'Carteira']).' '.fake()->unique()->numberBetween(1, 999),
            'type' => 'Conta corrente',
            'institution' => 'Banco',
            'note' => null,
            'opening_balance' => 0,
        ];
    }
}

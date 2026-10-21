<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->words(2, true),
            'amount' => -fake()->randomFloat(2, 5, 500),
            'date' => now()->toDateString(),
            'category_id' => Category::factory(),
            'account_id' => Account::factory(),
            'credit_card_id' => null,
        ];
    }
}

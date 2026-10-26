<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->words(2, true),
            'amount' => -fake()->randomFloat(2, 20, 800),
            'category_id' => Category::factory(),
            'account_id' => Account::factory(),
            'credit_card_id' => null,
            'day_of_month' => fake()->numberBetween(1, 28),
            'starts_on' => now()->toDateString(),
            'ends_on' => null,
            'active' => true,
            'generated_until' => null,
        ];
    }
}

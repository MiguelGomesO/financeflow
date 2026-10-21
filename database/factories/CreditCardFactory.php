<?php

namespace Database\Factories;

use App\Models\CreditCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCard>
 */
class CreditCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Cartão '.fake()->unique()->numberBetween(1, 999),
            'last4' => fake()->numerify('####'),
            'limit' => 5000,
            'close_day' => 3,
            'due_day' => 10,
        ];
    }
}

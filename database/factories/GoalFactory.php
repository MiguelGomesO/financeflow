<?php

namespace Database\Factories;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Meta '.fake()->unique()->numberBetween(1, 999),
            'target' => 5000,
            'due_month' => now()->addYear()->format('Y-m'),
        ];
    }
}

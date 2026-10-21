<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalDeposit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalDeposit>
 */
class GoalDepositFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'account_id' => null,
            'amount' => 100,
            'date' => now()->toDateString(),
        ];
    }
}

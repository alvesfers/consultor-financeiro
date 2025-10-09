<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalProgressEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalProgressEventFactory extends Factory
{
    protected $model = GoalProgressEvent::class;

    public function definition(): array
    {
        $sign = $this->faker->boolean(85) ? 1 : -1;

        return [
            'goal_id' => Goal::factory(),
            'date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'amount' => $sign * $this->faker->randomFloat(2, 50, 1500),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}

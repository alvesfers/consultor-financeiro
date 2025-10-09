<?php

namespace Database\Factories;

use App\Models\Nudge;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class NudgeFactory extends Factory
{
    protected $model = Nudge::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'channel' => $this->faker->randomElement(['in_app','email','whatsapp']),
            'sent_by' => $this->faker->randomElement(['auto','consultant']),
            'sent_at' => $this->faker->optional(0.8)->dateTimeBetween('-10 days', 'now'),
            'status'  => $this->faker->randomElement(['queued','sent','failed']),
        ];
    }
}

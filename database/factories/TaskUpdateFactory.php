<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskUpdateFactory extends Factory
{
    protected $model = TaskUpdate::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'updated_by' => User::factory()->client(),
            'status_new' => $this->faker->optional(0.5)->randomElement(['open', 'done', 'skipped', 'blocked']),
            'progress_percent' => $this->faker->optional(0.3)->numberBetween(0, 100),
            'comment' => $this->faker->optional()->sentence(),
            'evidence_file_path' => null,
        ];
    }
}

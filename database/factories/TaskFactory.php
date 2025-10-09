<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $start = $this->faker->optional(0.7)->dateTimeBetween('-15 days', '+10 days');
        $due = $this->faker->optional(0.9)->dateTimeBetween($start ?? 'now', '+30 days');

        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory()->consultant(),
            'assigned_to' => User::factory()->client(),
            'title' => $this->faker->randomElement([
                'Enviar extratos do mês', 'Pagar fatura do cartão', 'Revisar orçamento',
                'Ajustar categorias das compras', 'Aportar no objetivo principal',
            ]),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement([Task::TYPE_BINARY, Task::TYPE_CHECKLIST, Task::TYPE_PROGRESS]),
            'frequency' => $this->faker->randomElement([Task::FREQ_ONCE, Task::FREQ_MONTHLY, Task::FREQ_WEEKLY]),
            'custom_rrule' => null,
            'start_at' => $start,
            'due_at' => $due,
            'remind_before_minutes' => $this->faker->optional(0.5)->randomElement([30, 60, 120, 24 * 60]),
            'status' => Task::STATUS_OPEN,
            'visibility' => Task::VIS_CLIENT_AND_CONSULTANT,
            'evidence_required' => $this->faker->boolean(25),
            'related_goal_id' => $this->faker->boolean(40) ? Goal::factory() : null,
            'related_entity' => null,
        ];
    }

    public function done(): self
    {
        return $this->state(fn () => ['status' => Task::STATUS_DONE]);
    }

    public function blocked(): self
    {
        return $this->state(fn () => ['status' => Task::STATUS_BLOCKED]);
    }

    public function checklist(): self
    {
        return $this->state(fn () => ['type' => Task::TYPE_CHECKLIST]);
    }
}

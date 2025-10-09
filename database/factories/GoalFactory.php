<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'title' => $this->faker->randomElement(['Reserva de Emergência', 'Viagem', 'Carro', 'Aposentadoria', 'Curso']).' '.$this->faker->city(),
            'target_amount' => $this->faker->randomFloat(2, 500, 50000),
            'due_date' => $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+18 months'),
            'priority' => $this->faker->numberBetween(1, 5),
            'status' => Goal::STATUS_ATIVO,
            'created_by' => User::factory()->consultant(),
        ];
    }

    public function pausado(): self
    {
        return $this->state(fn () => ['status' => Goal::STATUS_PAUSADO]);
    }

    public function concluido(): self
    {
        return $this->state(fn () => ['status' => Goal::STATUS_CONCLUIDO]);
    }

    public function atrasado(): self
    {
        return $this->state(fn () => ['status' => Goal::STATUS_ATRASADO]);
    }
}

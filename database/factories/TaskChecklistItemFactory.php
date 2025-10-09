<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskChecklistItemFactory extends Factory
{
    protected $model = TaskChecklistItem::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory()->checklist(),
            'label' => $this->faker->randomElement([
                'Separar extratos', 'Baixar faturas', 'Exportar OFX', 'Enviar comprovantes', 'Validar categorias',
            ]),
            'done' => $this->faker->boolean(30),
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }
}

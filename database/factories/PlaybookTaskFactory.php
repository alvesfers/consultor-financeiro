<?php

namespace Database\Factories;

use App\Models\Playbook;
use App\Models\PlaybookTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaybookTaskFactory extends Factory
{
    protected $model = PlaybookTask::class;

    public function definition(): array
    {
        return [
            'playbook_id' => Playbook::factory(),
            'title' => $this->faker->randomElement([
                'Coletar dados bancários', 'Criar contas iniciais', 'Definir orçamento base',
                'Configurar metas', 'Apresentação de relatórios',
            ]),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement(['binary', 'checklist', 'progress']),
            'frequency' => 'once',
            'custom_rrule' => null,
            'offset_days_from_start' => $this->faker->numberBetween(0, 14),
            'default_due_hour' => $this->faker->optional(0.6)->numberBetween(8, 20),
        ];
    }
}

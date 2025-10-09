<?php

namespace Database\Factories;

use App\Models\Consultant;
use App\Models\Playbook;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaybookFactory extends Factory
{
    protected $model = Playbook::class;

    public function definition(): array
    {
        return [
            'consultant_id' => Consultant::factory(),
            'title' => 'Onboarding '.$this->faker->colorName(),
            'description' => $this->faker->optional()->paragraph(),
        ];
    }
}

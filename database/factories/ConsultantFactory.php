<?php

namespace Database\Factories;

use App\Models\Consultant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultantFactory extends Factory
{
    protected $model = Consultant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->consultant(),
            'firm_name' => $this->faker->company().' Consultoria',
        ];
    }
}

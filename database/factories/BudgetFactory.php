<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $month = $this->faker->dateTimeBetween('-2 months', '+2 months')->format('Y-m');

        return [
            'client_id' => Client::factory(),
            'month' => $month,
            'category_id' => Category::factory(),
            'planned_amount' => $this->faker->randomFloat(2, 50, 5000),
        ];
    }
}

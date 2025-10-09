<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionSplitFactory extends Factory
{
    protected $model = TransactionSplit::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 5, 500);
        $amount *= $this->faker->boolean(60) ? -1 : 1;

        return [
            'transaction_id' => Transaction::factory(),
            'category_id' => Category::factory(),
            'subcategory_id' => null,
            'amount' => $amount,
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}

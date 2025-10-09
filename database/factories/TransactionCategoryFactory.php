<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'category_id' => Category::factory(),
            'subcategory_id' => null,
        ];
    }
}

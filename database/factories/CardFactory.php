<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Card;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        $brands = ['Visa', 'Mastercard', 'Elo', 'Hipercard', 'Amex', 'Nubank', 'Itaúcard'];
        $close = $this->faker->numberBetween(1, 28);
        $due = min(28, $close + $this->faker->numberBetween(7, 12));

        return [
            'client_id' => Client::factory(),
            'name' => 'Cartão '.$this->faker->colorName(),
            'brand' => $this->faker->randomElement($brands),
            'limit_amount' => $this->faker->randomFloat(2, 500, 20000),
            'close_day' => $close,
            'due_day' => $due,
            'payment_account_id' => Account::factory()->checking(),
        ];
    }
}

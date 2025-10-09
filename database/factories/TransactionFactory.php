<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Card;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $sign = $this->faker->boolean(40) ? -1 : 1; // mais despesas que receitas
        $value = $sign * $this->faker->randomFloat(2, 10, 1500);

        $isCard = $this->faker->boolean(35);

        return [
            'client_id' => Client::factory(),
            'account_id' => $isCard ? null : Account::factory()->checking(),
            'card_id' => $isCard ? Card::factory() : null,
            'date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'amount' => $value,
            'status' => $this->faker->randomElement([
                Transaction::STATUS_CONFIRMED,
                Transaction::STATUS_PENDING,
                Transaction::STATUS_RECONCILED,
            ]),
            'method' => $this->faker->randomElement(['pix', 'debito', 'credito', 'boleto', 'transfer']),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function income(): self
    {
        return $this->state(fn (array $a) => ['amount' => abs($a['amount'] ?? $this->faker->randomFloat(2, 50, 3000))]);
    }

    public function expense(): self
    {
        return $this->state(fn (array $a) => ['amount' => -abs($a['amount'] ?? $this->faker->randomFloat(2, 10, 1500))]);
    }

    public function pending(): self
    {
        return $this->state(fn () => ['status' => Transaction::STATUS_PENDING]);
    }

    public function confirmed(): self
    {
        return $this->state(fn () => ['status' => Transaction::STATUS_CONFIRMED]);
    }

    public function reconciled(): self
    {
        return $this->state(fn () => ['status' => Transaction::STATUS_RECONCILED]);
    }
}

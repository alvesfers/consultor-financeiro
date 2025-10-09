<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $types = [
            Account::TYPE_CHECKING,
            Account::TYPE_WALLET,
            Account::TYPE_INVESTMENT,
            Account::TYPE_LOAN,
        ];

        return [
            'client_id' => Client::factory(),
            'name' => $this->faker->randomElement(['Conta Corrente', 'Carteira', 'Poupança', 'Investimentos']).' '.$this->faker->bankAccountNumber(),
            'type' => $this->faker->randomElement($types),
            'on_budget' => $this->faker->boolean(85),
            'opening_balance' => $this->faker->randomFloat(2, -1000, 10000),
            'currency' => 'BRL',
        ];
    }

    public function checking(): self
    {
        return $this->state(fn () => ['type' => Account::TYPE_CHECKING]);
    }

    public function wallet(): self
    {
        return $this->state(fn () => ['type' => Account::TYPE_WALLET]);
    }

    public function investment(): self
    {
        return $this->state(fn () => ['type' => Account::TYPE_INVESTMENT]);
    }

    public function loan(): self
    {
        return $this->state(fn () => ['type' => Account::TYPE_LOAN, 'on_budget' => false]);
    }
}

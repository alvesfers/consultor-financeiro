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
        // 40% das transações serão despesas (valores negativos)
        $sign = $this->faker->boolean(40) ? -1 : 1;
        $value = $sign * $this->faker->randomFloat(2, 10, 1500);

        // 35% das transações estarão associadas a um cartão
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
            'method' => $this->faker->randomElement([
                'pix', 'debito', 'credito', 'boleto', 'transfer',
            ]),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'invoice_month' => now()->format('Y-m'),
            'installment_count' => 1,
            'installment_index' => 1,
            'parent_transaction_id' => null,
            'invoice_paid' => $this->faker->boolean(80),
        ];
    }

    // --- Estados auxiliares para uso em testes e seeds ---

    /** Receita (valor positivo) */
    public function income(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => abs($attributes['amount'] ?? $this->faker->randomFloat(2, 50, 3000)),
            ];
        });
    }

    /** Despesa (valor negativo) */
    public function expense(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => -abs($attributes['amount'] ?? $this->faker->randomFloat(2, 10, 1500)),
            ];
        });
    }

    /** Status: pendente */
    public function pending(): self
    {
        return $this->state(fn () => ['status' => Transaction::STATUS_PENDING]);
    }

    /** Status: confirmado */
    public function confirmed(): self
    {
        return $this->state(fn () => ['status' => Transaction::STATUS_CONFIRMED]);
    }

    /** Status: conciliado */
    public function reconciled(): self
    {
        return $this->state(fn () => ['status' => Transaction::STATUS_RECONCILED]);
    }
}

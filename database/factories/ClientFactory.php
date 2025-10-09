<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->client(),
            'consultant_id' => Consultant::factory(),
            'status' => Client::STATUS_ATIVO,
        ];
    }

    public function pausado(): self
    {
        return $this->state(fn () => ['status' => Client::STATUS_PAUSADO]);
    }

    public function encerrado(): self
    {
        return $this->state(fn () => ['status' => Client::STATUS_ENCERRADO]);
    }
}

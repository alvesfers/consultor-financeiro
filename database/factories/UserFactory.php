<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // altere em prod
            'remember_token' => Str::random(10),
            'role' => User::ROLE_CLIENT,
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'active' => true,
        ];
    }

    public function admin(): self
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    public function consultant(): self
    {
        return $this->state(fn () => ['role' => User::ROLE_CONSULTANT]);
    }

    public function client(): self
    {
        return $this->state(fn () => ['role' => User::ROLE_CLIENT]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['active' => false]);
    }
}

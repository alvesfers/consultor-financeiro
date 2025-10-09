<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        // O owner polimórfico será atribuído pelo seeder normalmente.
        return [
            'owner_type' => null,
            'owner_id' => null,
            'path' => 'uploads/'.$this->faker->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size' => $this->faker->numberBetween(10_000, 1_500_000),
        ];
    }
}

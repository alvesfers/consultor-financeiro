<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /** Lista base de nomes “raiz” */
    private array $rootNames = [
        'Habitação', 'Transporte', 'Alimentação', 'Saúde', 'Educação',
        'Lazer', 'Roupas', 'Impostos', 'Investimentos', 'Outros',
    ];

    /** Lista base de nomes “filhos” */
    private array $childNames = [
        'Água', 'Luz', 'Mercado', 'Aluguel', 'Condomínio', 'Internet',
        'Farmácia', 'Restaurante', 'Gasolina', 'Streaming',
    ];

    public function definition(): array
    {
        // Sem unique(): se colidir, a gente anexa um sufixo aleatório pequeno.
        $name = $this->faker->randomElement($this->rootNames);

        return [
            'parent_id' => null,
            'name' => $name,
            'is_active' => true,
        ];
    }

    /**
     * Cria subcategoria. Se não for passado um parent, cria um automaticamente.
     */
    public function child(?Category $parent = null): self
    {
        return $this->state(function () use ($parent) {
            $base = $this->faker->randomElement($this->childNames);

            return [
                'parent_id' => $parent?->id ?? Category::factory(),
                'name' => $base,
            ];
        });
    }

    /** Opcional: gera um nome garantidamente único anexando um sufixo curto */
    public function withUniqueSuffix(): self
    {
        return $this->state(function (array $attributes) {
            $suffix = '-'.Str::lower(Str::random(4));

            return ['name' => ($attributes['name'] ?? $this->faker->word()).$suffix];
        });
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

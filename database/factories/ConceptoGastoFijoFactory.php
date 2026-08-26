<?php

namespace Database\Factories;

use App\Models\ConceptoGastoFijo;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConceptoGastoFijoFactory extends Factory
{
    protected $model = ConceptoGastoFijo::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'clave' => null,
            'orden' => fake()->numberBetween(1, 100),
            'activo' => true,
        ];
    }
}

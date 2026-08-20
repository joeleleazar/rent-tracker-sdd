<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Local ' . fake()->unique()->numerify('##'),
            'tamano' => fake()->randomFloat(2, 10, 500),
            'ubicacion_fisica' => fake()->address(),
            'descripcion' => fake()->sentence(),
            'locacion_padre_id' => null,
            'es_alquilable' => true,
        ];
    }
}

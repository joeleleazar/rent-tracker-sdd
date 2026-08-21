<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RepresentanteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'apellidos' => fake()->lastName(),
            'nombres' => fake()->firstName(),
            'dni' => fake()->unique()->numerify('########'),
            'fecha_nacimiento' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
        ];
    }
}

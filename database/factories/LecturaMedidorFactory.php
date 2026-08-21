<?php

namespace Database\Factories;

use App\Models\Locacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class LecturaMedidorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'locacion_id' => Locacion::factory(),
            'periodo' => now()->startOfMonth()->format('Y-m-d'),
            'lectura_anterior' => null,
            'lectura_actual' => fake()->randomFloat(2, 100, 5000),
            'consumo_calculado' => null,
            'fecha_registro' => now(),
        ];
    }
}

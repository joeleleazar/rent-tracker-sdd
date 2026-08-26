<?php

namespace Database\Factories;

use App\Models\Contrato;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReciboFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contrato_id' => Contrato::factory(),
            'locacion_id' => fn (array $atributos) => Contrato::find($atributos['contrato_id'])?->locacion_id
                ?? \App\Models\Locacion::factory(),
            'monto_renta' => fake()->randomFloat(2, 500, 3000),
            'periodo' => now()->startOfMonth()->format('Y-m-d'),
            'fecha_emision' => now()->format('Y-m-d'),
        ];
    }
}

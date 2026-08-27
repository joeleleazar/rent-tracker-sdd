<?php

namespace Database\Factories;

use App\Models\Recibo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recibo_id' => Recibo::factory(),
            'monto' => fake()->randomFloat(2, 10, 500),
            'fecha_pago' => now()->format('Y-m-d'),
            'registrado_por_id' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Models\Locacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContratoFactory extends Factory
{
    public function definition(): array
    {
        $fechaInicio = fake()->dateTimeBetween('-1 year', '+1 year');
        $fechaFin = (clone $fechaInicio)->modify('+11 months');

        return [
            'locacion_id' => Locacion::factory(),
            'inquilino_id' => Inquilino::factory(),
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'monto_renta' => fake()->randomFloat(2, 100, 5000),
            'estado' => 'activo',
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConfiguracionGeneralFactory extends Factory
{
    protected $model = \App\Models\ConfiguracionGeneral::class;

    public function definition(): array
    {
        return [
            'correo_notificaciones_vencimiento' => fake()->unique()->safeEmail(),
        ];
    }
}

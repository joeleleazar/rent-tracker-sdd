<?php

namespace Database\Factories;

use App\Models\Contrato;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentoContratoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contrato_id' => Contrato::factory(),
            'nombre_archivo' => 'contrato.pdf',
            'ruta_archivo' => 'contratos/1/contrato.pdf',
            'tipo_archivo' => 'pdf',
            'secuencia' => 1,
        ];
    }
}

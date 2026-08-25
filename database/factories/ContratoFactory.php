<?php

namespace Database\Factories;

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ContratoFactory extends Factory
{
    public function definition(): array
    {
        $fechaInicio = fake()->dateTimeBetween('-1 year', '+1 year');
        $fechaFin = (clone $fechaInicio)->modify('+11 months');

        return [
            'locacion_id' => Locacion::factory(),
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'monto_renta' => fake()->randomFloat(2, 100, 5000),
            'estado' => 'activo',
        ];
    }

    /**
     * `inquilino_id` ya no es una columna de `contratos` (specs/003-representantes-contrato,
     * corrección 2026-08-23: el inquilino es el representante del contrato, vía la
     * tabla pivote `contrato_inquilino`). Se conserva `inquilino_id` como
     * pseudo-atributo aceptado por este factory únicamente para no reescribir
     * las decenas de pruebas de otras features (garantía, recibos, solapamiento,
     * historial) que ya construyen contratos con `Contrato::factory()->create(['inquilino_id' => ...])`:
     * aquí se extrae antes de llegar al modelo y se adjunta como el inquilino
     * Principal vía el pivote. Si no se especifica, se crea y adjunta un
     * inquilino nuevo por defecto, preservando la invariante de FR-003 (todo
     * contrato debe tener al menos un inquilino) también en las pruebas.
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        // No se delega en `parent::create()`: para atributos no vacíos, la
        // implementación base de Factory hace `$this->state($attributes)->create([], $parent)`,
        // lo que reingresa a ESTE MISMO override (misma instancia, mismo tipo)
        // una segunda vez y duplicaría el adjuntado de abajo. Se reimplementa
        // el camino de creación de un único modelo (ver Factory::create() en
        // el framework) usando sus primitivas protegidas directamente.
        $inquilinoId = $attributes['inquilino_id'] ?? null;
        unset($attributes['inquilino_id']);

        $contrato = $this->state($attributes)->make([], $parent);

        $this->store(new Collection([$contrato]));

        $contrato->inquilinos()->attach(
            $inquilinoId ?? Inquilino::factory()->create()->id,
            ['es_principal' => true],
        );

        $this->callAfterCreating(new Collection([$contrato]), $parent);

        return $contrato;
    }
}

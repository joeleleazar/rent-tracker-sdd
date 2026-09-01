<?php

namespace App\Services;

use App\Models\LecturaMedidor;
use App\Models\Locacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * specs/044 (US1): arma las filas de la plantilla de carga masiva de lecturas
 * de luz para un periodo — una fila por locación alquilable, en el orden del
 * árbol de locaciones, con la lectura del periodo anterior como referencia y
 * la lectura actual precargada si ya existe registro para ese periodo.
 *
 * No incluye la tarifa por kWh: sigue siendo el input global de la pantalla
 * (FR-015). El batch-fetch de lecturas se hace antes de recorrer el árbol,
 * mismo criterio anti-N+1 de specs/018.
 */
class ServicioPlantillaLecturas
{
    /** Encabezados de la hoja, en orden. La primera columna es técnica (FR-010). */
    public const ENCABEZADOS = ['periodo', 'local_id', 'Locación', 'Lectura Periodo Anterior', 'Lectura Actual'];

    public function __construct(
        private readonly ServicioConstruccionArbolLocaciones $servicioArbol,
    ) {}

    /**
     * @return array<int, array{periodo: string, local_id: int, Locación: string, 'Lectura Periodo Anterior': string|null, 'Lectura Actual': string|null}>
     */
    public function filas(Carbon $periodo): array
    {
        $idsAlquilables = Locacion::alquilables()->pluck('id');

        $lecturasDelPeriodo = LecturaMedidor::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->get()
            ->keyBy('locacion_id');

        $lecturasAnteriores = LecturaMedidor::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', '<', $periodo->format('Y-m-d'))
            ->orderByDesc('periodo')
            ->get()
            ->unique('locacion_id')
            ->keyBy('locacion_id');

        return $this->aplanar(
            $this->servicioArbol->construir(),
            $periodo,
            $lecturasDelPeriodo,
            $lecturasAnteriores,
        );
    }

    /**
     * @param  array<int, array{locacion: Locacion, hijos: array}>  $nodos
     * @return array<int, array<string, mixed>>
     */
    private function aplanar(array $nodos, Carbon $periodo, Collection $delPeriodo, Collection $anteriores, string $ruta = ''): array
    {
        $filas = [];

        foreach ($nodos as $nodo) {
            $locacion = $nodo['locacion'];
            $rutaActual = $ruta === '' ? $locacion->nombre : $ruta.' > '.$locacion->nombre;

            if ($locacion->es_alquilable) {
                $anterior = $anteriores->get($locacion->id)?->lectura_actual;
                $actual = $delPeriodo->get($locacion->id)?->lectura_actual;

                $filas[] = [
                    'periodo' => $periodo->format('Y-m'),
                    'local_id' => $locacion->id,
                    'Locación' => $rutaActual,
                    'Lectura Periodo Anterior' => $anterior !== null ? (string) $anterior : null,
                    'Lectura Actual' => $actual !== null ? (string) $actual : null,
                ];
            }

            if (! empty($nodo['hijos'])) {
                $filas = [...$filas, ...$this->aplanar($nodo['hijos'], $periodo, $delPeriodo, $anteriores, $rutaActual)];
            }
        }

        return $filas;
    }
}

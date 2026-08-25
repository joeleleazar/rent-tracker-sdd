<?php

namespace App\Services;

use App\Models\Locacion;
use Illuminate\Support\Collection;

/**
 * Construye el árbol jerárquico completo de locaciones (specs/013-arbol-jerarquico-locaciones)
 * a partir de una única consulta, agrupando en memoria por `locacion_padre_id`
 * para evitar N+1 al recorrer jerarquías de hasta 1,000 locaciones
 * (Asunción A-002 de specs/001-jerarquia-locaciones).
 */
class ServicioConstruccionArbolLocaciones
{
    /**
     * Límite de niveles al ensamblar el árbol, como red de seguridad ante
     * datos corruptos (mismo valor que `Locacion::MAXIMO_SALTOS_ANCESTROS`).
     */
    private const MAXIMO_PROFUNDIDAD_ARBOL = 1000;

    /**
     * @return array<int, array{locacion: Locacion, hijos: array<int, mixed>}>
     */
    public function construir(): array
    {
        $locacionesPorPadre = Locacion::orderBy('nombre')->get()->groupBy('locacion_padre_id');

        $raices = $locacionesPorPadre->get(null, new Collection());

        return $this->ensamblarNodos($raices, $locacionesPorPadre, 0);
    }

    /**
     * @param Collection<int, Locacion> $locaciones
     * @param Collection<int|null, Collection<int, Locacion>> $locacionesPorPadre
     * @return array<int, array{locacion: Locacion, hijos: array<int, mixed>}>
     */
    private function ensamblarNodos(Collection $locaciones, Collection $locacionesPorPadre, int $profundidad): array
    {
        if ($profundidad >= self::MAXIMO_PROFUNDIDAD_ARBOL) {
            return [];
        }

        return $locaciones
            ->map(fn (Locacion $locacion) => [
                'locacion' => $locacion,
                'hijos' => $this->ensamblarNodos(
                    $locacionesPorPadre->get($locacion->id, new Collection()),
                    $locacionesPorPadre,
                    $profundidad + 1,
                ),
            ])
            ->all();
    }
}

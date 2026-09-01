<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * specs/043: resuelve una "rama" de la jerarquía de locaciones — una locación
 * más todos sus descendientes — para el filtro del listado de morosos del panel
 * de inicio (FR-019). `locaciones` es una lista de adyacencia
 * (`locacion_padre_id`); se recorre con un CTE recursivo de PostgreSQL en una
 * sola consulta, sin límite de profundidad (research.md §3).
 */
class ServicioJerarquiaLocaciones
{
    /**
     * @return array<int, int>  el id dado y todos sus descendientes
     */
    public function idsDeRama(int $locacionId): array
    {
        $filas = DB::select(
            <<<'SQL'
            WITH RECURSIVE rama AS (
                SELECT id FROM locaciones WHERE id = ?
                UNION ALL
                SELECT hija.id
                FROM locaciones hija
                JOIN rama ON hija.locacion_padre_id = rama.id
            )
            SELECT id FROM rama
            SQL,
            [$locacionId],
        );

        return array_map(static fn (object $fila): int => (int) $fila->id, $filas);
    }
}

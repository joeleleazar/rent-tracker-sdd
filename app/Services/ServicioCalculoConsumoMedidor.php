<?php

namespace App\Services;

use App\Models\LecturaMedidor;
use App\Models\Locacion;

/**
 * specs/006-historial-lectura-medidor §2: refactorizado en dos métodos explícitos.
 * `sugerirLecturaAnterior()` es una consulta de solo lectura usada únicamente para
 * precargar el formulario de un nuevo periodo (soporta periodos salteados, ver
 * Edge Case "Registro de periodos fuera de orden"); `calcularConsumo()` es una
 * operación aritmética pura sobre datos ya en memoria, sin acceso a base de datos,
 * de modo que el consumo de un periodo ya guardado nunca cambia silenciosamente si
 * se edita o inserta una fila de otro periodo (FR-006).
 */
class ServicioCalculoConsumoMedidor
{
    /**
     * Busca, para la locación dada, la lectura_actual de la fila con el periodo
     * cronológicamente más reciente estrictamente anterior al periodo solicitado.
     * Devuelve null si no existe ningún periodo previo registrado (FR-002).
     */
    public function sugerirLecturaAnterior(Locacion $locacion, string $periodo, ?int $excluirLecturaId = null): ?float
    {
        $anterior = LecturaMedidor::where('locacion_id', $locacion->id)
            ->where('periodo', '<', $periodo)
            ->when($excluirLecturaId, fn ($query) => $query->where('id', '!=', $excluirLecturaId))
            ->orderByDesc('periodo')
            ->first();

        return $anterior === null ? null : (float) $anterior->lectura_actual;
    }

    /**
     * Diferencia entre lectura_actual y lectura_anterior (editada o no) del mismo
     * registro (FR-004). Devuelve null si no hay lectura_anterior disponible
     * ("sin dato anterior").
     */
    public function calcularConsumo(?float $lecturaAnterior, float $lecturaActual): ?float
    {
        if ($lecturaAnterior === null) {
            return null;
        }

        return round($lecturaActual - $lecturaAnterior, 2);
    }
}

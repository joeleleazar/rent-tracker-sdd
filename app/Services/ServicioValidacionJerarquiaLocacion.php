<?php

namespace App\Services;

use App\Exceptions\LocacionCicloException;
use App\Exceptions\LocacionConHijasException;
use App\Models\Locacion;
use Closure;
use Illuminate\Support\Facades\DB;

class ServicioValidacionJerarquiaLocacion
{
    /**
     * Verifica que $nuevoLocacionPadreId no sea la propia $locacion ni ninguno de
     * sus descendientes (lo que crearía un ciclo), y solo si no hay conflicto
     * ejecuta $accion dentro de una transacción para persistir el cambio (FR-003).
     */
    public function validarYEjecutar(?Locacion $locacion, ?int $nuevoLocacionPadreId, Closure $accion): mixed
    {
        return DB::transaction(function () use ($locacion, $nuevoLocacionPadreId, $accion) {
            if ($locacion !== null && $nuevoLocacionPadreId !== null) {
                $this->verificarSinCiclo($locacion, $nuevoLocacionPadreId);
            }

            return $accion();
        });
    }

    private function verificarSinCiclo(Locacion $locacion, int $nuevoLocacionPadreId): void
    {
        if ($nuevoLocacionPadreId === $locacion->id) {
            throw new LocacionCicloException();
        }

        $candidatoPadre = Locacion::find($nuevoLocacionPadreId);

        foreach ($candidatoPadre?->ancestros() ?? [] as $ancestro) {
            if ($ancestro->id === $locacion->id) {
                throw new LocacionCicloException();
            }
        }
    }

    /**
     * Bloquea la eliminación de una locación que tenga sub-locaciones asociadas
     * (FR-007, Edge Case "Locaciones Huérfanas por Eliminación").
     */
    public function eliminar(Locacion $locacion): void
    {
        DB::transaction(function () use ($locacion) {
            if ($locacion->locacionesHijas()->exists()) {
                throw new LocacionConHijasException($locacion);
            }

            $locacion->delete();
        });
    }
}

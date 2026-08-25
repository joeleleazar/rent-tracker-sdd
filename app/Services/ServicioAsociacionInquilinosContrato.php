<?php

namespace App\Services;

use App\Exceptions\ContratoSinInquilinosException;
use App\Exceptions\InquilinoPrincipalInvalidoException;
use App\Exceptions\InquilinoPrincipalSinReemplazoException;
use App\Exceptions\UltimoInquilinoException;
use App\Models\Contrato;
use App\Models\Inquilino;
use Illuminate\Support\Facades\DB;

class ServicioAsociacionInquilinosContrato
{
    /**
     * Reemplaza por completo el conjunto de inquilinos de un contrato (usado al
     * crear/editar el contrato completo). Exige al menos un inquilino y, si hay
     * más de uno, exactamente uno marcado como Principal (FR-003).
     *
     * @param array<int, array{inquilino_id?: int|null, apellidos?: string, nombres?: string, dni?: string, fecha_nacimiento?: string, es_principal?: bool}> $inquilinos
     */
    public function sincronizar(Contrato $contrato, array $inquilinos): void
    {
        DB::transaction(function () use ($contrato, $inquilinos) {
            if (count($inquilinos) === 0) {
                throw new ContratoSinInquilinosException();
            }

            if (count($inquilinos) > 1) {
                $totalPrincipales = collect($inquilinos)->filter(fn (array $datos) => (bool) ($datos['es_principal'] ?? false))->count();

                if ($totalPrincipales !== 1) {
                    throw new InquilinoPrincipalInvalidoException();
                }
            }

            $datosSincronizacion = [];

            foreach ($inquilinos as $datos) {
                $inquilino = $this->resolverInquilino($datos);
                $esPrincipal = count($inquilinos) === 1 ? true : (bool) ($datos['es_principal'] ?? false);

                $datosSincronizacion[$inquilino->id] = ['es_principal' => $esPrincipal];
            }

            $contrato->inquilinos()->sync($datosSincronizacion);
        });
    }

    /**
     * Asocia un inquilino (existente o nuevo) a un contrato ya persistido, sin
     * afectar a los demás inquilinos ya asociados (US2).
     *
     * @param array{inquilino_id?: int|null, apellidos?: string, nombres?: string, dni?: string, fecha_nacimiento?: string, es_principal?: bool} $datos
     */
    public function agregar(Contrato $contrato, array $datos): Inquilino
    {
        return DB::transaction(function () use ($contrato, $datos) {
            $inquilino = $this->resolverInquilino($datos);

            $esPrimero = $contrato->inquilinos()->count() === 0;
            $esPrincipal = $esPrimero || (bool) ($datos['es_principal'] ?? false);

            if ($esPrincipal) {
                $contrato->inquilinos()->updateExistingPivot(
                    $contrato->inquilinos()->pluck('inquilinos.id')->all(),
                    ['es_principal' => false],
                );
            }

            $contrato->inquilinos()->syncWithoutDetaching([
                $inquilino->id => ['es_principal' => $esPrincipal],
            ]);

            return $inquilino;
        });
    }

    /**
     * Quita un inquilino de un contrato, bloqueando la acción si es el único
     * asociado (FR-004, Edge Case "Eliminación del Último Inquilino") o si es
     * el Principal y no se designó simultáneamente un reemplazo entre los
     * inquilinos restantes (FR-009, Edge Case "Eliminación del Inquilino
     * Principal cuando hay otros").
     */
    public function quitar(Contrato $contrato, Inquilino $inquilino, ?int $nuevoPrincipalId = null): void
    {
        DB::transaction(function () use ($contrato, $inquilino, $nuevoPrincipalId) {
            $totalAsociados = $contrato->inquilinos()->count();

            if ($totalAsociados <= 1) {
                throw new UltimoInquilinoException();
            }

            $eraPrincipal = (bool) $contrato->inquilinos()->wherePivot('inquilino_id', $inquilino->id)->wherePivot('es_principal', true)->exists();

            if ($eraPrincipal) {
                $idsRestantes = $contrato->inquilinos()->where('inquilinos.id', '!=', $inquilino->id)->pluck('inquilinos.id');

                if ($nuevoPrincipalId === null || ! $idsRestantes->contains($nuevoPrincipalId)) {
                    throw new InquilinoPrincipalSinReemplazoException();
                }
            }

            $contrato->inquilinos()->detach($inquilino->id);

            if ($eraPrincipal) {
                $contrato->inquilinos()->updateExistingPivot($nuevoPrincipalId, ['es_principal' => true]);
            }
        });
    }

    /**
     * @param array{inquilino_id?: int|null, apellidos?: string, nombres?: string, dni?: string, fecha_nacimiento?: string} $datos
     */
    private function resolverInquilino(array $datos): Inquilino
    {
        if (! empty($datos['inquilino_id'])) {
            return Inquilino::findOrFail($datos['inquilino_id']);
        }

        return Inquilino::firstOrCreate(
            ['dni' => $datos['dni']],
            [
                'apellidos' => $datos['apellidos'],
                'nombres' => $datos['nombres'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
            ],
        );
    }
}

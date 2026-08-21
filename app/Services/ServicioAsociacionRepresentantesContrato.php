<?php

namespace App\Services;

use App\Exceptions\ContratoSinRepresentantesException;
use App\Exceptions\RepresentantePrincipalInvalidoException;
use App\Exceptions\UltimoRepresentanteException;
use App\Models\Contrato;
use App\Models\Representante;
use Illuminate\Support\Facades\DB;

class ServicioAsociacionRepresentantesContrato
{
    /**
     * Reemplaza por completo el conjunto de representantes de un contrato (usado al
     * crear/editar el contrato completo). Exige al menos un representante y, si hay
     * más de uno, exactamente uno marcado como Principal (FR-003).
     *
     * @param array<int, array{representante_id?: int|null, apellidos?: string, nombres?: string, dni?: string, fecha_nacimiento?: string, es_principal?: bool}> $representantes
     */
    public function sincronizar(Contrato $contrato, array $representantes): void
    {
        DB::transaction(function () use ($contrato, $representantes) {
            if (count($representantes) === 0) {
                throw new ContratoSinRepresentantesException();
            }

            if (count($representantes) > 1) {
                $totalPrincipales = collect($representantes)->filter(fn (array $datos) => (bool) ($datos['es_principal'] ?? false))->count();

                if ($totalPrincipales !== 1) {
                    throw new RepresentantePrincipalInvalidoException();
                }
            }

            $datosSincronizacion = [];

            foreach ($representantes as $datos) {
                $representante = $this->resolverRepresentante($datos);
                $esPrincipal = count($representantes) === 1 ? true : (bool) ($datos['es_principal'] ?? false);

                $datosSincronizacion[$representante->id] = ['es_principal' => $esPrincipal];
            }

            $contrato->representantes()->sync($datosSincronizacion);
        });
    }

    /**
     * Asocia un representante (existente o nuevo) a un contrato ya persistido, sin
     * afectar a los demás representantes ya asociados (US2).
     *
     * @param array{representante_id?: int|null, apellidos?: string, nombres?: string, dni?: string, fecha_nacimiento?: string, es_principal?: bool} $datos
     */
    public function agregar(Contrato $contrato, array $datos): Representante
    {
        return DB::transaction(function () use ($contrato, $datos) {
            $representante = $this->resolverRepresentante($datos);

            $esPrimero = $contrato->representantes()->count() === 0;
            $esPrincipal = $esPrimero || (bool) ($datos['es_principal'] ?? false);

            if ($esPrincipal) {
                $contrato->representantes()->updateExistingPivot(
                    $contrato->representantes()->pluck('representantes.id')->all(),
                    ['es_principal' => false],
                );
            }

            $contrato->representantes()->syncWithoutDetaching([
                $representante->id => ['es_principal' => $esPrincipal],
            ]);

            return $representante;
        });
    }

    /**
     * Quita un representante de un contrato, bloqueando la acción si es el único
     * asociado (FR-004, Edge Case "Eliminación del Último Representante").
     */
    public function quitar(Contrato $contrato, Representante $representante): void
    {
        DB::transaction(function () use ($contrato, $representante) {
            $totalAsociados = $contrato->representantes()->count();

            if ($totalAsociados <= 1) {
                throw new UltimoRepresentanteException();
            }

            $contrato->representantes()->detach($representante->id);

            $quedaPrincipal = $contrato->representantes()->wherePivot('es_principal', true)->exists();

            if (! $quedaPrincipal) {
                $primerRestante = $contrato->representantes()->first();

                if ($primerRestante !== null) {
                    $contrato->representantes()->updateExistingPivot($primerRestante->id, ['es_principal' => true]);
                }
            }
        });
    }

    /**
     * @param array{representante_id?: int|null, apellidos?: string, nombres?: string, dni?: string, fecha_nacimiento?: string} $datos
     */
    private function resolverRepresentante(array $datos): Representante
    {
        if (! empty($datos['representante_id'])) {
            return Representante::findOrFail($datos['representante_id']);
        }

        return Representante::firstOrCreate(
            ['dni' => $datos['dni']],
            [
                'apellidos' => $datos['apellidos'],
                'nombres' => $datos['nombres'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
            ],
        );
    }
}

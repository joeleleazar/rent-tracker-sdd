<?php

namespace App\Services;

use App\Imports\ImportacionLecturasImport;
use App\Models\ConfiguracionGeneral;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Support\Importacion\FilaImportada;
use App\Support\Importacion\ResultadoImportacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * specs/044 (US1): parseo + validación por fila + upsert de la carga masiva de
 * lecturas de luz por plantilla. La vista previa no se persiste (FR-013); el
 * upsert por `(locacion_id, periodo)` es idempotente (FR-008/FR-012) y atómico
 * (FR-011).
 */
class ServicioImportacionLecturas
{
    public function __construct(
        private readonly ServicioCalculoConsumoMedidor $servicioConsumo,
    ) {}

    /**
     * Parsea el archivo y valida cada fila SIN tocar la base de datos.
     *
     * @return array{ok: bool, motivoRechazo: string|null, filas: array<int, FilaImportada>}
     */
    public function previsualizar(UploadedFile $archivo, Carbon $periodo): array
    {
        $import = new ImportacionLecturasImport;
        Excel::import($import, $archivo);

        if ($import->filas->isEmpty()) {
            return $this->rechazo('El archivo no tiene filas de datos.');
        }

        if ($this->pareceOtraPlantilla($import->encabezados)) {
            return $this->rechazo('El archivo parece ser la plantilla de recibos, no la de lecturas.');
        }

        $faltantes = $import->columnasFaltantes();
        if (! empty($faltantes)) {
            return $this->rechazo(
                'El archivo no corresponde a la plantilla de lecturas: faltan las columnas '.
                implode(', ', $faltantes).'.'
            );
        }

        $periodoEsperado = $periodo->format('Y-m');
        $periodosArchivo = $import->filas
            ->map(fn ($fila) => $this->normalizarPeriodo((string) ($fila['periodo'] ?? '')))
            ->filter()
            ->unique();

        if ($periodosArchivo->isEmpty() || $periodosArchivo->contains(fn ($p) => $p !== $periodoEsperado)) {
            return $this->rechazo(
                "El archivo fue generado para otro periodo. La pantalla está en {$periodoEsperado}."
            );
        }

        $contexto = $this->contexto($import->filas, $periodo);
        $filas = $import->filas
            ->map(fn ($fila) => $this->validarFila($fila->toArray(), $periodo, $contexto))
            ->all();

        return ['ok' => true, 'motivoRechazo' => null, 'filas' => $filas];
    }

    /**
     * Guarda las filas válidas dentro de una única transacción. `$filasInput`
     * son los inputs de la tabla de vista previa (no el archivo).
     *
     * @param  array<int, array{local_id: mixed, lectura_actual: mixed, total?: mixed}>  $filasInput
     */
    public function confirmar(array $filasInput, Carbon $periodo): ResultadoImportacion
    {
        $resultado = new ResultadoImportacion;

        $normalizadas = collect($filasInput)->map(fn ($fila) => [
            'local_id' => $fila['local_id'] ?? null,
            'lectura_actual' => $fila['lectura_actual'] ?? null,
            'total' => $fila['total'] ?? null,
        ]);

        $contexto = $this->contexto($normalizadas, $periodo);
        $tarifa = (float) ConfiguracionGeneral::actual()->tarifa_luz_por_unidad;

        DB::transaction(function () use ($normalizadas, $periodo, $contexto, $tarifa, $resultado) {
            foreach ($normalizadas as $entrada) {
                $fila = $this->validarFila($entrada, $periodo, $contexto);

                if (! $fila->valida) {
                    $resultado->registrarOmision();

                    continue;
                }

                $anteriorReal = $contexto['anteriores']->get($fila->localId)?->lectura_actual;
                $anteriorReal = $anteriorReal !== null ? (float) $anteriorReal : null;
                $consumoParaTotal = $this->servicioConsumo->calcularConsumo($anteriorReal ?? 0.0, (float) $fila->valores['lectura_actual']);

                $totalEnviado = $entrada['total'] ?? null;
                $total = is_numeric($totalEnviado)
                    ? (float) $totalEnviado
                    : round(($consumoParaTotal ?? 0.0) * $tarifa, 2);

                $existente = $contexto['delPeriodo']->get($fila->localId);

                LecturaMedidor::updateOrCreate(
                    ['locacion_id' => $fila->localId, 'periodo' => $periodo->format('Y-m-d')],
                    [
                        'lectura_anterior' => $anteriorReal,
                        'lectura_actual' => $fila->valores['lectura_actual'],
                        'total' => $total,
                        'fecha_registro' => now(),
                    ],
                );

                $existente !== null ? $resultado->registrarActualizacion() : $resultado->registrarCreacion();
            }
        });

        return $resultado;
    }

    /**
     * @param  Collection<int, mixed>  $filas
     * @return array{locaciones: Collection, delPeriodo: Collection, anteriores: Collection}
     */
    private function contexto(Collection $filas, Carbon $periodo): array
    {
        $ids = $filas
            ->map(fn ($fila) => is_array($fila) ? ($fila['local_id'] ?? null) : ($fila['local_id'] ?? null))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return [
            'locaciones' => Locacion::whereIn('id', $ids)->get()->keyBy('id'),
            'delPeriodo' => LecturaMedidor::whereIn('locacion_id', $ids)
                ->where('periodo', $periodo->format('Y-m-d'))
                ->get()
                ->keyBy('locacion_id'),
            'anteriores' => LecturaMedidor::whereIn('locacion_id', $ids)
                ->where('periodo', '<', $periodo->format('Y-m-d'))
                ->orderByDesc('periodo')
                ->get()
                ->unique('locacion_id')
                ->keyBy('locacion_id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array{locaciones: Collection, delPeriodo: Collection, anteriores: Collection}  $contexto
     */
    private function validarFila(array $fila, Carbon $periodo, array $contexto): FilaImportada
    {
        $localIdCrudo = $fila['local_id'] ?? null;
        $locacion = is_numeric($localIdCrudo) ? $contexto['locaciones']->get((int) $localIdCrudo) : null;
        $nombre = $locacion?->nombre ?? (string) ($fila['locacion'] ?? '(sin nombre)');
        $lecturaActual = $fila['lectura_actual'] ?? null;
        $anteriorReal = is_numeric($localIdCrudo)
            ? $contexto['anteriores']->get((int) $localIdCrudo)?->lectura_actual
            : null;

        $entidad = new FilaImportada(
            localId: is_numeric($localIdCrudo) ? (int) $localIdCrudo : null,
            nombre: $nombre,
            valores: [
                'lectura_actual' => is_numeric($lecturaActual) ? (float) $lecturaActual : $lecturaActual,
                'lectura_anterior' => $anteriorReal !== null ? (float) $anteriorReal : null,
            ],
        );

        if (! is_numeric($localIdCrudo)) {
            $entidad->invalidar('La columna local_id fue alterada o quedó vacía.', noRecuperable: true);

            return $entidad;
        }

        if ($locacion === null || ! $locacion->es_alquilable) {
            $entidad->invalidar('No corresponde a una locación alquilable activa.', noRecuperable: true);

            return $entidad;
        }

        if ($lecturaActual === null || $lecturaActual === '') {
            $entidad->invalidar('Falta la lectura actual.');

            return $entidad;
        }

        if (! is_numeric($lecturaActual) || (float) $lecturaActual < 0) {
            $entidad->invalidar('La lectura actual debe ser un número mayor o igual a 0.');

            return $entidad;
        }

        $anteriorReal = $contexto['anteriores']->get($locacion->id)?->lectura_actual;
        if ($anteriorReal !== null && (float) $lecturaActual < (float) $anteriorReal) {
            $entidad->invalidar('La lectura actual es menor que la del periodo anterior.');

            return $entidad;
        }

        $entidad->accion = $contexto['delPeriodo']->has($locacion->id)
            ? FilaImportada::ACCION_ACTUALIZAR
            : FilaImportada::ACCION_CREAR;

        return $entidad;
    }

    /**
     * @param  array<int, string>  $encabezados
     */
    private function pareceOtraPlantilla(array $encabezados): bool
    {
        return in_array('contrato', $encabezados, true) || in_array('renta_s', $encabezados, true);
    }

    private function normalizarPeriodo(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->format('Y-m');
        } catch (\Throwable) {
            return preg_match('/^\d{4}-\d{2}$/', $valor) ? $valor : null;
        }
    }

    /**
     * @return array{ok: bool, motivoRechazo: string, filas: array<int, FilaImportada>}
     */
    private function rechazo(string $motivo): array
    {
        return ['ok' => false, 'motivoRechazo' => $motivo, 'filas' => []];
    }
}

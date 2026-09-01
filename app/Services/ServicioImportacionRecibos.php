<?php

namespace App\Services;

use App\Exceptions\ConceptosReciboYaCubiertosException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Imports\ImportacionRecibosImport;
use App\Models\ConceptoGastoFijo;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Support\Importacion\FilaImportada;
use App\Support\Importacion\ResultadoImportacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * specs/044 (US2): parseo + validación por fila + upsert de la carga masiva de
 * recibos por plantilla. Reutiliza `ServicioGeneracionReciboPeriodo` para
 * crear/actualizar el recibo y sus conceptos; asume un único recibo vigente
 * por locación y periodo (research.md Decisión 3). La vista previa no se
 * persiste (FR-013); la confirmación es atómica (FR-011) e idempotente
 * (FR-012).
 */
class ServicioImportacionRecibos
{
    public function __construct(
        private readonly ServicioGeneracionReciboPeriodo $servicioGeneracion,
        private readonly ServicioPlantillaRecibos $servicioPlantilla,
    ) {}

    /**
     * @return array{ok: bool, motivoRechazo: string|null, filas: array<int, FilaImportada>, columnas: Collection, avisos: array<int, string>}
     */
    public function previsualizar(UploadedFile $archivo, Carbon $periodo): array
    {
        $import = new ImportacionRecibosImport;
        Excel::import($import, $archivo);

        $columnas = $this->servicioPlantilla->columnasConcepto();

        if ($import->filas->isEmpty()) {
            return $this->rechazo('El archivo no tiene filas de datos.', $columnas);
        }

        if ($this->pareceOtraPlantilla($import->encabezados)) {
            return $this->rechazo('El archivo parece ser la plantilla de lecturas, no la de recibos.', $columnas);
        }

        $faltantes = $import->columnasFaltantes();
        if (! empty($faltantes)) {
            return $this->rechazo(
                'El archivo no corresponde a la plantilla de recibos: faltan las columnas '.
                implode(', ', $faltantes).'.',
                $columnas,
            );
        }

        $periodoEsperado = $periodo->format('Y-m');
        $periodosArchivo = $import->filas
            ->map(fn ($fila) => $this->normalizarPeriodo((string) ($fila['periodo'] ?? '')))
            ->filter()
            ->unique();

        if ($periodosArchivo->isEmpty() || $periodosArchivo->contains(fn ($p) => $p !== $periodoEsperado)) {
            return $this->rechazo("El archivo fue generado para otro periodo. La pantalla está en {$periodoEsperado}.", $columnas);
        }

        // Columnas de concepto del archivo que ya no existen en el catálogo → aviso, no invalidan filas.
        $avisos = [];
        $slugsCatalogo = $columnas->map(fn ($c) => ServicioPlantillaRecibos::slug($c->nombre))->all();
        $slugsFijos = ['periodo', 'local_id', 'locacion', 'contrato', 'renta', 'luz', 'total'];
        foreach ($import->encabezados as $encabezado) {
            if (! in_array($encabezado, $slugsFijos, true) && ! in_array($encabezado, $slugsCatalogo, true)) {
                $avisos[] = "La columna «{$encabezado}» se ignoró: ya no está en el catálogo de conceptos.";
            }
        }

        $contexto = $this->contexto($import->filas->pluck('local_id')->all(), $periodo);
        $filas = $import->filas
            ->map(fn ($fila) => $this->validarFila($fila->toArray(), $periodo, $contexto, $columnas))
            ->all();

        return ['ok' => true, 'motivoRechazo' => null, 'filas' => $filas, 'columnas' => $columnas, 'avisos' => $avisos];
    }

    /**
     * @param  array<int, array{local_id: mixed, renta?: mixed, luz?: mixed, conceptos?: array<int, mixed>, total?: mixed}>  $filasInput
     */
    public function confirmar(array $filasInput, Carbon $periodo): ResultadoImportacion
    {
        $resultado = new ResultadoImportacion;
        $columnas = $this->servicioPlantilla->columnasConcepto();
        $conceptoLuz = ConceptoGastoFijo::firstWhere('clave', 'luz');

        $normalizadas = collect($filasInput)->map(fn ($fila) => [
            'local_id' => $fila['local_id'] ?? null,
            'renta' => $fila['renta'] ?? null,
            'luz' => $fila['luz'] ?? null,
            'conceptos' => (array) ($fila['conceptos'] ?? []),
            'total' => $fila['total'] ?? null,
        ]);

        $contexto = $this->contexto($normalizadas->pluck('local_id')->all(), $periodo);

        DB::transaction(function () use ($normalizadas, $periodo, $contexto, $columnas, $conceptoLuz, $resultado) {
            foreach ($normalizadas as $entrada) {
                $fila = $this->validarFila($entrada, $periodo, $contexto, $columnas);

                if (! $fila->valida) {
                    $resultado->registrarOmision();

                    continue;
                }

                $locacion = $contexto['locaciones']->get($fila->localId);
                $recibosVigentes = $contexto['recibos']->get($fila->localId, collect());

                $renta = (float) $fila->valores['renta'];
                $conceptos = $this->mapaConceptos($fila, $columnas, $conceptoLuz);
                $sumaComponentes = $renta + array_sum($conceptos);

                $totalExplicito = is_numeric($fila->valores['total']) ? (float) $fila->valores['total'] : null;
                if ($totalExplicito !== null && abs($totalExplicito - $sumaComponentes) > 0.001 && $conceptoLuz !== null) {
                    // specs/019: el total editable del recibo se refleja en el componente de luz.
                    $conceptos[$conceptoLuz->id] = round(($conceptos[$conceptoLuz->id] ?? 0) + ($totalExplicito - $sumaComponentes), 2);
                }

                $datos = [
                    'incluye_alquiler' => $renta > 0,
                    'monto_renta' => $renta,
                    'fecha_emision' => now()->format('Y-m-d'),
                    'conceptos' => $conceptos,
                ];

                try {
                    if ($recibosVigentes->count() === 1) {
                        $this->servicioGeneracion->actualizar($recibosVigentes->first(), $datos);
                        $resultado->registrarActualizacion();
                    } else {
                        $this->servicioGeneracion->generar($locacion, $periodo, $datos);
                        $resultado->registrarCreacion();
                    }
                } catch (SinContratoActivoEnPeriodoException|ConceptosReciboYaCubiertosException) {
                    $resultado->registrarOmision();
                }
            }
        });

        return $resultado;
    }

    /**
     * @param  Collection<int, ConceptoGastoFijo>  $columnas
     * @return array<int, float> concepto_gasto_fijo_id => monto
     */
    private function mapaConceptos(FilaImportada $fila, Collection $columnas, ?ConceptoGastoFijo $conceptoLuz): array
    {
        $conceptos = [];

        if ($conceptoLuz !== null) {
            $conceptos[$conceptoLuz->id] = round((float) $fila->valores['luz'], 2);
        }

        foreach ($columnas as $concepto) {
            $conceptos[$concepto->id] = round((float) ($fila->valores['conceptos'][$concepto->id] ?? 0), 2);
        }

        return $conceptos;
    }

    /**
     * @param  array<int, mixed>  $localIds
     * @return array{locaciones: Collection, contratos: Collection, recibos: Collection}
     */
    private function contexto(array $localIds, Carbon $periodo): array
    {
        $ids = collect($localIds)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values();

        $locaciones = Locacion::whereIn('id', $ids)->get()->keyBy('id');

        return [
            'locaciones' => $locaciones,
            'contratos' => $locaciones->mapWithKeys(fn (Locacion $l) => [$l->id => $l->contratoActivoEnPeriodo($periodo)]),
            'recibos' => Recibo::whereIn('locacion_id', $ids)
                ->where('periodo', $periodo->format('Y-m-d'))
                ->vigente()
                ->with('conceptos')
                ->get()
                ->groupBy('locacion_id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array{locaciones: Collection, contratos: Collection, recibos: Collection}  $contexto
     * @param  Collection<int, ConceptoGastoFijo>  $columnas
     */
    private function validarFila(array $fila, Carbon $periodo, array $contexto, Collection $columnas): FilaImportada
    {
        $localIdCrudo = $fila['local_id'] ?? null;
        $locacion = is_numeric($localIdCrudo) ? $contexto['locaciones']->get((int) $localIdCrudo) : null;

        // Los valores editables pueden venir de dos formas: del archivo (slug por
        // columna) o de la tabla de vista previa (renta/luz/conceptos[id]/total).
        $conceptosEntrada = [];
        foreach ($columnas as $concepto) {
            $desdeTabla = $fila['conceptos'][$concepto->id] ?? null;
            $desdeArchivo = $fila[ServicioPlantillaRecibos::slug($concepto->nombre)] ?? null;
            $conceptosEntrada[$concepto->id] = $desdeTabla ?? $desdeArchivo;
        }

        $entidad = new FilaImportada(
            localId: is_numeric($localIdCrudo) ? (int) $localIdCrudo : null,
            nombre: $locacion?->nombre ?? (string) ($fila['locacion'] ?? '(sin nombre)'),
            valores: [
                'renta' => $fila['renta'] ?? null,
                'luz' => $fila['luz'] ?? null,
                'conceptos' => $conceptosEntrada,
                'total' => $fila['total'] ?? null,
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

        if (($contexto['contratos']->get($locacion->id)) === null) {
            $entidad->invalidar('La locación no tiene un contrato activo en el periodo.', noRecuperable: true);

            return $entidad;
        }

        if ($contexto['recibos']->get($locacion->id, collect())->count() > 1) {
            $entidad->invalidar('Esta locación tiene varios recibos en el periodo; edítelos individualmente.', noRecuperable: true);

            return $entidad;
        }

        foreach ($this->montosDeLaFila($entidad) as $etiqueta => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }
            if (! is_numeric($valor) || (float) $valor < 0) {
                $entidad->invalidar("El monto «{$etiqueta}» debe ser un número mayor o igual a 0.");

                return $entidad;
            }
        }

        // Normaliza a float lo que quedó válido.
        $entidad->valores['renta'] = is_numeric($entidad->valores['renta']) ? (float) $entidad->valores['renta'] : 0.0;
        $entidad->valores['luz'] = is_numeric($entidad->valores['luz']) ? (float) $entidad->valores['luz'] : 0.0;
        foreach ($entidad->valores['conceptos'] as $id => $v) {
            $entidad->valores['conceptos'][$id] = is_numeric($v) ? (float) $v : 0.0;
        }

        $entidad->accion = $contexto['recibos']->get($locacion->id, collect())->count() === 1
            ? FilaImportada::ACCION_ACTUALIZAR
            : FilaImportada::ACCION_CREAR;

        return $entidad;
    }

    /**
     * @return array<string, mixed>
     */
    private function montosDeLaFila(FilaImportada $fila): array
    {
        $montos = ['Renta' => $fila->valores['renta'], 'Luz' => $fila->valores['luz'], 'Total' => $fila->valores['total']];

        foreach ($fila->valores['conceptos'] as $id => $valor) {
            $montos["concepto {$id}"] = $valor;
        }

        return $montos;
    }

    /**
     * @param  array<int, string>  $encabezados
     */
    private function pareceOtraPlantilla(array $encabezados): bool
    {
        return in_array('lectura_actual', $encabezados, true)
            || in_array('lectura_periodo_anterior', $encabezados, true);
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
     * @param  Collection<int, ConceptoGastoFijo>  $columnas
     * @return array{ok: bool, motivoRechazo: string, filas: array<int, FilaImportada>, columnas: Collection, avisos: array<int, string>}
     */
    private function rechazo(string $motivo, Collection $columnas): array
    {
        return ['ok' => false, 'motivoRechazo' => $motivo, 'filas' => [], 'columnas' => $columnas, 'avisos' => []];
    }
}

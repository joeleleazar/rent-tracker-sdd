<?php

namespace App\Services;

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\ValorConceptoContrato;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * specs/044 (US2): arma las filas de la plantilla de carga masiva de recibos
 * para un periodo — una fila por locación con contrato activo. Las columnas de
 * concepto son dinámicas: "Renta" y "Luz" son fijas (conceptos protegidos) y
 * el resto sale del catálogo `ConceptoGastoFijo` activo (specs/024).
 *
 * Si ya existe un único recibo vigente de la locación/periodo, la fila viene
 * precargada con sus montos; si no, con los valores derivados del contrato.
 */
class ServicioPlantillaRecibos
{
    public function __construct(
        private readonly ServicioConstruccionArbolLocaciones $servicioArbol,
        private readonly ServicioGeneracionReciboPeriodo $servicioGeneracion,
    ) {}

    /** Conceptos activos que NO son Renta ni Luz — una columna por cada uno. */
    public function columnasConcepto(): Collection
    {
        return ConceptoGastoFijo::activos()->ordenados()->get()
            ->reject(fn (ConceptoGastoFijo $c) => $c->esProtegido())
            ->values();
    }

    /**
     * Encabezados de la hoja, en orden. La primera columna `periodo` es técnica
     * (FR-010).
     *
     * @return array<int, string>
     */
    public function encabezados(): array
    {
        return [
            'periodo',
            'local_id',
            'Locación',
            'Contrato',
            'Renta',
            'Luz',
            ...$this->columnasConcepto()->pluck('nombre')->all(),
            'Total',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filas(Carbon $periodo): array
    {
        $columnas = $this->columnasConcepto();
        $conceptoLuz = ConceptoGastoFijo::firstWhere('clave', 'luz');

        $idsAlquilables = Locacion::alquilables()->pluck('id');

        $contratos = Contrato::whereIn('locacion_id', $idsAlquilables)
            ->where('estado', '!=', 'rescindido')
            ->where('fecha_inicio', '<=', $periodo->copy()->endOfMonth())
            ->where('fecha_fin', '>=', $periodo->copy()->startOfMonth())
            ->orderByDesc('fecha_inicio')
            ->with('inquilinos')
            ->get()
            ->unique('locacion_id')
            ->keyBy('locacion_id');

        $valoresConcepto = ValorConceptoContrato::whereIn('contrato_id', $contratos->pluck('id'))
            ->get()
            ->groupBy('contrato_id');

        $recibos = Recibo::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->vigente()
            ->with('conceptos')
            ->get()
            ->groupBy('locacion_id');

        return $this->aplanar(
            $this->servicioArbol->construir(),
            $periodo,
            $columnas,
            $conceptoLuz,
            $contratos,
            $valoresConcepto,
            $recibos,
        );
    }

    /**
     * @param  array<int, array{locacion: Locacion, hijos: array}>  $nodos
     * @return array<int, array<string, mixed>>
     */
    private function aplanar(
        array $nodos,
        Carbon $periodo,
        Collection $columnas,
        ?ConceptoGastoFijo $conceptoLuz,
        Collection $contratos,
        Collection $valoresConcepto,
        Collection $recibos,
        string $ruta = '',
    ): array {
        $filas = [];

        foreach ($nodos as $nodo) {
            $locacion = $nodo['locacion'];
            $rutaActual = $ruta === '' ? $locacion->nombre : $ruta.' > '.$locacion->nombre;
            $contrato = $contratos->get($locacion->id);

            if ($locacion->es_alquilable && $contrato !== null) {
                $recibosDeLaLocacion = $recibos->get($locacion->id, collect());
                $recibo = $recibosDeLaLocacion->count() === 1 ? $recibosDeLaLocacion->first() : null;
                $variosRecibos = $recibosDeLaLocacion->count() > 1;

                $valores = $valoresConcepto->get($contrato->id, collect())->keyBy('concepto_gasto_fijo_id');

                $fila = [
                    'periodo' => $periodo->format('Y-m'),
                    'local_id' => $locacion->id,
                    'Locación' => $rutaActual,
                    'Contrato' => $variosRecibos
                        ? 'varios recibos — editar individualmente'
                        : ('#'.$contrato->id.' '.($contrato->inquilinoPrincipal()?->nombreCompleto() ?? '')),
                    'Renta' => $this->montoRenta($recibo, $contrato),
                    'Luz' => $this->montoLuz($recibo, $conceptoLuz, $locacion, $periodo),
                ];

                foreach ($columnas as $concepto) {
                    $fila[$concepto->nombre] = $this->montoConcepto($recibo, $concepto, $valores);
                }

                $fila['Total'] = $recibo !== null
                    ? number_format($recibo->total(), 2, '.', '')
                    : number_format($this->sumaComponentes($fila, $columnas), 2, '.', '');

                $filas[] = $fila;
            }

            if (! empty($nodo['hijos'])) {
                $filas = [...$filas, ...$this->aplanar(
                    $nodo['hijos'], $periodo, $columnas, $conceptoLuz, $contratos, $valoresConcepto, $recibos, $rutaActual,
                )];
            }
        }

        return $filas;
    }

    private function montoRenta(?Recibo $recibo, Contrato $contrato): string
    {
        $valor = $recibo !== null ? ($recibo->monto_renta ?? 0) : ($contrato->monto_renta ?? 0);

        return number_format((float) $valor, 2, '.', '');
    }

    private function montoLuz(?Recibo $recibo, ?ConceptoGastoFijo $conceptoLuz, Locacion $locacion, Carbon $periodo): string
    {
        if ($recibo !== null && $conceptoLuz !== null) {
            $fila = $recibo->conceptos->firstWhere('concepto_gasto_fijo_id', $conceptoLuz->id);

            return number_format((float) ($fila?->monto ?? 0), 2, '.', '');
        }

        $lectura = $this->servicioGeneracion->lecturaDelPeriodo($locacion, $periodo);

        return number_format($this->servicioGeneracion->calcularMontoLuzSugerido($lectura), 2, '.', '');
    }

    private function montoConcepto(?Recibo $recibo, ConceptoGastoFijo $concepto, Collection $valores): string
    {
        if ($recibo !== null) {
            $fila = $recibo->conceptos->firstWhere('concepto_gasto_fijo_id', $concepto->id);

            return number_format((float) ($fila?->monto ?? 0), 2, '.', '');
        }

        return number_format((float) ($valores->get($concepto->id)?->valor ?? 0), 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private function sumaComponentes(array $fila, Collection $columnas): float
    {
        $suma = (float) $fila['Renta'] + (float) $fila['Luz'];

        foreach ($columnas as $concepto) {
            $suma += (float) ($fila[$concepto->nombre] ?? 0);
        }

        return $suma;
    }

    /** Slug de encabezado que produce WithHeadingRow para una columna de concepto. */
    public static function slug(string $nombre): string
    {
        return Str::slug($nombre, '_');
    }
}

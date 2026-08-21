<?php

namespace App\Services;

use App\Exceptions\ReciboDuplicadoPeriodoException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Models\ConfiguracionGeneral;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Genera y edita recibos por locación y periodo, con conceptos seleccionables y
 * monto de luz sugerido a partir del consumo calculado (specs/005, US2).
 */
class ServicioGeneracionReciboPeriodo
{
    public function __construct(
        private readonly ServicioCalculoProrrateoContrato $servicioProrrateo,
    ) {
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function generar(Locacion $locacion, Carbon $periodo, array $datos): Recibo
    {
        return DB::transaction(function () use ($locacion, $periodo, $datos) {
            $existente = Recibo::where('locacion_id', $locacion->id)
                ->where('periodo', $periodo->format('Y-m-d'))
                ->first();

            if ($existente !== null) {
                throw new ReciboDuplicadoPeriodoException($existente);
            }

            $contratoActivo = $locacion->contratoActivoEnPeriodo($periodo);

            if ($contratoActivo === null) {
                throw new SinContratoActivoEnPeriodoException();
            }

            $lectura = $this->lecturaDelPeriodo($locacion, $periodo);
            $prorrateo = $this->servicioProrrateo->calcular($contratoActivo, $periodo);

            return Recibo::create([
                'contrato_id' => $contratoActivo->id,
                'locacion_id' => $locacion->id,
                'lectura_medidor_id' => $lectura?->id,
                'monto_renta' => $datos['monto_renta'],
                'monto_agua' => $datos['monto_agua'],
                'monto_luz' => $datos['monto_luz'],
                'monto_pasadizo' => $datos['monto_pasadizo'],
                'monto_seguridad' => $datos['monto_seguridad'],
                'incluye_alquiler' => $datos['incluye_alquiler'],
                'incluye_luz' => $datos['incluye_luz'],
                'incluye_agua' => $datos['incluye_agua'],
                'incluye_seguridad' => $datos['incluye_seguridad'],
                'incluye_pasadizo' => $datos['incluye_pasadizo'],
                'periodo' => $periodo->format('Y-m-d'),
                'fecha_emision' => $datos['fecha_emision'],
                'dias_activos_periodo' => $prorrateo['dias_activos'] ?? null,
                'dias_totales_periodo' => $prorrateo['dias_totales'] ?? null,
            ]);
        });
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(Recibo $recibo, array $datos): void
    {
        DB::transaction(function () use ($recibo, $datos) {
            $recibo->update([
                'monto_renta' => $datos['monto_renta'],
                'monto_agua' => $datos['monto_agua'],
                'monto_luz' => $datos['monto_luz'],
                'monto_pasadizo' => $datos['monto_pasadizo'],
                'monto_seguridad' => $datos['monto_seguridad'],
                'incluye_alquiler' => $datos['incluye_alquiler'],
                'incluye_luz' => $datos['incluye_luz'],
                'incluye_agua' => $datos['incluye_agua'],
                'incluye_seguridad' => $datos['incluye_seguridad'],
                'incluye_pasadizo' => $datos['incluye_pasadizo'],
                'fecha_emision' => $datos['fecha_emision'],
            ]);
        });
    }

    public function lecturaDelPeriodo(Locacion $locacion, Carbon $periodo): ?LecturaMedidor
    {
        return LecturaMedidor::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->first();
    }

    /**
     * Monto sugerido de luz = consumo_calculado del periodo × tarifa vigente
     * (FR-006/FR-007). Devuelve 0.0 si no hay lectura o no hay dato de consumo
     * anterior ("sin dato anterior").
     */
    public function calcularMontoLuzSugerido(?LecturaMedidor $lectura): float
    {
        if ($lectura === null || $lectura->consumo_calculado === null) {
            return 0.0;
        }

        $tarifa = (float) ConfiguracionGeneral::actual()->tarifa_luz_por_unidad;

        return round((float) $lectura->consumo_calculado * $tarifa, 2);
    }
}

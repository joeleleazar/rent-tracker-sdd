<?php

namespace App\Services;

use App\Exceptions\ConceptosReciboYaCubiertosException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Models\ConceptoGastoFijo;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Genera y edita recibos por locación y periodo, con conceptos seleccionables y
 * monto de luz sugerido a partir del consumo calculado (specs/005, US2). specs/023:
 * una misma locación y periodo puede tener más de un recibo, siempre que el conjunto
 * de conceptos cubiertos entre todos ellos no se superponga. specs/024: los conceptos
 * ya no son un array fijo de 5 claves — se leen del catálogo dinámico
 * (`ConceptoGastoFijo`), y "Renta" se identifica por `esRenta()` en vez de una clave
 * `incluye_alquiler` codificada en este servicio.
 */
class ServicioGeneracionReciboPeriodo
{
    public function __construct(
        private readonly ServicioCalculoProrrateoContrato $servicioProrrateo,
    ) {
    }

    /**
     * @param array{incluye_alquiler?: bool, monto_renta?: mixed, fecha_emision: mixed, conceptos?: array<int, mixed>} $datos
     */
    public function generar(Locacion $locacion, Carbon $periodo, array $datos): Recibo
    {
        return DB::transaction(function () use ($locacion, $periodo, $datos) {
            $contratoActivo = $locacion->contratoActivoEnPeriodo($periodo);

            if ($contratoActivo === null) {
                throw new SinContratoActivoEnPeriodoException();
            }

            $this->validarSinSuperposicion($locacion, $periodo, $datos, null);

            $lectura = $this->lecturaDelPeriodo($locacion, $periodo);
            $prorrateo = $this->servicioProrrateo->calcular($contratoActivo, $periodo);

            $recibo = Recibo::create([
                'contrato_id' => $contratoActivo->id,
                'locacion_id' => $locacion->id,
                'lectura_medidor_id' => $lectura?->id,
                'monto_renta' => ($datos['incluye_alquiler'] ?? false) ? $datos['monto_renta'] : null,
                'periodo' => $periodo->format('Y-m-d'),
                'fecha_emision' => $datos['fecha_emision'],
                'dias_activos_periodo' => $prorrateo['dias_activos'] ?? null,
                'dias_totales_periodo' => $prorrateo['dias_totales'] ?? null,
            ]);

            $this->guardarConceptos($recibo, $datos['conceptos'] ?? []);

            return $recibo;
        });
    }

    /**
     * @param array{incluye_alquiler?: bool, monto_renta?: mixed, fecha_emision: mixed, conceptos?: array<int, mixed>} $datos
     */
    public function actualizar(Recibo $recibo, array $datos): void
    {
        DB::transaction(function () use ($recibo, $datos) {
            $this->validarSinSuperposicion($recibo->locacion, $recibo->periodo, $datos, $recibo->id);

            $recibo->update([
                'monto_renta' => ($datos['incluye_alquiler'] ?? false) ? $datos['monto_renta'] : null,
                'fecha_emision' => $datos['fecha_emision'],
            ]);

            $recibo->conceptos()->delete();
            $this->guardarConceptos($recibo, $datos['conceptos'] ?? []);
        });
    }

    /**
     * @param array<int, mixed> $conceptos concepto_gasto_fijo_id => monto
     */
    private function guardarConceptos(Recibo $recibo, array $conceptos): void
    {
        foreach ($conceptos as $conceptoId => $monto) {
            $recibo->conceptos()->create([
                'concepto_gasto_fijo_id' => $conceptoId,
                'monto' => $monto,
            ]);
        }
    }

    /**
     * specs/024 FR-006: conceptos activos del catálogo que todavía no están
     * cubiertos por ningún recibo existente de esta locación y periodo,
     * ordenados según `ConceptoGastoFijo::ordenados()`.
     *
     * @return Collection<int, ConceptoGastoFijo>
     */
    public function conceptosDisponibles(Locacion $locacion, Carbon $periodo): Collection
    {
        $recibos = Recibo::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->with('conceptos')
            ->get();

        return $this->conceptosDisponiblesDesde(ConceptoGastoFijo::activos()->ordenados()->get(), $recibos);
    }

    /**
     * specs/024: variante pura de `conceptosDisponibles()` que opera sobre
     * colecciones ya cargadas, para que un listado de varias locaciones
     * (`RegistroMasivoRecibosController::datosDelPeriodo()`) pueda calcular
     * esto sin una consulta por locación (mismo criterio anti-N+1 de specs/018).
     *
     * @param  Collection<int, ConceptoGastoFijo>  $conceptosActivos
     * @param  Collection<int, Recibo>  $recibosDeEsaLocacionYPeriodo
     * @return Collection<int, ConceptoGastoFijo>
     */
    public function conceptosDisponiblesDesde(Collection $conceptosActivos, Collection $recibosDeEsaLocacionYPeriodo): Collection
    {
        $rentaCubierta = $recibosDeEsaLocacionYPeriodo->contains(fn (Recibo $r) => $r->monto_renta !== null);
        $idsCubiertos = $recibosDeEsaLocacionYPeriodo
            ->flatMap(fn (Recibo $r) => $r->conceptos->pluck('concepto_gasto_fijo_id'))
            ->unique();

        return $conceptosActivos
            ->reject(fn (ConceptoGastoFijo $c) => $c->esRenta() ? $rentaCubierta : $idsCubiertos->contains($c->id))
            ->values();
    }

    /**
     * specs/024: para cada concepto ya cubierto de esta locación y periodo, el recibo
     * que lo cubre — usado por la UI para enlazar "ya está cubierto" al recibo real.
     *
     * @return Collection<int, Recibo> keyed by concepto_gasto_fijo_id
     */
    public function reciboQueCubre(Locacion $locacion, Carbon $periodo, ?int $excluirReciboId = null): Collection
    {
        $recibos = Recibo::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->when($excluirReciboId !== null, fn ($q) => $q->where('id', '!=', $excluirReciboId))
            ->with('conceptos')
            ->get();

        return $this->reciboQueCubreDesde(ConceptoGastoFijo::activos()->ordenados()->get(), $recibos);
    }

    /**
     * specs/024: variante pura de `reciboQueCubre()` — ver `conceptosDisponiblesDesde()`.
     *
     * @param  Collection<int, ConceptoGastoFijo>  $conceptosActivos
     * @param  Collection<int, Recibo>  $recibosDeEsaLocacionYPeriodo
     * @return Collection<int, Recibo> keyed by concepto_gasto_fijo_id
     */
    public function reciboQueCubreDesde(Collection $conceptosActivos, Collection $recibosDeEsaLocacionYPeriodo): Collection
    {
        $mapa = collect();

        foreach ($conceptosActivos as $concepto) {
            $recibo = $concepto->esRenta()
                ? $recibosDeEsaLocacionYPeriodo->first(fn (Recibo $r) => $r->monto_renta !== null)
                : $recibosDeEsaLocacionYPeriodo->first(fn (Recibo $r) => $r->conceptos->contains('concepto_gasto_fijo_id', $concepto->id));

            if ($recibo !== null) {
                $mapa->put($concepto->id, $recibo);
            }
        }

        return $mapa;
    }

    /**
     * specs/023 FR-007/FR-008: dentro de la transacción, relee (con `lockForUpdate()`)
     * los recibos existentes de esta locación y periodo y rechaza cualquier concepto
     * solicitado que ya esté cubierto — incluso si esa cobertura se produjo después de
     * que el llamador leyó el estado por última vez (condición de carrera).
     *
     * @param array{incluye_alquiler?: bool, conceptos?: array<int, mixed>} $datos
     */
    private function validarSinSuperposicion(Locacion $locacion, Carbon $periodo, array $datos, ?int $reciboIdActual): void
    {
        $recibos = Recibo::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->when($reciboIdActual !== null, fn ($q) => $q->where('id', '!=', $reciboIdActual))
            ->lockForUpdate()
            ->with('conceptos')
            ->get();

        $rentaCubierta = $recibos->contains(fn (Recibo $r) => $r->monto_renta !== null);
        $idsCubiertos = $recibos->flatMap(fn (Recibo $r) => $r->conceptos->pluck('concepto_gasto_fijo_id'))->unique();

        $superpuestos = collect();

        if (($datos['incluye_alquiler'] ?? false) && $rentaCubierta) {
            $superpuestos->push(ConceptoGastoFijo::where('clave', 'renta')->first());
        }

        $idsSolicitados = collect(array_keys($datos['conceptos'] ?? []));
        $idsSuperpuestos = $idsSolicitados->intersect($idsCubiertos);

        if ($idsSuperpuestos->isNotEmpty()) {
            $superpuestos = $superpuestos->merge(ConceptoGastoFijo::whereIn('id', $idsSuperpuestos)->get());
        }

        if ($superpuestos->isNotEmpty()) {
            $recibosQueCubren = $recibos->filter(fn (Recibo $r) => ($superpuestos->contains(fn (ConceptoGastoFijo $c) => $c->esRenta()) && $r->monto_renta !== null)
                || $r->conceptos->pluck('concepto_gasto_fijo_id')->intersect($superpuestos->pluck('id'))->isNotEmpty())
                ->values();

            throw new ConceptosReciboYaCubiertosException($superpuestos->values(), $recibosQueCubren);
        }
    }

    public function lecturaDelPeriodo(Locacion $locacion, Carbon $periodo): ?LecturaMedidor
    {
        return LecturaMedidor::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->first();
    }

    /**
     * Monto sugerido de luz = total ya persistido de la lectura del periodo
     * (specs/019 FR-006), fijado al momento en que esa lectura se registró —
     * no se recalcula con la tarifa vigente al generar el recibo, que puede
     * ya no ser la misma. Devuelve 0.0 si no hay lectura para ese periodo.
     */
    public function calcularMontoLuzSugerido(?LecturaMedidor $lectura): float
    {
        if ($lectura === null) {
            return 0.0;
        }

        return (float) $lectura->total;
    }
}

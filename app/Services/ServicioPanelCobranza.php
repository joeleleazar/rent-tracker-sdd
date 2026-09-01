<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Pago;
use App\Models\Recibo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * specs/043: cálculos de solo lectura del panel de inicio (estado de cobranza).
 *
 * Todo se deriva en cada request a partir de `recibos`, `recibo_conceptos`,
 * `pagos` y `contratos`; no se persiste nada. La consulta base
 * (`recibosBaseParaPanel`) se ejecuta una sola vez y alimenta el listado de
 * morosos, los próximos vencimientos, la cartera total y el facturado/cobrado
 * del periodo (research.md §2).
 */
class ServicioPanelCobranza
{
    /** Umbrales acumulativos, en días, del bloque "contratos por vencer". */
    private const HITO_MAYOR_VENCIMIENTO = 30;

    /** Colección base cacheada durante el request (una sola consulta). */
    private ?Collection $baseCache = null;

    public function __construct(
        private readonly ServicioCalculoFechaLimitePago $fechaLimite,
    ) {
    }

    // -----------------------------------------------------------------------
    // Consulta base
    // -----------------------------------------------------------------------

    /**
     * Recibos no anulados con la suma de sus conceptos y de sus pagos ya
     * resueltas como subconsultas escalares (`withSum`), sin hidratar las
     * colecciones hijas — una sola consulta para todo el panel.
     */
    private function recibosBaseParaPanel(): Collection
    {
        return $this->baseCache ??= Recibo::query()
            ->where('estado', '!=', 'anulado')
            ->withSum('conceptos as suma_conceptos', 'monto')
            ->withSum('pagos as suma_pagos', 'monto')
            ->with(['contrato.inquilinos', 'locacion'])
            ->get();
    }

    /**
     * Deriva los montos y la fecha límite de un recibo. Devuelve un objeto con
     * los campos comunes a morosos y próximos vencimientos.
     */
    private function filaBase(Recibo $recibo): object
    {
        $total = (float) ($recibo->monto_renta ?? 0) + (float) ($recibo->suma_conceptos ?? 0);
        $pagado = (float) ($recibo->suma_pagos ?? 0);
        $inquilino = $recibo->contrato?->inquilinoPrincipal();

        return (object) [
            'recibo' => $recibo,
            'inquilinoId' => $inquilino?->id,
            'inquilino' => $inquilino?->nombreCompleto(),
            'locacion' => $recibo->locacion,
            'periodo' => $recibo->periodo,
            'montoTotal' => $total,
            'montoPagado' => $pagado,
            'saldoPendiente' => max(0.0, $total - $pagado),
            'fechaLimite' => $this->fechaLimite->calcular($recibo->periodo->copy()),
        ];
    }

    // -----------------------------------------------------------------------
    // Morosos (US1)
    // -----------------------------------------------------------------------

    /**
     * @param array<int, int>|null $idsRama
     * @return Collection<int, object>
     */
    public function recibosMorosos(?string $tramo = null, ?array $idsRama = null): Collection
    {
        $hoy = Carbon::now()->startOfDay();

        return $this->recibosBaseParaPanel()
            ->map(fn (Recibo $recibo): object => $this->filaBase($recibo))
            ->filter(fn (object $fila): bool => $fila->saldoPendiente > 0.0 && $fila->fechaLimite->lt($hoy))
            ->map(function (object $fila) use ($hoy): object {
                $fila->diasDeAtraso = (int) $fila->fechaLimite->diffInDays($hoy);
                $fila->tramoAntiguedad = $this->tramoDeAntiguedad($fila->diasDeAtraso);

                return $fila;
            })
            ->when($tramo !== null, fn (Collection $c) => $c->where('tramoAntiguedad', $tramo))
            ->when(
                $idsRama !== null,
                fn (Collection $c) => $c->filter(fn (object $fila): bool => in_array($fila->locacion?->id, $idsRama, true)),
            )
            ->sortByDesc('diasDeAtraso')
            ->values();
    }

    public function tramoDeAntiguedad(int $dias): string
    {
        return match (true) {
            $dias <= 30 => '1-30',
            $dias <= 60 => '31-60',
            $dias <= 90 => '61-90',
            default => '90+',
        };
    }

    /**
     * @param Collection<int, object> $morosos
     * @return array{cantidadRecibos: int, cantidadInquilinos: int, montoAdeudadoVencido: float, porTramo: array<string, array{cantidad: int, monto: float}>}
     */
    public function resumenMorosidad(Collection $morosos): array
    {
        $porTramo = [];
        foreach (['1-30', '31-60', '61-90', '90+'] as $clave) {
            $delTramo = $morosos->where('tramoAntiguedad', $clave);
            $porTramo[$clave] = [
                'cantidad' => $delTramo->count(),
                'monto' => (float) $delTramo->sum('saldoPendiente'),
            ];
        }

        return [
            'cantidadRecibos' => $morosos->count(),
            'cantidadInquilinos' => $morosos->pluck('inquilinoId')->filter()->unique()->count(),
            'montoAdeudadoVencido' => (float) $morosos->sum('saldoPendiente'),
            'porTramo' => $porTramo,
        ];
    }

    // -----------------------------------------------------------------------
    // Próximos vencimientos (US2)
    // -----------------------------------------------------------------------

    /**
     * @return Collection<int, object>
     */
    public function proximosVencimientos(): Collection
    {
        $hoy = Carbon::now()->startOfDay();

        return $this->recibosBaseParaPanel()
            ->map(fn (Recibo $recibo): object => $this->filaBase($recibo))
            ->filter(fn (object $fila): bool => $fila->saldoPendiente > 0.0 && $fila->fechaLimite->gte($hoy))
            ->map(function (object $fila) use ($hoy): object {
                $fila->diasRestantes = (int) $hoy->diffInDays($fila->fechaLimite);

                return $fila;
            })
            ->sortBy(fn (object $fila) => $fila->fechaLimite->timestamp)
            ->values();
    }

    /**
     * @param Collection<int, object> $proximos
     * @return array{cantidad: int, montoTotal: float}
     */
    public function resumenProximos(Collection $proximos): array
    {
        return [
            'cantidad' => $proximos->count(),
            'montoTotal' => (float) $proximos->sum('saldoPendiente'),
        ];
    }

    // -----------------------------------------------------------------------
    // Indicadores del periodo (US3)
    // -----------------------------------------------------------------------

    /**
     * @return array{facturadoDelPeriodo: float, cobradoDeRecibosDelPeriodo: float, recaudadoEsteMes: float, tasaDeCobranza: float|null, carteraTotalPorCobrar: float}
     */
    public function indicadoresDelPeriodo(): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $base = $this->recibosBaseParaPanel();

        $delPeriodo = $base->filter(
            fn (Recibo $r): bool => $r->periodo->betweenIncluded($inicioMes, $finMes),
        );

        $facturado = (float) $delPeriodo->sum(
            fn (Recibo $r): float => (float) ($r->monto_renta ?? 0) + (float) ($r->suma_conceptos ?? 0),
        );
        $cobradoDelPeriodo = (float) $delPeriodo->sum(fn (Recibo $r): float => (float) ($r->suma_pagos ?? 0));

        $cartera = (float) $base->sum(function (Recibo $r): float {
            $total = (float) ($r->monto_renta ?? 0) + (float) ($r->suma_conceptos ?? 0);

            return max(0.0, $total - (float) ($r->suma_pagos ?? 0));
        });

        $recaudadoEsteMes = (float) Pago::query()
            ->whereBetween('fecha_pago', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->whereHas('recibo', fn ($q) => $q->where('estado', '!=', 'anulado'))
            ->sum('monto');

        return [
            'facturadoDelPeriodo' => $facturado,
            'cobradoDeRecibosDelPeriodo' => $cobradoDelPeriodo,
            'recaudadoEsteMes' => $recaudadoEsteMes,
            'tasaDeCobranza' => $facturado > 0.0 ? round($cobradoDelPeriodo / $facturado * 100, 1) : null,
            'carteraTotalPorCobrar' => $cartera,
        ];
    }

    // -----------------------------------------------------------------------
    // Contratos por vencer (US3)
    // -----------------------------------------------------------------------

    /**
     * @return array{dentro7: Collection<int, object>, dentro15: Collection<int, object>, dentro30: Collection<int, object>}
     */
    public function contratosPorVencer(): array
    {
        $hoy = Carbon::now()->startOfDay();
        $limite = $hoy->copy()->addDays(self::HITO_MAYOR_VENCIMIENTO);

        $contratos = Contrato::query()
            ->where('estado', 'activo')
            ->whereBetween('fecha_fin', [$hoy->toDateString(), $limite->toDateString()])
            ->with(['locacion', 'inquilinos'])
            ->orderBy('fecha_fin')
            ->get()
            ->map(fn (Contrato $contrato): object => (object) [
                'contrato' => $contrato,
                'inquilino' => $contrato->inquilinoPrincipal()?->nombreCompleto(),
                'locacion' => $contrato->locacion,
                'fechaFin' => $contrato->fecha_fin,
                'diasRestantes' => (int) $hoy->diffInDays($contrato->fecha_fin->copy()->startOfDay()),
            ]);

        return [
            'dentro7' => $contratos->where('diasRestantes', '<=', 7)->values(),
            'dentro15' => $contratos->where('diasRestantes', '<=', 15)->values(),
            'dentro30' => $contratos->values(),
        ];
    }
}

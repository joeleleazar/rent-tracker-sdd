<?php

namespace App\Http\Controllers;

use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioConstruccionArbolLocaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * specs/032: pantalla de seguimiento de pagos — mismo árbol de locaciones que
 * `RegistroMasivoRecibosController` (research.md Decisión 6), agregando por
 * locación el avance de pago de sus recibos vigentes del período
 * (research.md Decisión 7; contracts/vista-seguimiento-pagos.md).
 */
class SeguimientoPagosController extends Controller
{
    public function __construct(private readonly ServicioConstruccionArbolLocaciones $servicioArbol)
    {
    }

    public function index(Request $solicitud): View
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));
        $datos = $this->datosDelPeriodo($periodo);

        return view('pagos.seguimiento.index', [
            'raices' => $this->servicioArbol->construir(),
            'periodo' => $periodo,
            'montoPagadoPorLocacion' => $datos['montoPagadoPorLocacion'],
            'montoTotalPorLocacion' => $datos['montoTotalPorLocacion'],
            'cantidadRecibosPorLocacion' => $datos['cantidadRecibosPorLocacion'],
            'estadoAgregadoPorLocacion' => $datos['estadoAgregadoPorLocacion'],
        ]);
    }

    /**
     * @return array{montoPagadoPorLocacion: array<int, float>, montoTotalPorLocacion: array<int, float>, cantidadRecibosPorLocacion: array<int, int>, estadoAgregadoPorLocacion: array<int, string>}
     */
    private function datosDelPeriodo(Carbon $periodo): array
    {
        $idsAlquilables = Locacion::alquilables()->pluck('id');

        $recibosPorLocacion = Recibo::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->where('estado', '!=', 'anulado')
            ->with(['conceptos', 'pagos'])
            ->get()
            ->groupBy('locacion_id');

        $montoPagadoPorLocacion = [];
        $montoTotalPorLocacion = [];
        $cantidadRecibosPorLocacion = [];
        $estadoAgregadoPorLocacion = [];

        foreach ($idsAlquilables as $id) {
            $recibosVigentes = $recibosPorLocacion->get($id, collect());

            $cantidadRecibosPorLocacion[$id] = $recibosVigentes->count();

            if ($recibosVigentes->isEmpty()) {
                $estadoAgregadoPorLocacion[$id] = 'sin_recibos';

                continue;
            }

            $montoPagado = $recibosVigentes->sum(fn (Recibo $r) => $r->montoPagado());
            $montoTotal = $recibosVigentes->sum(fn (Recibo $r) => $r->total());

            $montoPagadoPorLocacion[$id] = $montoPagado;
            $montoTotalPorLocacion[$id] = $montoTotal;

            $estadoAgregadoPorLocacion[$id] = match (true) {
                $montoPagado <= 0.0 => 'sin_pagos',
                $montoPagado >= $montoTotal => 'pagado',
                default => 'parcial',
            };
        }

        return [
            'montoPagadoPorLocacion' => $montoPagadoPorLocacion,
            'montoTotalPorLocacion' => $montoTotalPorLocacion,
            'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
            'estadoAgregadoPorLocacion' => $estadoAgregadoPorLocacion,
        ];
    }

    private function resolverPeriodo(?string $periodo): Carbon
    {
        if ($periodo === null || $periodo === '') {
            return now()->startOfMonth();
        }

        return Carbon::parse($periodo)->startOfMonth();
    }
}

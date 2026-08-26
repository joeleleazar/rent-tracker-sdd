<?php

namespace App\Http\Controllers;

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioConstruccionArbolLocaciones;
use App\Services\ServicioGeneracionReciboPeriodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Registro masivo de recibos (specs/023): pantalla análoga a
 * RegistroMasivoLecturasController — un árbol de locaciones para un periodo,
 * donde cada locación con contrato activo muestra sus conceptos todavía no
 * cubiertos. specs/024: los conceptos ya no son un array fijo de 5 claves —
 * se leen del catálogo dinámico (`ConceptoGastoFijo`). specs/026: "Generar
 * Recibo" navega a la página individual de generación (locaciones.recibos.*)
 * en vez de abrir un modal propio de esta pantalla.
 */
class RegistroMasivoRecibosController extends Controller
{
    public function __construct(
        private readonly ServicioConstruccionArbolLocaciones $servicioArbol,
        private readonly ServicioGeneracionReciboPeriodo $servicioGeneracion,
    ) {
    }

    public function index(Request $solicitud): View
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));
        $datos = $this->datosDelPeriodo($periodo);

        return view('recibos.registro-masivo.index', [
            'raices' => $datos['raices'],
            'periodo' => $periodo,
            'contratosActivos' => $datos['contratosActivos'],
            'conceptosActivos' => $datos['conceptosActivos'],
            'conceptosDisponiblesPorLocacion' => $datos['conceptosDisponiblesPorLocacion'],
            'reciboQueCubrePorLocacion' => $datos['reciboQueCubrePorLocacion'],
            'cantidadRecibosPorLocacion' => $datos['cantidadRecibosPorLocacion'],
            'totalFacturadoPorLocacion' => $datos['totalFacturadoPorLocacion'],
            'tieneRecibosPorLocacion' => $datos['tieneRecibosPorLocacion'],
        ]);
    }

    /**
     * specs/026 US3: acceso directo a lo ya emitido para una locación y periodo, sin pasar
     * por los badges de concepto uno por uno. A diferencia del resto de esta pantalla, cuenta
     * y lista TODOS los recibos (incluidos los anulados) — es una vista de auditoría, no de
     * disponibilidad (contracts/ver-recibos-del-periodo.md).
     */
    public function recibosDelPeriodo(Request $solicitud, Locacion $locacion): RedirectResponse|View
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));

        $recibos = Recibo::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->with('conceptos.conceptoGastoFijo')
            ->orderBy('created_at')
            ->get();

        if ($recibos->isEmpty()) {
            return redirect()->route('recibos.registroMasivo.index', ['periodo' => $periodo->format('Y-m')]);
        }

        if ($recibos->count() === 1) {
            return redirect()->route('recibos.show', $recibos->first());
        }

        return view('recibos.registro-masivo.recibos-del-periodo', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'recibos' => $recibos,
        ]);
    }

    /**
     * Reúne locaciones/contratos activos/recibos del periodo con el mínimo de
     * consultas (mismo criterio anti-N+1 ya establecido en specs/018 para
     * RegistroMasivoLecturasController::datosDelPeriodo()). specs/024 US4:
     * cantidad y total facturado por locación se calculan sobre esta misma
     * colección ya agrupada, sin consultas adicionales (research.md Decisión 8).
     *
     * @return array{raices: array, contratosActivos: Collection, conceptosDisponiblesPorLocacion: array<int, Collection>, reciboQueCubrePorLocacion: array<int, Collection>, cantidadRecibosPorLocacion: array<int, int>, totalFacturadoPorLocacion: array<int, float>}
     */
    private function datosDelPeriodo(Carbon $periodo): array
    {
        $idsAlquilables = Locacion::alquilables()->pluck('id');

        $inicioDeMes = $periodo->copy()->startOfMonth();
        $finDeMes = $periodo->copy()->endOfMonth();

        $contratosActivos = Contrato::whereIn('locacion_id', $idsAlquilables)
            ->where('estado', '!=', 'rescindido')
            ->where('fecha_inicio', '<=', $finDeMes)
            ->where('fecha_fin', '>=', $inicioDeMes)
            ->orderByDesc('fecha_inicio')
            ->get()
            ->unique('locacion_id')
            ->keyBy('locacion_id');

        $recibosPorLocacion = Recibo::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->with('conceptos')
            ->get()
            ->groupBy('locacion_id');

        $conceptosActivos = ConceptoGastoFijo::activos()->ordenados()->get();

        $conceptosDisponiblesPorLocacion = [];
        $reciboQueCubrePorLocacion = [];
        $cantidadRecibosPorLocacion = [];
        $totalFacturadoPorLocacion = [];
        $tieneRecibosPorLocacion = [];

        foreach ($idsAlquilables as $id) {
            $recibosDeLaLocacion = $recibosPorLocacion->get($id, collect());
            // specs/026: un recibo anulado no cuenta como cobertura vigente — se filtra ANTES de
            // calcular disponibilidad/cobertura, no solo al totalizar (causa raíz del defecto
            // reportado con Local 101, ver research.md Decisión 1).
            $recibosVigentes = $recibosDeLaLocacion->where('estado', '!=', 'anulado');
            $conceptosDisponiblesPorLocacion[$id] = $this->servicioGeneracion->conceptosDisponiblesDesde($conceptosActivos, $recibosVigentes);
            $reciboQueCubrePorLocacion[$id] = $this->servicioGeneracion->reciboQueCubreDesde($conceptosActivos, $recibosVigentes);

            $cantidadRecibosPorLocacion[$id] = $recibosVigentes->count();
            $totalFacturadoPorLocacion[$id] = $recibosVigentes->sum(fn (Recibo $r) => $r->total());
            // specs/026 US3: a diferencia de lo anterior, "Ver Recibos" debe seguir ofreciéndose
            // aunque el único recibo del periodo esté anulado (auditoría, no disponibilidad) —
            // ver research.md Decisión 5.
            $tieneRecibosPorLocacion[$id] = $recibosDeLaLocacion->isNotEmpty();
        }

        return [
            'raices' => $this->servicioArbol->construir(),
            'contratosActivos' => $contratosActivos,
            'conceptosActivos' => $conceptosActivos,
            'conceptosDisponiblesPorLocacion' => $conceptosDisponiblesPorLocacion,
            'reciboQueCubrePorLocacion' => $reciboQueCubrePorLocacion,
            'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
            'totalFacturadoPorLocacion' => $totalFacturadoPorLocacion,
            'tieneRecibosPorLocacion' => $tieneRecibosPorLocacion,
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

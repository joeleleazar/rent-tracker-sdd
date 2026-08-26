<?php

namespace App\Http\Controllers;

use App\Exceptions\ConceptosReciboYaCubiertosException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Http\Requests\SolicitudGuardarReciboRegistroMasivo;
use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioCalculoProrrateoContrato;
use App\Services\ServicioConstruccionArbolLocaciones;
use App\Services\ServicioGeneracionReciboPeriodo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Registro masivo de recibos (specs/023): pantalla análoga a
 * RegistroMasivoLecturasController — un árbol de locaciones para un periodo,
 * donde cada locación con contrato activo muestra sus conceptos todavía no
 * cubiertos y permite generar un recibo desde un modal, sin salir de la
 * pantalla. specs/024: los conceptos ya no son un array fijo de 5 claves —
 * se leen del catálogo dinámico (`ConceptoGastoFijo`).
 */
class RegistroMasivoRecibosController extends Controller
{
    public function __construct(
        private readonly ServicioConstruccionArbolLocaciones $servicioArbol,
        private readonly ServicioGeneracionReciboPeriodo $servicioGeneracion,
        private readonly ServicioCalculoProrrateoContrato $servicioProrrateo,
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
        ]);
    }

    /**
     * FR-004/FR-005: contenido del modal de una locación — solo sus conceptos
     * todavía no cubiertos, con su monto sugerido (misma lógica que
     * `ReciboController::create()`, research.md Decisión 6).
     */
    public function modal(Request $solicitud, Locacion $locacion): View
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));
        $contratoActivo = $locacion->contratoActivoEnPeriodo($periodo);
        $conceptosDisponibles = $contratoActivo !== null
            ? $this->servicioGeneracion->conceptosDisponibles($locacion, $periodo)
            : collect();
        $prorrateo = $contratoActivo !== null ? $this->servicioProrrateo->calcular($contratoActivo, $periodo) : null;
        $lectura = $this->servicioGeneracion->lecturaDelPeriodo($locacion, $periodo);

        $montosSugeridos = collect();
        foreach ($conceptosDisponibles as $concepto) {
            if ($concepto->esRenta()) {
                $montosSugeridos->put($concepto->id, $prorrateo['monto_renta_sugerido'] ?? (float) $contratoActivo->monto_renta);
            } elseif ($concepto->esLuz()) {
                $montosSugeridos->put($concepto->id, $this->servicioGeneracion->calcularMontoLuzSugerido($lectura));
            } else {
                $montosSugeridos->put($concepto->id, $contratoActivo->valorDeConcepto($concepto) ?? 0.0);
            }
        }

        return view('recibos.registro-masivo.partials.modal-recibo', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'contratoActivo' => $contratoActivo,
            'conceptosDisponibles' => $conceptosDisponibles,
            'montosSugeridos' => $montosSugeridos,
            'prorrateo' => $prorrateo,
        ]);
    }

    /**
     * FR-007/FR-008: genera el recibo de inmediato (research.md Decisión 5) y
     * responde con la parcial de estado de la fila ya actualizada — nunca con
     * un redirect, porque siempre se llama por htmx desde el modal.
     */
    public function store(SolicitudGuardarReciboRegistroMasivo $solicitud, Locacion $locacion): View|Response
    {
        $datos = $solicitud->validated();
        $datos['incluye_alquiler'] = $solicitud->boolean('incluye_alquiler');

        $conceptos = [];
        foreach ($solicitud->validated('conceptos', []) as $conceptoId => $campos) {
            if (filter_var($campos['incluido'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $conceptos[(int) $conceptoId] = $campos['monto'];
            }
        }
        $datos['conceptos'] = $conceptos;

        $periodo = Carbon::parse($datos['periodo'])->startOfMonth();

        try {
            $this->servicioGeneracion->generar($locacion, $periodo, $datos);
        } catch (SinContratoActivoEnPeriodoException|ConceptosReciboYaCubiertosException $excepcion) {
            return response()->view('recibos.registro-masivo.partials.error-modal-recibo', [
                'mensaje' => $excepcion->getMessage(),
            ], 422);
        }

        $contratoActivo = $locacion->contratoActivoEnPeriodo($periodo);
        $conceptosActivos = ConceptoGastoFijo::activos()->ordenados()->get();
        $recibosDeLaLocacion = Recibo::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->with('conceptos')
            ->get();

        return view('recibos.registro-masivo.partials.estado-recibo-locacion', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'contratoActivo' => $contratoActivo,
            'conceptosActivos' => $conceptosActivos,
            'conceptosDisponibles' => $this->servicioGeneracion->conceptosDisponiblesDesde($conceptosActivos, $recibosDeLaLocacion),
            'reciboQueCubre' => $this->servicioGeneracion->reciboQueCubreDesde($conceptosActivos, $recibosDeLaLocacion),
            'cantidadRecibos' => $recibosDeLaLocacion->where('estado', '!=', 'anulado')->count(),
            'totalFacturado' => $recibosDeLaLocacion->where('estado', '!=', 'anulado')->sum(fn (Recibo $r) => $r->total()),
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

        foreach ($idsAlquilables as $id) {
            $recibosDeLaLocacion = $recibosPorLocacion->get($id, collect());
            $conceptosDisponiblesPorLocacion[$id] = $this->servicioGeneracion->conceptosDisponiblesDesde($conceptosActivos, $recibosDeLaLocacion);
            $reciboQueCubrePorLocacion[$id] = $this->servicioGeneracion->reciboQueCubreDesde($conceptosActivos, $recibosDeLaLocacion);

            $recibosVigentes = $recibosDeLaLocacion->where('estado', '!=', 'anulado');
            $cantidadRecibosPorLocacion[$id] = $recibosVigentes->count();
            $totalFacturadoPorLocacion[$id] = $recibosVigentes->sum(fn (Recibo $r) => $r->total());
        }

        return [
            'raices' => $this->servicioArbol->construir(),
            'contratosActivos' => $contratosActivos,
            'conceptosActivos' => $conceptosActivos,
            'conceptosDisponiblesPorLocacion' => $conceptosDisponiblesPorLocacion,
            'reciboQueCubrePorLocacion' => $reciboQueCubrePorLocacion,
            'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
            'totalFacturadoPorLocacion' => $totalFacturadoPorLocacion,
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

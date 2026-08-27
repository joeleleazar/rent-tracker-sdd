<?php

namespace App\Http\Controllers;

use App\Exceptions\CambioEstadoReciboRequiereConfirmacionException;
use App\Exceptions\ConceptosReciboYaCubiertosException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Http\Requests\SolicitudActualizarEstadoRecibo;
use App\Http\Requests\SolicitudGuardarRecibo;
use App\Models\BorradorRecibo;
use App\Models\ConceptoGastoFijo;
use App\Models\ConfiguracionGeneral;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioCalculoProrrateoContrato;
use App\Services\ServicioCambioEstadoRecibo;
use App\Services\ServicioGeneracionReciboPeriodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Rutas locación-céntricas (no contrato-céntricas): ver
 * specs/005-lecturas-medidor-recibo-periodo/research.md §1, aplicado directamente
 * en specs/004 (ver tasks.md de esa feature, sección Notes). Desde specs/005,
 * delega en ServicioGeneracionReciboPeriodo (conceptos seleccionables, monto de
 * luz sugerido, no-duplicación por periodo, @edit/@update).
 */
class ReciboController extends Controller
{
    public function __construct(
        private readonly ServicioGeneracionReciboPeriodo $servicioGeneracion,
        private readonly ServicioCambioEstadoRecibo $servicioCambioEstado,
        private readonly ServicioCalculoProrrateoContrato $servicioProrrateo,
    ) {
    }

    public function index(Locacion $locacion): View
    {
        return view('locaciones.recibos.index', [
            'locacion' => $locacion,
            'recibos' => $locacion->recibos()->orderByDesc('periodo')->get(),
        ]);
    }

    public function create(Locacion $locacion): View
    {
        $periodo = $this->resolverPeriodo(request()->query('periodo'));
        $contratoActivo = $locacion->contratoActivoEnPeriodo($periodo);
        $lectura = $this->servicioGeneracion->lecturaDelPeriodo($locacion, $periodo);
        $conceptosDisponibles = $this->servicioGeneracion->conceptosDisponibles($locacion, $periodo);
        $reciboQueCubre = $this->servicioGeneracion->reciboQueCubre($locacion, $periodo);
        $prorrateo = $contratoActivo !== null ? $this->servicioProrrateo->calcular($contratoActivo, $periodo) : null;
        $borrador = $this->borradorDe($locacion, $periodo);

        return view('locaciones.recibos.create', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'contratoActivo' => $contratoActivo,
            'lectura' => $lectura,
            'montoLuzSugerido' => $this->servicioGeneracion->calcularMontoLuzSugerido($lectura),
            'conceptosDisponibles' => $conceptosDisponibles,
            'reciboQueCubre' => $reciboQueCubre,
            'prorrateo' => $prorrateo,
            'borrador' => $borrador,
        ]);
    }

    public function store(SolicitudGuardarRecibo $solicitud, Locacion $locacion): RedirectResponse
    {
        $datos = $this->datosConConceptos($solicitud);
        $periodo = Carbon::parse($datos['periodo'])->startOfMonth();

        try {
            $recibo = $this->servicioGeneracion->generar($locacion, $periodo, $datos);
        } catch (SinContratoActivoEnPeriodoException|ConceptosReciboYaCubiertosException $excepcion) {
            return back()->withInput()->withErrors(['periodo' => $excepcion->getMessage()]);
        }

        $this->borradorDe($locacion, $periodo)?->delete();

        return redirect()->route('recibos.show', $recibo)
            ->with('mensaje', 'Recibo emitido correctamente.');
    }

    /**
     * specs/026: guarda (upsert) el avance en progreso de la generación de un
     * recibo para el usuario autenticado, sin exigir que esté completo — se
     * usa tanto desde el autoguardado pasivo (cada 120s) como desde el botón
     * "Guardar Borrador" (contracts/borrador-recibo.md).
     */
    public function guardarBorrador(Request $solicitud, Locacion $locacion): Response
    {
        $periodo = Carbon::parse($solicitud->input('periodo'))->startOfMonth();

        $conceptos = [];
        foreach ($solicitud->input('conceptos', []) as $conceptoId => $campos) {
            if (filter_var($campos['incluido'] ?? false, FILTER_VALIDATE_BOOLEAN) && is_numeric($campos['monto'] ?? null)) {
                $conceptos[(int) $conceptoId] = (float) $campos['monto'];
            }
        }

        BorradorRecibo::updateOrCreate(
            ['usuario_id' => Auth::id(), 'periodo' => $periodo->format('Y-m-d'), 'locacion_id' => $locacion->id],
            [
                'incluye_alquiler' => filter_var($solicitud->input('incluye_alquiler', false), FILTER_VALIDATE_BOOLEAN),
                'monto_renta' => is_numeric($solicitud->input('monto_renta')) ? $solicitud->input('monto_renta') : null,
                'fecha_emision' => $solicitud->input('fecha_emision') ?: null,
                'conceptos' => $conceptos,
            ],
        );

        return response('Borrador guardado a las ' . now()->format('H:i') . '.');
    }

    private function borradorDe(Locacion $locacion, Carbon $periodo): ?BorradorRecibo
    {
        return BorradorRecibo::where('usuario_id', Auth::id())
            ->where('periodo', $periodo->format('Y-m-d'))
            ->where('locacion_id', $locacion->id)
            ->first();
    }

    public function show(Recibo $recibo): View
    {
        $recibo->load(['locacion', 'contrato', 'conceptos.conceptoGastoFijo', 'pagos.registradoPor']);

        return view('locaciones.recibos.show', [
            'recibo' => $recibo,
        ]);
    }

    public function edit(Recibo $recibo): View
    {
        $recibo->load('conceptos.conceptoGastoFijo');
        $conceptosDisponibles = $this->servicioGeneracion->conceptosDisponibles($recibo->locacion, $recibo->periodo);

        // specs/029: Renta nunca vive en $recibo->conceptos (se guarda aparte, como monto_renta
        // directo en el recibo — specs/024 research.md Decisión 2), así que la unión de abajo con
        // "los conceptos que este recibo ya tiene" nunca la reincorpora por sí sola. Cuando el
        // propio recibo ya la incluye, conceptosDisponibles() la excluye (correcto: ya está
        // cubierta, por este mismo recibo) y hay que agregarla de vuelta explícitamente para que
        // el formulario pueda seguir editándola.
        $conceptosDelRecibo = $recibo->conceptos->pluck('conceptoGastoFijo');
        if ($recibo->monto_renta !== null) {
            $conceptosDelRecibo = $conceptosDelRecibo->push(ConceptoGastoFijo::where('clave', 'renta')->first());
        }

        return view('locaciones.recibos.edit', [
            'recibo' => $recibo,
            // El propio recibo puede seguir usando los conceptos que ya tiene (edición, no
            // generación) — se ofrecen los disponibles MÁS los que este recibo ya incluye.
            'conceptosDisponibles' => $conceptosDisponibles->concat($conceptosDelRecibo)
                ->unique('id')
                ->sortBy('orden')
                ->values(),
        ]);
    }

    public function update(SolicitudGuardarRecibo $solicitud, Recibo $recibo): RedirectResponse
    {
        $datos = $this->datosConConceptos($solicitud);

        try {
            $this->servicioGeneracion->actualizar($recibo, $datos);
        } catch (ConceptosReciboYaCubiertosException $excepcion) {
            return back()->withInput()->withErrors(['periodo' => $excepcion->getMessage()]);
        }

        return redirect()->route('recibos.show', $recibo)
            ->with('mensaje', 'Recibo actualizado correctamente.');
    }

    /**
     * Cambia el estado de pago del recibo (pendiente/pagado/anulado, US1 de
     * specs/007). Transiciones hacia/desde "anulado" exigen `confirmado=true`.
     */
    /**
     * specs/032: tras retirar el toggle manual Pendiente/Pagado (FR-006), esta
     * acción solo administra las 2 transiciones que siguen siendo manuales —
     * anular y reactivar (salir de "anulado") — despachando al método
     * correspondiente de ServicioCambioEstadoRecibo.
     */
    public function actualizarEstado(SolicitudActualizarEstadoRecibo $solicitud, Recibo $recibo): RedirectResponse
    {
        try {
            if ($solicitud->validated('nuevo_estado') === 'anulado') {
                $this->servicioCambioEstado->anular($recibo, $solicitud->boolean('confirmado'));
            } else {
                $this->servicioCambioEstado->reactivar($recibo, $solicitud->boolean('confirmado'));
            }
        } catch (CambioEstadoReciboRequiereConfirmacionException $excepcion) {
            return back()->withErrors(['estado' => $excepcion->getMessage()]);
        }

        return redirect()->route('recibos.show', $recibo)
            ->with('mensaje', 'Estado del recibo actualizado correctamente.');
    }

    /**
     * Comprobante único del recibo (US2/US3 de specs/007): misma vista para
     * impresión (`window.print()`) y para captura como imagen (`html2canvas` +
     * `navigator.share`), con marca "ANULADO" si corresponde (FR-009).
     */
    public function comprobante(Recibo $recibo): View
    {
        $recibo->load(['locacion', 'contrato', 'conceptos.conceptoGastoFijo']);

        return view('locaciones.recibos.comprobante', [
            'recibo' => $recibo,
            'nombrePropietario' => ConfiguracionGeneral::actual()->nombre_propietario,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosConConceptos(SolicitudGuardarRecibo $solicitud): array
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

        return $datos;
    }

    private function resolverPeriodo(?string $periodo): Carbon
    {
        if ($periodo === null || $periodo === '') {
            return now()->startOfMonth();
        }

        return Carbon::parse($periodo)->startOfMonth();
    }
}

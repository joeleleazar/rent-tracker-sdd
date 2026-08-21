<?php

namespace App\Http\Controllers;

use App\Exceptions\CambioEstadoReciboRequiereConfirmacionException;
use App\Exceptions\ReciboDuplicadoPeriodoException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Http\Requests\SolicitudActualizarEstadoRecibo;
use App\Http\Requests\SolicitudGuardarRecibo;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioCalculoProrrateoContrato;
use App\Services\ServicioCambioEstadoRecibo;
use App\Services\ServicioGeneracionReciboPeriodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
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
        $reciboExistente = Recibo::where('locacion_id', $locacion->id)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->first();
        $prorrateo = $contratoActivo !== null ? $this->servicioProrrateo->calcular($contratoActivo, $periodo) : null;

        return view('locaciones.recibos.create', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'contratoActivo' => $contratoActivo,
            'lectura' => $lectura,
            'montoLuzSugerido' => $this->servicioGeneracion->calcularMontoLuzSugerido($lectura),
            'reciboExistente' => $reciboExistente,
            'prorrateo' => $prorrateo,
        ]);
    }

    public function store(SolicitudGuardarRecibo $solicitud, Locacion $locacion): RedirectResponse
    {
        $datos = $this->datosConConceptos($solicitud);
        $periodo = Carbon::parse($datos['periodo'])->startOfMonth();

        try {
            $recibo = $this->servicioGeneracion->generar($locacion, $periodo, $datos);
        } catch (SinContratoActivoEnPeriodoException $excepcion) {
            return back()->withInput()->withErrors(['periodo' => $excepcion->getMessage()]);
        } catch (ReciboDuplicadoPeriodoException $excepcion) {
            return redirect()->route('recibos.edit', $excepcion->reciboExistente)
                ->withErrors(['periodo' => $excepcion->getMessage()]);
        }

        return redirect()->route('recibos.show', $recibo)
            ->with('mensaje', 'Recibo emitido correctamente.');
    }

    public function show(Recibo $recibo): View
    {
        $recibo->load(['locacion', 'contrato']);

        return view('locaciones.recibos.show', [
            'recibo' => $recibo,
        ]);
    }

    public function edit(Recibo $recibo): View
    {
        return view('locaciones.recibos.edit', [
            'recibo' => $recibo,
        ]);
    }

    public function update(SolicitudGuardarRecibo $solicitud, Recibo $recibo): RedirectResponse
    {
        $datos = $this->datosConConceptos($solicitud);

        $this->servicioGeneracion->actualizar($recibo, $datos);

        return redirect()->route('recibos.show', $recibo)
            ->with('mensaje', 'Recibo actualizado correctamente.');
    }

    /**
     * Cambia el estado de pago del recibo (pendiente/pagado/anulado, US1 de
     * specs/007). Transiciones hacia/desde "anulado" exigen `confirmado=true`.
     */
    public function actualizarEstado(SolicitudActualizarEstadoRecibo $solicitud, Recibo $recibo): RedirectResponse
    {
        try {
            $this->servicioCambioEstado->cambiar(
                $recibo,
                $solicitud->validated('nuevo_estado'),
                $solicitud->boolean('confirmado'),
            );
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
        $recibo->load(['locacion', 'contrato']);

        return view('locaciones.recibos.comprobante', [
            'recibo' => $recibo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosConConceptos(SolicitudGuardarRecibo $solicitud): array
    {
        $datos = $solicitud->validated();

        foreach (['incluye_alquiler', 'incluye_luz', 'incluye_agua', 'incluye_seguridad', 'incluye_pasadizo'] as $concepto) {
            $datos[$concepto] = $solicitud->boolean($concepto);
        }

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

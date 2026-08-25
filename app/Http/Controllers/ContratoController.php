<?php

namespace App\Http\Controllers;

use App\Exceptions\ContratoSinInquilinosException;
use App\Exceptions\ContratoSolapadoException;
use App\Exceptions\GarantiaDescuadreException;
use App\Exceptions\InquilinoPrincipalInvalidoException;
use App\Exceptions\InquilinoPrincipalSinReemplazoException;
use App\Exceptions\MotivoRetencionRequeridoException;
use App\Exceptions\ResolucionGarantiaRequiereConfirmacionException;
use App\Exceptions\UltimoInquilinoException;
use App\Http\Requests\SolicitudAsociarInquilino;
use App\Http\Requests\SolicitudGuardarContrato;
use App\Http\Requests\SolicitudGuardarCostosContrato;
use App\Http\Requests\SolicitudQuitarInquilino;
use App\Http\Requests\SolicitudRegistrarResolucionGarantia;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Services\ServicioAsociacionInquilinosContrato;
use App\Services\ServicioResolucionGarantiaContrato;
use App\Services\ServicioValidacionSolapamientoContrato;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContratoController extends Controller
{
    public function __construct(
        private readonly ServicioValidacionSolapamientoContrato $servicioValidacionSolapamiento,
        private readonly ServicioAsociacionInquilinosContrato $servicioInquilinos,
        private readonly ServicioResolucionGarantiaContrato $servicioResolucionGarantia,
    ) {
    }

    public function index(Locacion $locacion): View
    {
        return view('contratos.index', [
            'locacion' => $locacion,
            'contratos' => $locacion->contratos()->historialCronologico()->with('inquilinos')->get(),
        ]);
    }

    public function create(Locacion $locacion): View
    {
        return view('contratos.create', [
            'locacion' => $locacion,
        ]);
    }

    public function store(SolicitudGuardarContrato $solicitud, Locacion $locacion): RedirectResponse
    {
        $datos = $solicitud->validated();
        $inquilinosInput = $datos['inquilinos'] ?? [];
        $principalIndex = $datos['principal_index'] ?? null;
        unset($datos['inquilinos'], $datos['principal_index']);

        $inquilinosData = $this->prepararInquilinos($inquilinosInput, $principalIndex);
        $datos = $this->conEstadoGarantia($datos);

        try {
            $contrato = $this->servicioValidacionSolapamiento->validarYEjecutar(
                $locacion->id,
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                null,
                function () use ($locacion, $datos, $inquilinosData) {
                    $contrato = $locacion->contratos()->create($datos);
                    $this->servicioInquilinos->sincronizar($contrato, $inquilinosData);

                    return $contrato;
                },
            );
        } catch (ContratoSolapadoException $excepcion) {
            // specs/012, FR-002: se adjunta el contrato en conflicto (ya calculado por
            // la excepción) además del mensaje, para el modal de dos bloques — no
            // cambia el código de redirección ni la clave 'solapamiento' ya cubiertos
            // por tests (ver research.md §3). Se flashea un array plano (no el modelo
            // Eloquent) porque la sesión serializa/deserializa objetos como arrays.
            return back()->withInput()->withErrors(['solapamiento' => $excepcion->getMessage()])
                ->with('contratoEnConflicto', $this->datosContratoEnConflicto($excepcion->contratoEnConflicto));
        } catch (ContratoSinInquilinosException|InquilinoPrincipalInvalidoException $excepcion) {
            return back()->withInput()->withErrors(['inquilinos' => $excepcion->getMessage()]);
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Contrato registrado correctamente.');
    }

    public function show(Contrato $contrato): View
    {
        $contrato->load(['locacion', 'inquilinos', 'documentos']);

        return view('contratos.show', [
            'contrato' => $contrato,
        ]);
    }

    public function edit(Contrato $contrato): View
    {
        return view('contratos.edit', [
            'contrato' => $contrato,
        ]);
    }

    public function update(SolicitudGuardarContrato $solicitud, Contrato $contrato): RedirectResponse
    {
        $datos = $solicitud->validated();

        // Reinicio de hitos de notificación de vencimiento si cambia fecha_fin
        // (specs/004-condiciones-contrato-recibo, Edge Case "Corrección de fecha de
        // fin tras notificación enviada"; ver research.md §4).
        if ($contrato->fecha_fin->format('Y-m-d') !== $datos['fecha_fin']) {
            $datos['notificado_30_dias_en'] = null;
            $datos['notificado_15_dias_en'] = null;
            $datos['notificado_7_dias_en'] = null;
        }

        $datos = $this->conEstadoGarantia($datos, $contrato);

        try {
            $this->servicioValidacionSolapamiento->validarYEjecutar(
                $contrato->locacion_id,
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                $contrato->id,
                fn () => $contrato->update($datos),
            );
        } catch (ContratoSolapadoException $excepcion) {
            return back()->withInput()->withErrors(['solapamiento' => $excepcion->getMessage()])
                ->with('contratoEnConflicto', $this->datosContratoEnConflicto($excepcion->contratoEnConflicto));
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Contrato actualizado correctamente.');
    }

    /**
     * Edición rápida de los 4 costos fijos desde la vista de detalle del contrato,
     * sin tocar fechas/monto_renta/estado (specs/004, US1).
     */
    public function actualizarCostos(SolicitudGuardarCostosContrato $solicitud, Contrato $contrato): RedirectResponse
    {
        $contrato->update($solicitud->validated());

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Costos del contrato actualizados correctamente.');
    }

    /**
     * Asocia un inquilino (existente o nuevo) a un contrato ya persistido (US2),
     * gestionado de forma atómica desde la vista de detalle, igual que los documentos.
     */
    public function agregarInquilino(SolicitudAsociarInquilino $solicitud, Contrato $contrato): RedirectResponse
    {
        $this->servicioInquilinos->agregar($contrato, $solicitud->validated());

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Inquilino asociado al contrato correctamente.');
    }

    /**
     * Quita un inquilino de un contrato ya persistido, bloqueando la acción si es
     * el único asociado (FR-004) o si es el Principal y no se designó un
     * reemplazo entre los inquilinos restantes (FR-009).
     */
    public function quitarInquilino(SolicitudQuitarInquilino $solicitud, Contrato $contrato, Inquilino $inquilino): RedirectResponse
    {
        try {
            $this->servicioInquilinos->quitar($contrato, $inquilino, $solicitud->validated('nuevo_principal_id'));
        } catch (UltimoInquilinoException|InquilinoPrincipalSinReemplazoException $excepcion) {
            return back()->withErrors(['inquilinos' => $excepcion->getMessage()]);
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Inquilino removido del contrato correctamente.');
    }

    /**
     * Registra la resolución (devolución/retención) de la garantía de un
     * contrato ya entregada (specs/009, US3).
     */
    public function registrarResolucionGarantia(SolicitudRegistrarResolucionGarantia $solicitud, Contrato $contrato): RedirectResponse
    {
        try {
            $this->servicioResolucionGarantia->registrar(
                $contrato,
                (float) $solicitud->validated('monto_devuelto_garantia'),
                (float) $solicitud->validated('monto_retenido_garantia'),
                $solicitud->validated('motivo_retencion_garantia'),
                $solicitud->boolean('confirmado'),
            );
        } catch (ResolucionGarantiaRequiereConfirmacionException|MotivoRetencionRequeridoException|GarantiaDescuadreException $excepcion) {
            return back()->withInput()->withErrors(['garantia' => $excepcion->getMessage()]);
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Resolución de garantía registrada correctamente.');
    }

    /**
     * Datos del contrato en conflicto para el modal de solapamiento (specs/012,
     * FR-002). Se devuelve un array plano (no el modelo Eloquent) porque la
     * sesión de Laravel serializa/deserializa objetos flasheados como arrays
     * al pasar de una petición a la siguiente — ver research.md §3.
     *
     * @return array{fecha_inicio: string, fecha_fin: string, inquilino_nombre: string, monto_renta: string, contrato_id: int}
     */
    private function datosContratoEnConflicto(Contrato $contrato): array
    {
        return [
            'contrato_id' => $contrato->id,
            'fecha_inicio' => $contrato->fecha_inicio->format('d/m/Y'),
            'fecha_fin' => $contrato->fecha_fin->format('d/m/Y'),
            'inquilino_nombre' => $contrato->inquilinoPrincipal()?->nombreCompleto() ?? '—',
            'monto_renta' => number_format((float) $contrato->monto_renta, 2),
        ];
    }

    /**
     * Deriva `estado_garantia` a partir de `monto_garantia` (specs/009, FR-001):
     * "entregada" por defecto al registrar un monto > 0 por primera vez; se
     * preserva "resuelta" si ya existía (la resolución se gestiona únicamente vía
     * `registrarResolucionGarantia`, no desde este formulario general); se limpia
     * a null si el monto queda en 0/vacío (Edge Case "garantía con monto cero").
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function conEstadoGarantia(array $datos, ?Contrato $contrato = null): array
    {
        $montoGarantia = (float) ($datos['monto_garantia'] ?? 0);

        if ($montoGarantia <= 0) {
            $datos['estado_garantia'] = null;
        } elseif ($contrato === null || $contrato->estado_garantia === null) {
            $datos['estado_garantia'] = 'entregada';
        }

        return $datos;
    }

    /**
     * @param array<int, array<string, mixed>> $inquilinosInput
     * @return array<int, array<string, mixed>>
     */
    private function prepararInquilinos(array $inquilinosInput, ?int $principalIndex): array
    {
        $inquilinos = [];

        foreach (array_values($inquilinosInput) as $indice => $datos) {
            $datos['es_principal'] = $principalIndex !== null
                ? $indice === $principalIndex
                : $indice === 0;

            $inquilinos[] = $datos;
        }

        return $inquilinos;
    }
}

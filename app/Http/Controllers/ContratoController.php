<?php

namespace App\Http\Controllers;

use App\Exceptions\ContratoSolapadoException;
use App\Http\Requests\SolicitudGuardarContrato;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Services\ServicioValidacionSolapamientoContrato;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContratoController extends Controller
{
    public function __construct(
        private readonly ServicioValidacionSolapamientoContrato $servicioValidacionSolapamiento,
    ) {
    }

    public function index(Locacion $locacion): View
    {
        return view('contratos.index', [
            'locacion' => $locacion,
            'contratos' => $locacion->contratos()->historialCronologico()->with('inquilino')->get(),
        ]);
    }

    public function create(Locacion $locacion): View
    {
        return view('contratos.create', [
            'locacion' => $locacion,
            'inquilinos' => Inquilino::orderBy('nombre')->get(),
        ]);
    }

    public function store(SolicitudGuardarContrato $solicitud, Locacion $locacion): RedirectResponse
    {
        $datos = $solicitud->validated();

        try {
            $contrato = $this->servicioValidacionSolapamiento->validarYEjecutar(
                $locacion->id,
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                null,
                fn () => $locacion->contratos()->create($datos),
            );
        } catch (ContratoSolapadoException $excepcion) {
            return back()->withInput()->withErrors(['solapamiento' => $excepcion->getMessage()]);
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Contrato registrado correctamente.');
    }

    public function show(Contrato $contrato): View
    {
        $contrato->load(['locacion', 'inquilino', 'documentos']);

        return view('contratos.show', [
            'contrato' => $contrato,
        ]);
    }

    public function edit(Contrato $contrato): View
    {
        return view('contratos.edit', [
            'contrato' => $contrato,
            'inquilinos' => Inquilino::orderBy('nombre')->get(),
        ]);
    }

    public function update(SolicitudGuardarContrato $solicitud, Contrato $contrato): RedirectResponse
    {
        $datos = $solicitud->validated();

        try {
            $this->servicioValidacionSolapamiento->validarYEjecutar(
                $contrato->locacion_id,
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                $contrato->id,
                fn () => $contrato->update($datos),
            );
        } catch (ContratoSolapadoException $excepcion) {
            return back()->withInput()->withErrors(['solapamiento' => $excepcion->getMessage()]);
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Contrato actualizado correctamente.');
    }
}

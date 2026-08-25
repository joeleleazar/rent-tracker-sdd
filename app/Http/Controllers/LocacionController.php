<?php

namespace App\Http\Controllers;

use App\Exceptions\LocacionCicloException;
use App\Exceptions\LocacionConHijasException;
use App\Http\Requests\SolicitudGuardarLocacion;
use App\Models\Locacion;
use App\Services\ServicioConstruccionArbolLocaciones;
use App\Services\ServicioValidacionJerarquiaLocacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocacionController extends Controller
{
    public function __construct(
        private readonly ServicioValidacionJerarquiaLocacion $servicioValidacionJerarquia,
        private readonly ServicioConstruccionArbolLocaciones $servicioArbol,
    ) {
    }

    /**
     * Vista unificada de árbol jerárquico (specs/013-arbol-jerarquico-locaciones):
     * muestra todas las locaciones (alquilables y contenedoras), reemplazando el
     * listado plano filtrado que existía antes y al listado general de /dashboard.
     */
    public function index(): View
    {
        return view('locaciones.index', [
            'raices' => $this->servicioArbol->construir(),
        ]);
    }

    /**
     * Acepta el query param opcional `locacion_padre_id` (specs/013-arbol-jerarquico-locaciones,
     * FR-011: acción rápida "Agregar" desde una fila de la tabla jerárquica) para
     * preseleccionar la locación padre en el formulario.
     */
    public function create(Request $solicitud): View
    {
        return view('locaciones.create', [
            'locaciones' => Locacion::orderBy('nombre')->get(),
            'locacionPadreId' => $solicitud->integer('locacion_padre_id') ?: null,
        ]);
    }

    public function store(SolicitudGuardarLocacion $solicitud): RedirectResponse
    {
        $datos = $solicitud->validated();

        try {
            $locacion = $this->servicioValidacionJerarquia->validarYEjecutar(
                null,
                $datos['locacion_padre_id'] ?? null,
                fn () => Locacion::create($datos),
            );
        } catch (LocacionCicloException $excepcion) {
            return back()->withInput()->withErrors(['locacion_padre_id' => $excepcion->getMessage()]);
        }

        return redirect()->route('locaciones.show', $locacion)
            ->with('mensaje', 'Locación registrada correctamente.');
    }

    public function show(Locacion $locacion): View
    {
        return view('locaciones.show', [
            'locacion' => $locacion,
        ]);
    }

    public function edit(Locacion $locacion): View
    {
        return view('locaciones.edit', [
            'locacion' => $locacion,
            'locaciones' => Locacion::where('id', '!=', $locacion->id)->orderBy('nombre')->get(),
        ]);
    }

    public function update(SolicitudGuardarLocacion $solicitud, Locacion $locacion): RedirectResponse
    {
        $datos = $solicitud->validated();

        try {
            $this->servicioValidacionJerarquia->validarYEjecutar(
                $locacion,
                $datos['locacion_padre_id'] ?? null,
                fn () => $locacion->update($datos),
            );
        } catch (LocacionCicloException $excepcion) {
            return back()->withInput()->withErrors(['locacion_padre_id' => $excepcion->getMessage()]);
        }

        return redirect()->route('locaciones.show', $locacion)
            ->with('mensaje', 'Locación actualizada correctamente.');
    }

    public function destroy(Locacion $locacion): RedirectResponse
    {
        try {
            $this->servicioValidacionJerarquia->eliminar($locacion);
        } catch (LocacionConHijasException $excepcion) {
            return back()->withErrors(['eliminar' => $excepcion->getMessage()]);
        }

        return redirect()->route('locaciones.index')
            ->with('mensaje', 'Locación eliminada correctamente.');
    }
}

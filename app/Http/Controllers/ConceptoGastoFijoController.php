<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudGuardarConceptoGastoFijo;
use App\Models\ConceptoGastoFijo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD del catálogo dinámico de conceptos de gasto fijo (specs/024, US1) —
 * reemplaza los 5 conceptos antes codificados como columnas fijas de
 * `Contrato`/`Recibo`. "Renta" (`clave='renta'`) es un concepto protegido:
 * no puede desactivarse ni eliminarse (FR-002). Ningún concepto puede
 * eliminarse (solo desactivarse) si ya está en uso en algún contrato o
 * recibo (FR-003).
 */
class ConceptoGastoFijoController extends Controller
{
    public function index(): View
    {
        $conceptos = ConceptoGastoFijo::ordenados()
            ->withCount(['valoresConcepto as contratos_en_uso', 'reciboConceptos as recibos_en_uso'])
            ->get();

        return view('conceptos-gasto-fijo.index', [
            'conceptos' => $conceptos,
        ]);
    }

    public function create(): View
    {
        return view('conceptos-gasto-fijo.create');
    }

    public function store(SolicitudGuardarConceptoGastoFijo $solicitud): RedirectResponse
    {
        ConceptoGastoFijo::create([
            'nombre' => $solicitud->validated('nombre'),
            'orden' => $solicitud->validated('orden'),
        ]);

        return redirect()->route('conceptosGastoFijo.index')
            ->with('mensaje', 'Concepto creado correctamente.');
    }

    public function edit(ConceptoGastoFijo $conceptosGastoFijo): View
    {
        return view('conceptos-gasto-fijo.edit', [
            'concepto' => $conceptosGastoFijo,
        ]);
    }

    public function update(SolicitudGuardarConceptoGastoFijo $solicitud, ConceptoGastoFijo $conceptosGastoFijo): RedirectResponse
    {
        $activo = $solicitud->boolean('activo');

        if ($conceptosGastoFijo->esProtegido() && ! $activo) {
            return back()->withInput()->withErrors([
                'activo' => 'Este concepto no puede desactivarse: el sistema depende de que esté siempre disponible.',
            ]);
        }

        $conceptosGastoFijo->update([
            'nombre' => $solicitud->validated('nombre'),
            'orden' => $solicitud->validated('orden'),
            'activo' => $activo,
        ]);

        return redirect()->route('conceptosGastoFijo.index')
            ->with('mensaje', 'Concepto actualizado correctamente.');
    }

    public function destroy(ConceptoGastoFijo $conceptosGastoFijo): RedirectResponse
    {
        if ($conceptosGastoFijo->esProtegido()) {
            return back()->withErrors([
                'eliminar' => 'Este concepto no puede eliminarse.',
            ]);
        }

        $enUso = $conceptosGastoFijo->valoresConcepto()->count() + $conceptosGastoFijo->reciboConceptos()->count();

        if ($enUso > 0) {
            return back()->withErrors([
                'eliminar' => "Este concepto está en uso en {$enUso} registro(s) (contratos o recibos) y no puede eliminarse. Puede desactivarlo en su lugar.",
            ]);
        }

        $conceptosGastoFijo->delete();

        return redirect()->route('conceptosGastoFijo.index')
            ->with('mensaje', 'Concepto eliminado correctamente.');
    }
}

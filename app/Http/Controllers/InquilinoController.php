<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudGuardarInquilino;
use App\Models\Inquilino;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InquilinoController extends Controller
{
    /**
     * Busca inquilinos en el directorio global por DNI o apellidos (FR-007),
     * usado por el buscador de la vista de contrato para evitar duplicados.
     */
    public function buscar(Request $solicitud): JsonResponse
    {
        $termino = trim((string) $solicitud->query('q', ''));

        if ($termino === '') {
            return response()->json(['inquilinos' => []]);
        }

        $inquilinos = Inquilino::query()
            ->where('dni', 'ILIKE', "%{$termino}%")
            ->orWhere('apellidos', 'ILIKE', "%{$termino}%")
            ->orderBy('apellidos')
            ->limit(10)
            ->get(['id', 'apellidos', 'nombres', 'dni', 'fecha_nacimiento']);

        return response()->json(['inquilinos' => $inquilinos]);
    }

    /**
     * Registra un nuevo inquilino directamente en el directorio global.
     */
    public function store(SolicitudGuardarInquilino $solicitud): RedirectResponse
    {
        Inquilino::create($solicitud->validated());

        return back()->with('mensaje', 'Inquilino registrado correctamente en el directorio.');
    }
}

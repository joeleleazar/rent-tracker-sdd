<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudGuardarRepresentante;
use App\Models\Representante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RepresentanteController extends Controller
{
    /**
     * Busca representantes en el directorio global por DNI o apellidos (FR-007),
     * usado por el buscador de la vista de contrato para evitar duplicados.
     */
    public function buscar(Request $solicitud): JsonResponse
    {
        $termino = trim((string) $solicitud->query('q', ''));

        if ($termino === '') {
            return response()->json(['representantes' => []]);
        }

        $representantes = Representante::query()
            ->where('dni', 'ILIKE', "%{$termino}%")
            ->orWhere('apellidos', 'ILIKE', "%{$termino}%")
            ->orderBy('apellidos')
            ->limit(10)
            ->get(['id', 'apellidos', 'nombres', 'dni', 'fecha_nacimiento']);

        return response()->json(['representantes' => $representantes]);
    }

    /**
     * Registra un nuevo representante directamente en el directorio global.
     */
    public function store(SolicitudGuardarRepresentante $solicitud): RedirectResponse
    {
        Representante::create($solicitud->validated());

        return back()->with('mensaje', 'Representante registrado correctamente en el directorio.');
    }
}

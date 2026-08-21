<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudActualizarConfiguracionGeneral;
use App\Models\ConfiguracionGeneral;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracionGeneralController extends Controller
{
    public function edit(): View
    {
        return view('configuracion.edit', [
            'configuracion' => ConfiguracionGeneral::actual(),
        ]);
    }

    public function update(SolicitudActualizarConfiguracionGeneral $solicitud): RedirectResponse
    {
        ConfiguracionGeneral::actual()->update($solicitud->validated());

        return redirect()->route('configuracion.edit')
            ->with('mensaje', 'Configuración general actualizada correctamente.');
    }
}

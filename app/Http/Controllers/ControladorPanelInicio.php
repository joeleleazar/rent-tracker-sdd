<?php

namespace App\Http\Controllers;

use App\Models\Locacion;
use App\Services\ServicioJerarquiaLocaciones;
use App\Services\ServicioPanelCobranza;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * specs/043: panel de inicio de solo lectura. Vive bajo la pila
 * `['auth', 'cuenta.activa']` (sin `perfil.master`), por lo que Master y
 * Administrador lo ven por igual. No expone ninguna acción de escritura: todos
 * los enlaces llevan a pantallas existentes (detalle de recibo, detalle de
 * contrato).
 */
class ControladorPanelInicio extends Controller
{
    /** Valores válidos para el filtro por tramo de antigüedad del atraso. */
    private const TRAMOS = ['1-30', '31-60', '61-90', '90+'];

    public function __construct(
        private readonly ServicioPanelCobranza $servicio,
        private readonly ServicioJerarquiaLocaciones $jerarquia,
    ) {
    }

    public function index(Request $solicitud): View
    {
        $tramo = $this->tramoValido($solicitud->query('tramo'));
        $locacionFiltro = $this->locacionValida($solicitud->query('locacion'));
        $idsRama = $locacionFiltro !== null ? $this->jerarquia->idsDeRama($locacionFiltro->id) : null;

        $morosos = $this->servicio->recibosMorosos($tramo, $idsRama);
        $proximos = $this->servicio->proximosVencimientos();

        return view('panel.inicio', [
            'morosos' => $morosos,
            'resumenMorosidad' => $this->servicio->resumenMorosidad($morosos),
            'filtros' => [
                'tramo' => $tramo,
                'locacion' => $locacionFiltro?->id,
                'hayFiltro' => $tramo !== null || $locacionFiltro !== null,
                'locacionesDisponibles' => Locacion::query()->orderBy('nombre')->get(['id', 'nombre', 'locacion_padre_id']),
            ],
            'proximos' => $proximos,
            'resumenProximos' => $this->servicio->resumenProximos($proximos),
            'indicadores' => $this->servicio->indicadoresDelPeriodo(),
            'contratosPorVencer' => $this->servicio->contratosPorVencer(),
        ]);
    }

    private function tramoValido(?string $valor): ?string
    {
        return in_array($valor, self::TRAMOS, true) ? $valor : null;
    }

    private function locacionValida(mixed $valor): ?Locacion
    {
        if ($valor === null || $valor === '' || ! ctype_digit((string) $valor)) {
            return null;
        }

        return Locacion::find((int) $valor);
    }
}

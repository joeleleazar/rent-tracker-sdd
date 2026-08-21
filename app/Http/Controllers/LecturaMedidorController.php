<?php

namespace App\Http\Controllers;

use App\Exceptions\ConsumoNegativoSinConfirmarException;
use App\Exceptions\LecturaMedidorDuplicadaException;
use App\Http\Requests\SolicitudGuardarLecturaMedidor;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioCalculoConsumoMedidor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LecturaMedidorController extends Controller
{
    public function __construct(
        private readonly ServicioCalculoConsumoMedidor $servicioConsumo,
    ) {
    }

    /**
     * Historial de lecturas de la locación, ordenado cronológicamente, con
     * lectura_anterior/lectura_actual/consumo y advertencia de discrepancia con el
     * periodo siguiente (specs/006, US3), y el recibo asociado a cada periodo (si
     * existe, de specs/005).
     */
    public function index(Locacion $locacion): View
    {
        $lecturas = $locacion->lecturasMedidor()->orderByDesc('periodo')->get();
        $recibosPorPeriodo = $locacion->recibos()->get()->keyBy(fn (Recibo $recibo) => $recibo->periodo->format('Y-m-d'));

        return view('locaciones.lecturas.index', [
            'locacion' => $locacion,
            'lecturas' => $lecturas,
            'recibosPorPeriodo' => $recibosPorPeriodo,
        ]);
    }

    public function create(Locacion $locacion): View|RedirectResponse
    {
        $periodo = $this->resolverPeriodo(request()->query('periodo'));

        $existente = $locacion->lecturasMedidor()->where('periodo', $periodo->format('Y-m-d'))->first();

        if ($existente !== null) {
            return redirect()->route('lecturas.edit', $existente);
        }

        $lecturaAnteriorSugerida = $this->servicioConsumo->sugerirLecturaAnterior($locacion, $periodo->format('Y-m-d'));

        return view('locaciones.lecturas.create', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'lecturaAnteriorSugerida' => $lecturaAnteriorSugerida,
        ]);
    }

    public function store(SolicitudGuardarLecturaMedidor $solicitud, Locacion $locacion): RedirectResponse
    {
        $datos = $solicitud->validated();
        $periodo = Carbon::parse($datos['periodo'])->startOfMonth();
        $confirmado = $solicitud->boolean('confirmar_consumo_negativo');
        $lecturaAnterior = isset($datos['lectura_anterior']) && $datos['lectura_anterior'] !== '' && $datos['lectura_anterior'] !== null
            ? (float) $datos['lectura_anterior']
            : null;

        try {
            $lectura = DB::transaction(function () use ($locacion, $periodo, $datos, $confirmado, $lecturaAnterior) {
                $existente = $locacion->lecturasMedidor()->where('periodo', $periodo->format('Y-m-d'))->first();

                if ($existente !== null) {
                    throw new LecturaMedidorDuplicadaException($existente);
                }

                $consumo = $this->servicioConsumo->calcularConsumo($lecturaAnterior, (float) $datos['lectura_actual']);

                if ($consumo !== null && $consumo < 0 && ! $confirmado) {
                    throw new ConsumoNegativoSinConfirmarException();
                }

                return LecturaMedidor::create([
                    'locacion_id' => $locacion->id,
                    'periodo' => $periodo->format('Y-m-d'),
                    'lectura_anterior' => $lecturaAnterior,
                    'lectura_actual' => $datos['lectura_actual'],
                    'consumo_calculado' => $consumo,
                    'fecha_registro' => now(),
                ]);
            });
        } catch (LecturaMedidorDuplicadaException $excepcion) {
            return redirect()->route('lecturas.edit', $excepcion->lecturaExistente)
                ->withErrors(['lectura_actual' => $excepcion->getMessage()]);
        } catch (ConsumoNegativoSinConfirmarException $excepcion) {
            return back()->withInput()->withErrors(['lectura_actual' => $excepcion->getMessage()]);
        }

        return redirect()->route('locaciones.lecturas.index', $locacion)
            ->with('mensaje', 'Lectura del medidor registrada correctamente.');
    }

    public function edit(LecturaMedidor $lectura): View
    {
        $reciboEmitido = Recibo::where('locacion_id', $lectura->locacion_id)
            ->where('periodo', $lectura->periodo->format('Y-m-d'))
            ->first();

        return view('locaciones.lecturas.edit', [
            'lectura' => $lectura,
            'reciboEmitido' => $reciboEmitido,
        ]);
    }

    public function update(SolicitudGuardarLecturaMedidor $solicitud, LecturaMedidor $lectura): RedirectResponse
    {
        $datos = $solicitud->validated();
        $confirmado = $solicitud->boolean('confirmar_consumo_negativo');
        $lecturaAnterior = isset($datos['lectura_anterior']) && $datos['lectura_anterior'] !== '' && $datos['lectura_anterior'] !== null
            ? (float) $datos['lectura_anterior']
            : null;

        try {
            DB::transaction(function () use ($lectura, $datos, $confirmado, $lecturaAnterior) {
                $consumo = $this->servicioConsumo->calcularConsumo($lecturaAnterior, (float) $datos['lectura_actual']);

                if ($consumo !== null && $consumo < 0 && ! $confirmado) {
                    throw new ConsumoNegativoSinConfirmarException();
                }

                $lectura->update([
                    'lectura_anterior' => $lecturaAnterior,
                    'lectura_actual' => $datos['lectura_actual'],
                    'consumo_calculado' => $consumo,
                ]);
            });
        } catch (ConsumoNegativoSinConfirmarException $excepcion) {
            return back()->withInput()->withErrors(['lectura_actual' => $excepcion->getMessage()]);
        }

        return redirect()->route('locaciones.lecturas.index', $lectura->locacion)
            ->with('mensaje', 'Lectura del medidor actualizada correctamente.');
    }

    private function resolverPeriodo(?string $periodo): Carbon
    {
        if ($periodo === null || $periodo === '') {
            return now()->startOfMonth();
        }

        return Carbon::parse($periodo)->startOfMonth();
    }
}

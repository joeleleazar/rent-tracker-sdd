<?php

namespace App\Http\Controllers;

use App\Exceptions\ConsumoNegativoSinConfirmarException;
use App\Exceptions\LecturaMedidorDuplicadaException;
use App\Exports\ExportacionRegistroMasivoLecturas;
use App\Http\Requests\SolicitudActualizarTarifaRegistroMasivo;
use App\Http\Requests\SolicitudGuardarLecturaMedidor;
use App\Http\Requests\SolicitudGuardarRegistroMasivoLecturas;
use App\Models\BorradorLecturaMedidor;
use App\Models\ConfiguracionGeneral;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Services\ServicioCalculoConsumoMedidor;
use App\Services\ServicioConstruccionArbolLocaciones;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Registro masivo de lecturas de luz (specs/015): vía adicional al flujo
 * individual ya existente en LecturaMedidorController, para completar varias
 * locaciones a la vez desde la misma pantalla, con autoguardado de borrador,
 * totalizado por consumo (FR-013/FR-014/FR-015), exportación a Excel/PDF
 * (FR-016) y edición en línea de una lectura ya registrada (FR-005/FR-017).
 */
class RegistroMasivoLecturasController extends Controller
{
    public function __construct(
        private readonly ServicioConstruccionArbolLocaciones $servicioArbol,
        private readonly ServicioCalculoConsumoMedidor $servicioConsumo,
    ) {
    }

    public function index(Request $solicitud): View
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));
        $datos = $this->datosDelPeriodo($periodo);

        $borradores = BorradorLecturaMedidor::where('usuario_id', Auth::id())
            ->where('periodo', $periodo->format('Y-m-d'))
            ->get()
            ->keyBy('locacion_id');

        return view('lecturas.registro-masivo.index', [
            'raices' => $datos['raices'],
            'periodo' => $periodo,
            'lecturasDelPeriodo' => $datos['lecturasDelPeriodo'],
            'lecturasAnteriores' => $datos['lecturasAnteriores'],
            'borradores' => $borradores,
            'tarifa' => $datos['tarifa'],
        ]);
    }

    public function store(SolicitudGuardarRegistroMasivoLecturas $solicitud): RedirectResponse
    {
        $periodo = Carbon::parse($solicitud->validated('periodo'))->startOfMonth();
        $filas = $solicitud->input('lecturas', []);

        $guardadas = 0;
        $errores = [];

        $idsLocaciones = array_keys($filas);

        // specs/018 (FR-001/FR-002): batch-fetch de locaciones, lecturas del
        // periodo y lecturas anteriores ANTES del foreach, mismo patrón ya usado
        // en datosDelPeriodo() (líneas ~297-309), en vez de una consulta por fila.
        $locaciones = Locacion::whereIn('id', $idsLocaciones)->get()->keyBy('id');

        $lecturasDelPeriodo = LecturaMedidor::whereIn('locacion_id', $idsLocaciones)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->get()
            ->keyBy('locacion_id');

        $lecturasAnteriores = LecturaMedidor::whereIn('locacion_id', $idsLocaciones)
            ->where('periodo', '<', $periodo->format('Y-m-d'))
            ->orderByDesc('periodo')
            ->get()
            ->unique('locacion_id')
            ->keyBy('locacion_id');

        $tarifa = (float) ConfiguracionGeneral::actual()->tarifa_luz_por_unidad;

        foreach ($filas as $locacionId => $datosFila) {
            $valorActual = $datosFila['lectura_actual'] ?? null;

            if ($valorActual === null || $valorActual === '') {
                continue;
            }

            if (! is_numeric($valorActual) || (float) $valorActual < 0) {
                $errores["lecturas.{$locacionId}.lectura_actual"] = 'La lectura debe ser un número mayor o igual a 0.';

                continue;
            }

            $locacion = $locaciones->get((int) $locacionId);

            if ($locacion === null || ! $locacion->es_alquilable) {
                continue;
            }

            $confirmado = filter_var($datosFila['confirmar_consumo_negativo'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $lecturaAnteriorRegistrada = $lecturasAnteriores->get($locacion->id)?->lectura_actual;
            $lecturaAnterior = $lecturaAnteriorRegistrada !== null ? (float) $lecturaAnteriorRegistrada : null;
            // specs/019 FR-001: exclusivo del registro masivo — sin lectura anterior *registrada*,
            // el CONSUMO se calcula usando 0 en vez de quedar sin dato. `lectura_anterior` se sigue
            // persistiendo como null en ese caso (no se fabrica un "0" histórico que no existió,
            // ver research.md) — eso preservaría el comportamiento ya esperado de
            // `discrepanciaConSiguiente()` y de la columna "Lectura Periodo Anterior", que ya
            // resuelven "sin dato" consultando la lectura previa real, no esta columna.
            $lecturaAnteriorParaConsumo = $lecturaAnterior ?? 0.0;

            try {
                $existente = $lecturasDelPeriodo->get($locacion->id);

                if ($existente !== null) {
                    throw new LecturaMedidorDuplicadaException($existente);
                }

                $consumo = $this->servicioConsumo->calcularConsumo($lecturaAnteriorParaConsumo, (float) $valorActual);

                if ($consumo !== null && $consumo < 0 && ! $confirmado) {
                    throw new ConsumoNegativoSinConfirmarException();
                }

                // specs/019 FR-003/FR-004 (research.md Decisión 2): el total ya llega calculado
                // por el navegador (editado o no); si no llega numérico (JS deshabilitado), se
                // recalcula el mismo sugerido que el navegador habría mostrado. Ningún chequeo de
                // signo adicional (research.md Decisión 7): un total negativo es válido cuando el
                // consumo negativo ya fue confirmado arriba.
                $totalEnviado = $datosFila['total'] ?? null;
                $total = is_numeric($totalEnviado) ? (float) $totalEnviado : round($consumo * $tarifa, 2);

                // Sin DB::transaction(): el chequeo de duplicado ya se resolvió en
                // memoria contra el prefetch (specs/018, research.md R4) y el único
                // efecto en base de datos de esta fila es este único INSERT, que ya
                // es atómico por sí mismo — envolverlo en una transacción solo
                // agregaría BEGIN/COMMIT sin ningún beneficio adicional. La unique
                // (locacion_id, periodo) de la BD sigue siendo la última defensa
                // ante una condición de carrera entre dos envíos concurrentes.
                LecturaMedidor::create([
                    'locacion_id' => $locacion->id,
                    'periodo' => $periodo->format('Y-m-d'),
                    'lectura_anterior' => $lecturaAnterior,
                    'lectura_actual' => $valorActual,
                    'total' => $total,
                    'fecha_registro' => now(),
                ]);

                $guardadas++;
            } catch (ConsumoNegativoSinConfirmarException|LecturaMedidorDuplicadaException $excepcion) {
                $errores["lecturas.{$locacionId}.lectura_actual"] = $excepcion->getMessage();
            } catch (QueryException $excepcion) {
                if ($excepcion->getCode() !== '23505') {
                    throw $excepcion;
                }

                $errores["lecturas.{$locacionId}.lectura_actual"] = 'Ya existe una lectura registrada para ese periodo en esta locación. Edite la lectura existente en vez de crear un duplicado.';
            }
        }

        if (empty($errores)) {
            BorradorLecturaMedidor::where('usuario_id', Auth::id())
                ->where('periodo', $periodo->format('Y-m-d'))
                ->delete();

            return redirect()
                ->route('lecturas.registroMasivo.index', ['periodo' => $periodo->format('Y-m')])
                ->with('mensaje', "Se registraron {$guardadas} lecturas correctamente.");
        }

        return back()->withInput()->withErrors($errores);
    }

    public function guardarBorrador(Request $solicitud): Response
    {
        $periodo = Carbon::parse($solicitud->input('periodo'))->startOfMonth();
        $filas = $solicitud->input('lecturas', []);
        $ahora = now();

        $registros = collect($filas)
            ->filter(fn ($datosFila) => isset($datosFila['lectura_actual']) && $datosFila['lectura_actual'] !== '' && is_numeric($datosFila['lectura_actual']))
            ->map(fn ($datosFila, $locacionId) => [
                'usuario_id' => Auth::id(),
                'periodo' => $periodo->format('Y-m-d'),
                'locacion_id' => (int) $locacionId,
                'lectura_actual' => $datosFila['lectura_actual'],
                // specs/019 research.md Decisión 4: protege un total editado a mano igual que ya
                // protege lectura_actual — llega en cada ciclo vía hx-include sin cambios de marcado.
                'total' => is_numeric($datosFila['total'] ?? null) ? $datosFila['total'] : null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])
            ->values()
            ->all();

        if (! empty($registros)) {
            BorradorLecturaMedidor::upsert($registros, ['usuario_id', 'periodo', 'locacion_id'], ['lectura_actual', 'total', 'updated_at']);
        }

        return response('Borrador guardado a las ' . $ahora->format('H:i') . '.');
    }

    /**
     * FR-015: actualiza la tarifa por kWh (Configuración General, ya
     * existente desde specs/005) desde la propia pantalla de registro
     * masivo, disparado vía htmx en el evento "change" del input de tarifa
     * (Decisión 7 de research.md). Respuesta mínima: el recálculo visual de
     * los totales ya ocurrió del lado del cliente antes de este request.
     */
    public function actualizarTarifa(SolicitudActualizarTarifaRegistroMasivo $solicitud): Response
    {
        ConfiguracionGeneral::actual()->update([
            'tarifa_luz_por_unidad' => $solicitud->validated('tarifa_luz_por_unidad'),
        ]);

        return response()->noContent();
    }

    /**
     * FR-016: descarga en Excel del mismo contenido que ve el usuario en
     * pantalla para el periodo seleccionado.
     */
    public function exportarExcel(Request $solicitud): BinaryFileResponse
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));
        $datos = $this->datosDelPeriodo($periodo);
        $filas = $this->filasExportables($datos['raices'], $datos['lecturasDelPeriodo'], $datos['lecturasAnteriores'], $datos['tarifa']);

        return Excel::download(
            new ExportacionRegistroMasivoLecturas($filas),
            "lecturas-{$periodo->format('Y-m')}.xlsx"
        );
    }

    /**
     * FR-016: descarga en PDF del mismo contenido que ve el usuario en
     * pantalla para el periodo seleccionado.
     */
    public function exportarPdf(Request $solicitud): Response
    {
        $periodo = $this->resolverPeriodo($solicitud->query('periodo'));
        $datos = $this->datosDelPeriodo($periodo);
        $filas = $this->filasExportables($datos['raices'], $datos['lecturasDelPeriodo'], $datos['lecturasAnteriores'], $datos['tarifa']);

        $pdf = Pdf::loadView('lecturas.registro-masivo.exportar-pdf', [
            'periodo' => $periodo,
            'filas' => $filas,
            'tarifa' => $datos['tarifa'],
        ]);

        return $pdf->download("lecturas-{$periodo->format('Y-m')}.pdf");
    }

    /**
     * FR-005/FR-017: reemplaza la celda de una lectura ya registrada por su
     * modo de edición dentro de la misma fila (o, con `?cancelar=1`, la
     * vuelve a modo lectura sin guardar nada — usado por el botón cancelar
     * del propio modo de edición).
     */
    public function editarInline(Request $solicitud, LecturaMedidor $lectura): View
    {
        return view('lecturas.registro-masivo.partials.campo-lectura-registro-masivo', [
            'locacion' => $lectura->locacion,
            'lecturaDelPeriodo' => $lectura,
            'lecturaAnterior' => $this->lecturaAnteriorDe($lectura),
            'borrador' => null,
            'modoEdicion' => ! $solicitud->boolean('cancelar'),
        ]);
    }

    /**
     * FR-005/FR-017: guarda la edición en línea disparada por `editarInline`,
     * reutilizando la misma validación y el mismo patrón transaccional que
     * LecturaMedidorController@update (Decisión 9 de research.md), pero
     * respondiendo con la parcial de la celda en vez de redirigir.
     */
    public function actualizarInline(SolicitudGuardarLecturaMedidor $solicitud, LecturaMedidor $lectura): View
    {
        $datos = $solicitud->validated();
        $confirmado = $solicitud->boolean('confirmar_consumo_negativo');
        $lecturaAnteriorValor = $lectura->lectura_anterior !== null ? (float) $lectura->lectura_anterior : null;

        try {
            DB::transaction(function () use ($lectura, $datos, $confirmado, $lecturaAnteriorValor) {
                $consumo = $this->servicioConsumo->calcularConsumo($lecturaAnteriorValor, (float) $datos['lectura_actual']);

                if ($consumo !== null && $consumo < 0 && ! $confirmado) {
                    throw new ConsumoNegativoSinConfirmarException();
                }

                $lectura->update([
                    'lectura_actual' => $datos['lectura_actual'],
                ]);
            });
        } catch (ConsumoNegativoSinConfirmarException $excepcion) {
            return view('lecturas.registro-masivo.partials.campo-lectura-registro-masivo', [
                'locacion' => $lectura->locacion,
                'lecturaDelPeriodo' => $lectura,
                'lecturaAnterior' => $this->lecturaAnteriorDe($lectura),
                'borrador' => null,
                'modoEdicion' => true,
                'valorIntentado' => $datos['lectura_actual'],
                'errors' => new MessageBag(['lectura_actual' => [$excepcion->getMessage()]]),
            ]);
        }

        return view('lecturas.registro-masivo.partials.campo-lectura-registro-masivo', [
            'locacion' => $lectura->locacion,
            'lecturaDelPeriodo' => $lectura->fresh(),
            'lecturaAnterior' => $this->lecturaAnteriorDe($lectura),
            'borrador' => null,
            'modoEdicion' => false,
        ]);
    }

    private function lecturaAnteriorDe(LecturaMedidor $lectura): ?LecturaMedidor
    {
        return LecturaMedidor::where('locacion_id', $lectura->locacion_id)
            ->where('periodo', '<', $lectura->periodo->format('Y-m-d'))
            ->orderByDesc('periodo')
            ->first();
    }

    /**
     * Reúne locaciones/lecturas del periodo/lecturas anteriores/tarifa,
     * reutilizado por `index`, `exportarExcel` y `exportarPdf` (Decisión 8 de
     * research.md) para que el contenido exportado nunca se desincronice del
     * visible en pantalla. Evita N+1 con las mismas dos consultas agrupadas
     * ya usadas por `index` desde specs/015 original (Decisión 2).
     *
     * @return array{raices: array, lecturasDelPeriodo: Collection, lecturasAnteriores: Collection, tarifa: string}
     */
    private function datosDelPeriodo(Carbon $periodo): array
    {
        $idsAlquilables = Locacion::alquilables()->pluck('id');

        $lecturasDelPeriodo = LecturaMedidor::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', $periodo->format('Y-m-d'))
            ->get()
            ->keyBy('locacion_id');

        $lecturasAnteriores = LecturaMedidor::whereIn('locacion_id', $idsAlquilables)
            ->where('periodo', '<', $periodo->format('Y-m-d'))
            ->orderByDesc('periodo')
            ->get()
            ->unique('locacion_id')
            ->keyBy('locacion_id');

        return [
            'raices' => $this->servicioArbol->construir(),
            'lecturasDelPeriodo' => $lecturasDelPeriodo,
            'lecturasAnteriores' => $lecturasAnteriores,
            'tarifa' => ConfiguracionGeneral::actual()->tarifa_luz_por_unidad,
        ];
    }

    /**
     * Aplana el árbol de locaciones en una fila por locación alquilable
     * (completada o pendiente), con una "ubicación" que concatena el nombre
     * de sus ancestros para conservar contexto jerárquico sin necesitar una
     * tabla anidada en la hoja de cálculo/PDF (FR-016).
     *
     * @param  array<int, array{locacion: Locacion, hijos: array}>  $nodos
     * @return array<int, array{ubicacion: string, lectura_anterior: string|null, lectura_actual: string|null, consumo: string|null, total: float|null}>
     */
    private function filasExportables(array $nodos, Collection $lecturasDelPeriodo, Collection $lecturasAnteriores, string $tarifa, string $ruta = ''): array
    {
        $filas = [];

        foreach ($nodos as $nodo) {
            $locacion = $nodo['locacion'];
            $rutaActual = $ruta === '' ? $locacion->nombre : $ruta . ' > ' . $locacion->nombre;

            if ($locacion->es_alquilable) {
                $lecturaDelPeriodo = $lecturasDelPeriodo->get($locacion->id);
                $lecturaAnterior = $lecturasAnteriores->get($locacion->id);
                $consumo = $lecturaDelPeriodo?->consumo_calculado;
                $total = $consumo !== null ? round((float) $consumo * (float) $tarifa, 2) : null;

                $filas[] = [
                    'ubicacion' => $rutaActual,
                    'lectura_anterior' => $lecturaAnterior !== null ? (string) $lecturaAnterior->lectura_actual : null,
                    'lectura_actual' => $lecturaDelPeriodo !== null ? (string) $lecturaDelPeriodo->lectura_actual : null,
                    'consumo' => $consumo !== null ? (string) $consumo : null,
                    'total' => $total,
                ];
            }

            if (! empty($nodo['hijos'])) {
                $filas = [...$filas, ...$this->filasExportables($nodo['hijos'], $lecturasDelPeriodo, $lecturasAnteriores, $tarifa, $rutaActual)];
            }
        }

        return $filas;
    }

    private function resolverPeriodo(?string $periodo): Carbon
    {
        if ($periodo === null || $periodo === '') {
            return now()->startOfMonth();
        }

        return Carbon::parse($periodo)->startOfMonth();
    }
}

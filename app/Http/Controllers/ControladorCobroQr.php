<?php

namespace App\Http\Controllers;

use App\Exceptions\MontoPagoExcedeSaldoException;
use App\Exceptions\MontoPagoInvalidoException;
use App\Exceptions\ReciboAnuladoNoAdmitePagosException;
use App\Http\Requests\SolicitudRegistrarCobroRapido;
use App\Models\Pago;
use App\Models\Recibo;
use App\Services\ServicioCodigoQrRecibo;
use App\Services\ServicioGestionPagosRecibo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * specs/044 (US3): cobro por QR desde el inicio. Vive bajo `['auth','cuenta.activa']`
 * como el resto de la app — Master y Administrador acceden por igual
 * (research.md Decisión 10). La vista de un recibo concreto (`cobro.recibo`)
 * exige una URL firmada: el QR del comprobante y el ingreso manual del número
 * llevan a ella, un id crudo no.
 *
 * Estados (research.md Decisión 11): habilita el formulario cuando el recibo
 * NO está anulado y tiene saldo pendiente > 0; si está anulado o ya saldado,
 * muestra un aviso y no ofrece el formulario.
 */
class ControladorCobroQr extends Controller
{
    public function __construct(
        private readonly ServicioCodigoQrRecibo $servicioQr,
        private readonly ServicioGestionPagosRecibo $servicioPagos,
    ) {}

    /** Vista de escaneo: cámara (JS) + ingreso manual del número de recibo (sin JS). */
    public function index(): View
    {
        return view('cobro.index');
    }

    /**
     * Resuelve un recibo por su número tecleado y redirige a la vista de cobro
     * firmada (mismo destino que el QR). Número inválido o inexistente →
     * vuelve al escáner con el error y el foco en el campo.
     */
    public function buscar(Request $solicitud): RedirectResponse
    {
        $numero = (string) $solicitud->query('numero', '');

        if (! $this->servicioQr->numeroEsValido($numero)) {
            return redirect()->route('cobro.index')
                ->withErrors(['numero' => 'Ingrese un número de recibo válido.']);
        }

        $recibo = Recibo::find($this->servicioQr->idDesdeNumero($numero));

        if ($recibo === null) {
            return redirect()->route('cobro.index')
                ->withErrors(['numero' => 'No se encontró un recibo con ese número.']);
        }

        return redirect()->to($this->servicioQr->enlace($recibo));
    }

    /** Vista de cobro de un recibo concreto (URL firmada). Formulario rápido o aviso por estado. */
    public function recibo(Recibo $recibo): View
    {
        $recibo->load(['locacion', 'contrato', 'conceptos', 'pagos']);

        $bloqueo = match (true) {
            $recibo->estado === 'anulado' => 'Este recibo está anulado y no admite pagos.',
            $recibo->saldoPendiente() <= 0.0 => 'Este recibo ya está saldado.',
            default => null,
        };

        return view('cobro.recibo', [
            'recibo' => $recibo,
            'bloqueo' => $bloqueo,
        ]);
    }

    /**
     * Registra el pago del formulario rápido reutilizando
     * ServicioGestionPagosRecibo (misma validación de saldo/anulado que
     * `recibos/show`). Si se adjuntó evidencia, se guarda sobre el pago recién
     * creado con el mismo mecanismo de specs/035.
     */
    public function registrarPago(SolicitudRegistrarCobroRapido $solicitud, Recibo $recibo): RedirectResponse
    {
        $destino = $this->servicioQr->enlace($recibo);

        try {
            $pago = $this->servicioPagos->registrar($recibo, [
                'monto' => $solicitud->validated('monto'),
                'fecha_pago' => $solicitud->validated('fecha_pago'),
                'medio_pago' => $solicitud->validated('medio_pago'),
            ], Auth::id());
        } catch (ReciboAnuladoNoAdmitePagosException|MontoPagoExcedeSaldoException|MontoPagoInvalidoException $excepcion) {
            return redirect()->to($destino)->withErrors(['monto' => $excepcion->getMessage()])->withInput();
        }

        if ($solicitud->hasFile('evidencia')) {
            $this->guardarEvidencia($pago, $solicitud->file('evidencia'));
        }

        return redirect()->to($destino)->with('mensaje', 'Pago registrado correctamente.');
    }

    /** specs/035: un único archivo de evidencia por pago, en el disco `local`. */
    private function guardarEvidencia(Pago $pago, UploadedFile $archivo): void
    {
        DB::transaction(function () use ($pago, $archivo) {
            if ($pago->tieneEvidencia()) {
                Storage::disk('local')->delete($pago->evidencia_ruta);
            }

            $ruta = $archivo->store("pagos/{$pago->id}", 'local');

            $pago->update([
                'evidencia_ruta' => $ruta,
                'evidencia_nombre_archivo' => $archivo->getClientOriginalName(),
                'evidencia_tipo' => $archivo->getClientMimeType() === 'application/pdf' ? 'pdf' : 'imagen',
            ]);
        });
    }
}

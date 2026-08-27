<?php

namespace App\Http\Controllers;

use App\Exceptions\MontoPagoExcedeSaldoException;
use App\Exceptions\MontoPagoInvalidoException;
use App\Exceptions\ReciboAnuladoNoAdmitePagosException;
use App\Http\Requests\SolicitudGuardarPago;
use App\Models\Pago;
use App\Models\Recibo;
use App\Services\ServicioGestionPagosRecibo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PagoReciboController extends Controller
{
    public function __construct(private readonly ServicioGestionPagosRecibo $servicio)
    {
    }

    public function store(SolicitudGuardarPago $solicitud, Recibo $recibo): RedirectResponse
    {
        try {
            $this->servicio->registrar($recibo, $solicitud->validated(), Auth::id());
        } catch (ReciboAnuladoNoAdmitePagosException|MontoPagoExcedeSaldoException|MontoPagoInvalidoException $excepcion) {
            return back()->withErrors(['monto' => $excepcion->getMessage()])->withInput();
        }

        return redirect()->route('recibos.show', $recibo)
            ->with('mensaje', 'Pago registrado correctamente.');
    }

    public function update(SolicitudGuardarPago $solicitud, Pago $pago): RedirectResponse
    {
        try {
            $this->servicio->actualizar($pago, $solicitud->validated());
        } catch (ReciboAnuladoNoAdmitePagosException|MontoPagoExcedeSaldoException|MontoPagoInvalidoException $excepcion) {
            return back()->withErrors(['monto' => $excepcion->getMessage()])->withInput();
        }

        return redirect()->route('recibos.show', $pago->recibo_id)
            ->with('mensaje', 'Pago actualizado correctamente.');
    }

    public function destroy(Pago $pago): RedirectResponse
    {
        $reciboId = $pago->recibo_id;

        try {
            $this->servicio->eliminar($pago);
        } catch (ReciboAnuladoNoAdmitePagosException $excepcion) {
            return back()->withErrors(['monto' => $excepcion->getMessage()]);
        }

        return redirect()->route('recibos.show', $reciboId)
            ->with('mensaje', 'Pago eliminado correctamente.');
    }
}

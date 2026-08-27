<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudSubirEvidenciaPago;
use App\Models\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * specs/035: evidencia (imagen o PDF) del comprobante de pago ya firmado —
 * un único archivo por pago, se reemplaza al subir uno nuevo (research.md
 * Decisión 2/3), mismo mecanismo de almacenamiento ya usado por
 * DocumentoContratoController para los documentos de contrato.
 */
class EvidenciaPagoController extends Controller
{
    public function store(SolicitudSubirEvidenciaPago $solicitud, Pago $pago): RedirectResponse
    {
        DB::transaction(function () use ($solicitud, $pago) {
            if ($pago->tieneEvidencia()) {
                Storage::disk('local')->delete($pago->evidencia_ruta);
            }

            $archivo = $solicitud->file('archivo');
            $ruta = $archivo->store("pagos/{$pago->id}", 'local');

            $pago->update([
                'evidencia_ruta' => $ruta,
                'evidencia_nombre_archivo' => $archivo->getClientOriginalName(),
                'evidencia_tipo' => $archivo->getClientMimeType() === 'application/pdf' ? 'pdf' : 'imagen',
            ]);
        });

        return redirect()->route('recibos.show', $pago->recibo_id)
            ->with('mensaje', 'Evidencia del pago subida correctamente.');
    }

    public function show(Pago $pago): StreamedResponse
    {
        abort_unless($pago->tieneEvidencia(), 404);

        return Storage::disk('local')->response($pago->evidencia_ruta, $pago->evidencia_nombre_archivo);
    }
}

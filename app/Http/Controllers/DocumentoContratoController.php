<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudSubirDocumentoContrato;
use App\Models\Contrato;
use App\Models\DocumentoContrato;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoContratoController extends Controller
{
    public function store(SolicitudSubirDocumentoContrato $solicitud, Contrato $contrato): RedirectResponse
    {
        $carpeta = "contratos/{$contrato->id}";

        if ($solicitud->hasFile('archivo_pdf')) {
            $archivo = $solicitud->file('archivo_pdf');
            $ruta = $archivo->store($carpeta, 'local');

            DocumentoContrato::create([
                'contrato_id' => $contrato->id,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'ruta_archivo' => $ruta,
                'tipo_archivo' => 'pdf',
                'secuencia' => 1,
            ]);

            return redirect()->route('contratos.show', $contrato)
                ->with('mensaje', 'PDF del contrato subido correctamente.');
        }

        $secuenciaInicial = $contrato->documentos()->where('tipo_archivo', 'imagen')->max('secuencia') ?? 0;

        foreach ($solicitud->file('archivo_imagenes') as $indice => $archivo) {
            $ruta = $archivo->store($carpeta, 'local');

            DocumentoContrato::create([
                'contrato_id' => $contrato->id,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'ruta_archivo' => $ruta,
                'tipo_archivo' => 'imagen',
                'secuencia' => $secuenciaInicial + $indice + 1,
            ]);
        }

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Fotos del contrato subidas correctamente.');
    }

    public function show(Contrato $contrato, DocumentoContrato $documento): StreamedResponse
    {
        abort_unless($documento->contrato_id === $contrato->id, 404);

        return Storage::disk('local')->response($documento->ruta_archivo, $documento->nombre_archivo);
    }

    public function destroy(Contrato $contrato, DocumentoContrato $documento): RedirectResponse
    {
        abort_unless($documento->contrato_id === $contrato->id, 404);

        DB::transaction(function () use ($documento) {
            Storage::disk('local')->delete($documento->ruta_archivo);
            $documento->delete();
        });

        return redirect()->route('contratos.show', $contrato)
            ->with('mensaje', 'Documento eliminado correctamente.');
    }
}

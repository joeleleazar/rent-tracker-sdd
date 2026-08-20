<?php

namespace App\Http\Requests;

use App\Models\Contrato;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SolicitudSubirDocumentoContrato extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
            'archivo_imagenes' => ['nullable', 'array', 'max:10'],
            'archivo_imagenes.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $validador->after(function (Validator $validador) {
            $tienePdf = $this->hasFile('archivo_pdf');
            $tieneImagenes = $this->hasFile('archivo_imagenes');

            if (! $tienePdf && ! $tieneImagenes) {
                $validador->errors()->add('archivo_pdf', 'Debe seleccionar un PDF o al menos una foto.');

                return;
            }

            if ($tienePdf && $tieneImagenes) {
                $validador->errors()->add('archivo_pdf', 'No puede subir un PDF y fotos al mismo tiempo.');

                return;
            }

            /** @var Contrato $contrato */
            $contrato = $this->route('contrato');
            $documentosExistentes = $contrato->documentos();

            if ($tienePdf && $documentosExistentes->exists()) {
                $validador->errors()->add('archivo_pdf', 'Este contrato ya tiene documentos adjuntos; elimínelos antes de subir un nuevo PDF.');

                return;
            }

            if ($tieneImagenes) {
                if ($documentosExistentes->where('tipo_archivo', 'pdf')->exists()) {
                    $validador->errors()->add('archivo_imagenes', 'Este contrato ya tiene un PDF adjunto; no se pueden mezclar tipos de documento.');

                    return;
                }

                $totalImagenes = $documentosExistentes->where('tipo_archivo', 'imagen')->count() + count($this->file('archivo_imagenes'));

                if ($totalImagenes > 10) {
                    $validador->errors()->add('archivo_imagenes', 'No se pueden adjuntar más de 10 fotos por contrato.');
                }
            }
        });
    }
}

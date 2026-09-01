<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * specs/044 (US2): confirma la vista previa de la importación masiva de
 * recibos. Recibe los inputs de la tabla editable (no el archivo).
 */
class SolicitudConfirmarImportacionRecibos extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'periodo' => ['required', 'date_format:Y-m-d'],
            'filas' => ['required', 'array', 'min:1'],
            'filas.*.local_id' => ['required', 'integer'],
            'filas.*.renta' => ['nullable', 'numeric'],
            'filas.*.luz' => ['nullable', 'numeric'],
            'filas.*.total' => ['nullable', 'numeric'],
            'filas.*.conceptos' => ['nullable', 'array'],
            'filas.*.conceptos.*' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'filas.required' => 'No hay filas para importar.',
        ];
    }
}

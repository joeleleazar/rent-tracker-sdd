<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * specs/044 (US1): confirma la vista previa de la importación masiva de
 * lecturas. Recibe los inputs de la tabla editable (no el archivo).
 */
class SolicitudConfirmarImportacionLecturas extends FormRequest
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
            'filas.*.lectura_actual' => ['nullable', 'numeric'],
            'filas.*.total' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'filas.required' => 'No hay filas para importar.',
            'filas.*.local_id.required' => 'Una de las filas perdió su identificador de local.',
        ];
    }
}

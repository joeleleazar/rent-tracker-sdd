<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación deliberadamente mínima (specs/015, FR-009): la validación numérica
 * por fila NO vive aquí porque una FormRequest aborta toda la petición ante el
 * primer error, lo que impediría guardar las filas válidas del mismo lote junto
 * a una fila inválida. Esa validación se hace manualmente por fila dentro de
 * RegistroMasivoLecturasController@store.
 */
class SolicitudGuardarRegistroMasivoLecturas extends FormRequest
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
            'periodo' => ['required', 'date'],
            'lecturas' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.required' => 'El periodo del lote es obligatorio.',
            'periodo.date' => 'El periodo no es una fecha válida.',
        ];
    }
}

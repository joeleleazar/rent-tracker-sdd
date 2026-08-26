<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * specs/024: valores de referencia por concepto de gasto fijo, indexados por
 * `concepto_gasto_fijo_id` (`valores[{id}]`) — reemplaza los 4 campos fijos
 * `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad`. El controlador
 * (no esta validación) descarta cualquier id que no corresponda a un
 * concepto activo y no protegido (Renta/Luz nunca se configuran aquí).
 */
class SolicitudGuardarCostosContrato extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'valores' => ['sometimes', 'array'],
            'valores.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'valores.*.numeric' => 'El valor de referencia debe ser un número.',
            'valores.*.min' => 'El valor de referencia no puede ser negativo.',
        ];
    }
}

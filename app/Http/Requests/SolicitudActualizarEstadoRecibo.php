<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudActualizarEstadoRecibo extends FormRequest
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
            'nuevo_estado' => ['required', 'in:pendiente,pagado,anulado'],
            'confirmado' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nuevo_estado.required' => 'Debe seleccionar el nuevo estado del recibo.',
            'nuevo_estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}

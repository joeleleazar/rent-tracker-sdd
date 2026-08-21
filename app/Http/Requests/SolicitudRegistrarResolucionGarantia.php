<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudRegistrarResolucionGarantia extends FormRequest
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
            'monto_devuelto_garantia' => ['required', 'numeric', 'min:0'],
            'monto_retenido_garantia' => ['required', 'numeric', 'min:0'],
            'motivo_retencion_garantia' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ((float) $this->input('monto_retenido_garantia', 0) > 0 && empty($value)) {
                        $fail('Debe ingresar un motivo de retención cuando el monto retenido es mayor a cero.');
                    }
                },
            ],
            'confirmado' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_devuelto_garantia.required' => 'El monto devuelto es obligatorio.',
            'monto_devuelto_garantia.numeric' => 'El monto devuelto debe ser un número.',
            'monto_devuelto_garantia.min' => 'El monto devuelto no puede ser negativo.',
            'monto_retenido_garantia.required' => 'El monto retenido es obligatorio.',
            'monto_retenido_garantia.numeric' => 'El monto retenido debe ser un número.',
            'monto_retenido_garantia.min' => 'El monto retenido no puede ser negativo.',
            'motivo_retencion_garantia.string' => 'El motivo de retención no es válido.',
        ];
    }
}

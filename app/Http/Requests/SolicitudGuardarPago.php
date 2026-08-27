<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudGuardarPago extends FormRequest
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
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'Debe indicar el monto del pago.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'fecha_pago.required' => 'Debe indicar la fecha del pago.',
            'fecha_pago.date' => 'La fecha del pago no es válida.',
            'fecha_pago.before_or_equal' => 'La fecha del pago no puede ser futura.',
        ];
    }
}

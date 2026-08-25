<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudActualizarTarifaRegistroMasivo extends FormRequest
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
            'tarifa_luz_por_unidad' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tarifa_luz_por_unidad.required' => 'La tarifa por kWh es obligatoria.',
            'tarifa_luz_por_unidad.numeric' => 'La tarifa por kWh debe ser un número.',
            'tarifa_luz_por_unidad.min' => 'La tarifa por kWh no puede ser negativa.',
        ];
    }
}

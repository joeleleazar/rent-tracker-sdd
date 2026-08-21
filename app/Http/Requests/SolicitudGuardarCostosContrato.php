<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición rápida de solo los 4 costos fijos del contrato desde su vista de detalle,
 * sin tocar fechas/monto_renta/estado (contracts/rutas-condiciones-contrato-recibo.md).
 */
class SolicitudGuardarCostosContrato extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['costo_agua', 'costo_luz', 'costo_pasadizo', 'costo_seguridad'] as $campo) {
            if ($this->input($campo) === null || $this->input($campo) === '') {
                $this->merge([$campo => 0]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'costo_agua' => ['required', 'numeric', 'min:0'],
            'costo_luz' => ['required', 'numeric', 'min:0'],
            'costo_pasadizo' => ['required', 'numeric', 'min:0'],
            'costo_seguridad' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'costo_agua.numeric' => 'El costo de agua debe ser un número.',
            'costo_agua.min' => 'El costo de agua no puede ser negativo.',
            'costo_luz.numeric' => 'El costo de luz debe ser un número.',
            'costo_luz.min' => 'El costo de luz no puede ser negativo.',
            'costo_pasadizo.numeric' => 'El costo de pasadizo debe ser un número.',
            'costo_pasadizo.min' => 'El costo de pasadizo no puede ser negativo.',
            'costo_seguridad.numeric' => 'El costo de seguridad debe ser un número.',
            'costo_seguridad.min' => 'El costo de seguridad no puede ser negativo.',
        ];
    }
}

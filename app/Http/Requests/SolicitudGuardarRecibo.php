<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudGuardarRecibo extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['monto_agua', 'monto_luz', 'monto_pasadizo', 'monto_seguridad'] as $campo) {
            if ($this->input($campo) === null || $this->input($campo) === '') {
                $this->merge([$campo => 0]);
            }
        }

        if ($this->input('fecha_emision') === null || $this->input('fecha_emision') === '') {
            $this->merge(['fecha_emision' => now()->format('Y-m-d')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periodo' => ['required', 'date'],
            'monto_renta' => ['required', 'numeric', 'min:0'],
            'monto_agua' => ['required', 'numeric', 'min:0'],
            'monto_luz' => ['required', 'numeric', 'min:0'],
            'monto_pasadizo' => ['required', 'numeric', 'min:0'],
            'monto_seguridad' => ['required', 'numeric', 'min:0'],
            'fecha_emision' => ['required', 'date'],
            // Conceptos seleccionables (specs/005, FR-005): checkboxes ausentes
            // significan "false" (excluido); no requieren regla 'required'.
            'incluye_alquiler' => ['sometimes', 'boolean'],
            'incluye_luz' => ['sometimes', 'boolean'],
            'incluye_agua' => ['sometimes', 'boolean'],
            'incluye_seguridad' => ['sometimes', 'boolean'],
            'incluye_pasadizo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.required' => 'El periodo del recibo es obligatorio.',
            'periodo.date' => 'El periodo no es una fecha válida.',
            'monto_renta.required' => 'El monto de renta es obligatorio.',
            'monto_renta.numeric' => 'El monto de renta debe ser un número.',
            'monto_renta.min' => 'El monto de renta no puede ser negativo.',
            'monto_agua.min' => 'El monto de agua no puede ser negativo.',
            'monto_luz.min' => 'El monto de luz no puede ser negativo.',
            'monto_pasadizo.min' => 'El monto de pasadizo no puede ser negativo.',
            'monto_seguridad.min' => 'El monto de seguridad no puede ser negativo.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
        ];
    }
}

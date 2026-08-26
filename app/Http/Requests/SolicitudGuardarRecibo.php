<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * specs/024: `conceptos[{concepto_gasto_fijo_id}][incluido|monto]` reemplaza los
 * campos fijos `incluye_agua`/`monto_agua`/... — "Renta" conserva su forma fija
 * (`incluye_alquiler`/`monto_renta`), como el resto del formulario individual.
 */
class SolicitudGuardarRecibo extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('monto_renta') === null || $this->input('monto_renta') === '') {
            $this->merge(['monto_renta' => 0]);
        }

        $conceptos = collect($this->input('conceptos', []))
            ->map(fn (array $campos) => [
                'incluido' => $campos['incluido'] ?? false,
                'monto' => ($campos['monto'] ?? null) === '' || ($campos['monto'] ?? null) === null ? 0 : $campos['monto'],
            ])
            ->all();
        $this->merge(['conceptos' => $conceptos]);

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
            'fecha_emision' => ['required', 'date'],
            'incluye_alquiler' => ['sometimes', 'boolean'],
            'conceptos' => ['sometimes', 'array'],
            'conceptos.*.incluido' => ['boolean'],
            'conceptos.*.monto' => ['numeric', 'min:0'],
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
            'conceptos.*.monto.min' => 'El monto de un concepto no puede ser negativo.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
        ];
    }
}

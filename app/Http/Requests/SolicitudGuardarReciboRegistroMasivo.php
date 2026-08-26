<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * specs/023/024: conceptos + montos enviados desde el modal de "Registro Masivo
 * de Recibos" — mismas reglas que SolicitudGuardarRecibo (Renta fija,
 * `conceptos[{concepto_gasto_fijo_id}][incluido|monto]` dinámico), con dos
 * diferencias: (1) exige al menos un concepto marcado (FR-012, no tiene
 * sentido un recibo vacío); (2) responde siempre con la parcial de error del
 * modal en vez de redirigir, porque la petición siempre llega por htmx.
 */
class SolicitudGuardarReciboRegistroMasivo extends FormRequest
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
     * @return array<string, mixed>
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $incluyeAlquiler = filter_var($this->input('incluye_alquiler', false), FILTER_VALIDATE_BOOLEAN);
            $algunConceptoMarcado = collect($this->input('conceptos', []))
                ->contains(fn (array $campos) => filter_var($campos['incluido'] ?? false, FILTER_VALIDATE_BOOLEAN));

            if (! $incluyeAlquiler && ! $algunConceptoMarcado) {
                $validator->errors()->add('conceptos', 'Debe marcar al menos un concepto para generar el recibo.');
            }
        });
    }

    /**
     * specs/023: esta solicitud siempre llega por htmx desde el modal — un 302
     * de redirección con errores de sesión (comportamiento por defecto de
     * FormRequest) no tiene forma de mostrarse dentro del modal, así que se
     * responde siempre con la parcial de error y estado 422.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->view('recibos.registro-masivo.partials.error-modal-recibo', [
                'mensaje' => $validator->errors()->first(),
            ], 422)
        );
    }
}

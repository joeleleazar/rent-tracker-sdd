<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `clave` nunca se acepta desde este formulario (specs/024 FR-001) — todo
 * concepto creado o editado desde la UI es un concepto regular; los 2
 * conceptos protegidos (Renta, Luz) ya existen desde el sembrado inicial.
 */
class SolicitudGuardarConceptoGastoFijo extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0'],
            // 'activo' es un checkbox opcional (solo el formulario de edición lo ofrece — un
            // concepto nuevo siempre nace activo, sin necesidad de este campo). Su ausencia
            // significa "desactivado" al editar (mismo criterio ya usado para incluye_* en
            // recibos); el controlador lee $solicitud->boolean('activo') directamente, sin
            // mezclar un valor por defecto aquí que confundiría "no enviado" con "true".
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del concepto es obligatorio.',
            'orden.required' => 'El orden del concepto es obligatorio.',
            'orden.integer' => 'El orden debe ser un número entero.',
        ];
    }
}

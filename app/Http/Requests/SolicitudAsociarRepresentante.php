<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la asociación de un representante (existente o nuevo) a un contrato ya
 * persistido (ContratoController@agregarRepresentante). A diferencia de
 * SolicitudGuardarRepresentante, el DNI no se valida como único: si coincide con un
 * representante ya existente en el directorio global, se reutiliza (FR-007).
 */
class SolicitudAsociarRepresentante extends FormRequest
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
            'representante_id' => ['nullable', 'integer', 'exists:representantes,id'],
            'apellidos' => ['required_without:representante_id', 'nullable', 'string', 'max:255'],
            'nombres' => ['required_without:representante_id', 'nullable', 'string', 'max:255'],
            'dni' => ['required_without:representante_id', 'nullable', 'string', 'regex:/^[0-9]{8}$/'],
            'fecha_nacimiento' => ['required_without:representante_id', 'nullable', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'es_principal' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'apellidos.required_without' => 'Los apellidos del representante son obligatorios.',
            'nombres.required_without' => 'Los nombres del representante son obligatorios.',
            'dni.required_without' => 'El DNI del representante es obligatorio.',
            'dni.regex' => 'El DNI debe tener formato válido (8 dígitos numéricos).',
            'fecha_nacimiento.required_without' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before_or_equal' => 'El representante debe ser mayor de edad.',
        ];
    }
}

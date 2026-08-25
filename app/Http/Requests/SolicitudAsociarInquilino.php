<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la asociación de un inquilino (existente o nuevo) a un contrato ya
 * persistido (ContratoController@agregarInquilino). A diferencia de
 * SolicitudGuardarInquilino, el DNI no se valida como único: si coincide con un
 * inquilino ya existente en el directorio global, se reutiliza (FR-007).
 */
class SolicitudAsociarInquilino extends FormRequest
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
            'inquilino_id' => ['nullable', 'integer', 'exists:inquilinos,id'],
            'apellidos' => ['required_without:inquilino_id', 'nullable', 'string', 'max:255'],
            'nombres' => ['required_without:inquilino_id', 'nullable', 'string', 'max:255'],
            'dni' => ['required_without:inquilino_id', 'nullable', 'string', 'regex:/^[0-9]{8}$/'],
            'fecha_nacimiento' => ['required_without:inquilino_id', 'nullable', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'es_principal' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'apellidos.required_without' => 'Los apellidos del inquilino son obligatorios.',
            'nombres.required_without' => 'Los nombres del inquilino son obligatorios.',
            'dni.required_without' => 'El DNI del inquilino es obligatorio.',
            'dni.regex' => 'El DNI debe tener formato válido (8 dígitos numéricos).',
            'fecha_nacimiento.required_without' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before_or_equal' => 'El inquilino debe ser mayor de edad.',
        ];
    }
}

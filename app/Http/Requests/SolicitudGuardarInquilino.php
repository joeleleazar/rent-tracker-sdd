<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudGuardarInquilino extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'apellidos' => ['required', 'string', 'max:255'],
            'nombres' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'regex:/^[0-9]{8}$/', 'unique:inquilinos,dni'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
        ];
    }

    public function messages(): array
    {
        return [
            'apellidos.required' => 'Los apellidos del inquilino son obligatorios.',
            'nombres.required' => 'Los nombres del inquilino son obligatorios.',
            'dni.required' => 'El DNI del inquilino es obligatorio.',
            'dni.regex' => 'El DNI debe tener formato válido (8 dígitos numéricos).',
            'dni.unique' => 'Ya existe un inquilino registrado con ese DNI en el directorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' => 'El inquilino debe ser mayor de edad.',
        ];
    }
}

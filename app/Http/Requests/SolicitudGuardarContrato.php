<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudGuardarContrato extends FormRequest
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
            'inquilino_id' => ['required', 'integer', 'exists:inquilinos,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'monto_renta' => ['required', 'numeric', 'gt:0'],
            'estado' => ['required', 'in:borrador,activo,vencido,rescindido'],
        ];
    }

    public function messages(): array
    {
        return [
            'inquilino_id.required' => 'Debe seleccionar un inquilino.',
            'inquilino_id.exists' => 'El inquilino seleccionado no es válido.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'monto_renta.required' => 'El monto de renta es obligatorio.',
            'monto_renta.gt' => 'El monto de renta debe ser mayor a cero.',
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}

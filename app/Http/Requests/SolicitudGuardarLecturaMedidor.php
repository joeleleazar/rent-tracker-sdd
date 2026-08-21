<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudGuardarLecturaMedidor extends FormRequest
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
            'periodo' => ['required', 'date'],
            'lectura_anterior' => ['nullable', 'numeric', 'min:0'],
            'lectura_actual' => ['required', 'numeric', 'min:0'],
            'confirmar_consumo_negativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.required' => 'El periodo de la lectura es obligatorio.',
            'periodo.date' => 'El periodo no es una fecha válida.',
            'lectura_anterior.numeric' => 'La lectura anterior debe ser un número.',
            'lectura_anterior.min' => 'La lectura anterior no puede ser negativa.',
            'lectura_actual.required' => 'La lectura actual del medidor es obligatoria.',
            'lectura_actual.numeric' => 'La lectura actual debe ser un número.',
            'lectura_actual.min' => 'La lectura actual no puede ser negativa.',
        ];
    }
}

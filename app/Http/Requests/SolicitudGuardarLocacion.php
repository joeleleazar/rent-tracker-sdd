<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudGuardarLocacion extends FormRequest
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
        $locacionActualId = $this->route('locacion')?->id;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tamano' => ['required', 'numeric', 'gt:0'],
            'ubicacion_fisica' => ['required', 'string'],
            'descripcion' => ['required', 'string'],
            'locacion_padre_id' => [
                'nullable',
                'integer',
                Rule::exists('locaciones', 'id'),
                Rule::notIn(array_filter([$locacionActualId])),
            ],
            'es_alquilable' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'tamano.required' => 'El tamaño es obligatorio.',
            'tamano.numeric' => 'El tamaño debe ser un valor numérico.',
            'tamano.gt' => 'El tamaño debe ser mayor a cero.',
            'ubicacion_fisica.required' => 'La ubicación física es obligatoria.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'locacion_padre_id.exists' => 'La locación padre seleccionada no es válida.',
            'locacion_padre_id.not_in' => 'No se puede asignar una locación hija como padre.',
        ];
    }
}

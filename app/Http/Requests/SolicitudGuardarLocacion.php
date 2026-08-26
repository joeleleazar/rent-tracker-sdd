<?php

namespace App\Http\Requests;

use App\Models\Locacion;
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
        $locacion = $this->route('locacion');

        // specs/025 (FR-001/FR-002): "tipo" se agregó como nullable para no
        // romper locaciones ya existentes (2026_08_23_020000_add_tipo_to_locaciones_table.php),
        // pero la validación nunca reflejó esa intención — bloqueaba guardar
        // CUALQUIER edición de una locación sin tipo previo. Solo se permite
        // dejarlo vacío si la locación editada ya tenía tipo null antes de
        // este request; crear una locación nueva sigue exigiéndolo siempre.
        $permitirTipoVacio = $locacion !== null && $locacion->tipo === null;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tamano' => ['required', 'numeric', 'gt:0'],
            'ubicacion_fisica' => ['required', 'string'],
            'descripcion' => ['required', 'string'],
            'locacion_padre_id' => [
                'nullable',
                'integer',
                Rule::exists('locaciones', 'id'),
                Rule::notIn(array_filter([$locacion?->id])),
            ],
            'es_alquilable' => ['boolean'],
            'tipo' => [$permitirTipoVacio ? 'nullable' : 'required', Rule::in(array_keys(Locacion::TIPOS))],
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
            'tipo.required' => 'Debe seleccionar el tipo de locación.',
            'tipo.in' => 'El tipo de locación seleccionado no es válido.',
        ];
    }
}

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
     * Los costos fijos no aplicables se registran como "S/ 0.00" en vez de nulos
     * (FR-002 de specs/004-condiciones-contrato-recibo), sin bloquear el guardado.
     */
    protected function prepareForValidation(): void
    {
        foreach (['costo_agua', 'costo_luz', 'costo_pasadizo', 'costo_seguridad'] as $campo) {
            if ($this->input($campo) === null || $this->input($campo) === '') {
                $this->merge([$campo => 0]);
            }
        }

        // Garantía (specs/009): a diferencia de los costos fijos, es genuinamente
        // opcional — una cadena vacía se normaliza a null (no a 0), para no forzar
        // un registro de garantía "0.00" cuando el administrador no la completó.
        foreach (['monto_garantia', 'fecha_entrega_garantia', 'medio_entrega_garantia'] as $campo) {
            if ($this->input($campo) === '') {
                $this->merge([$campo => null]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reglas = [
            'inquilino_id' => ['required', 'integer', 'exists:inquilinos,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'monto_renta' => ['required', 'numeric', 'gt:0'],
            'estado' => ['required', 'in:borrador,activo,vencido,rescindido'],
            'costo_agua' => ['nullable', 'numeric', 'min:0'],
            'costo_luz' => ['nullable', 'numeric', 'min:0'],
            'costo_pasadizo' => ['nullable', 'numeric', 'min:0'],
            'costo_seguridad' => ['nullable', 'numeric', 'min:0'],
            // Garantía entregada (specs/009): opcional; fecha_entrega_garantia solo
            // obligatoria si se registró un monto_garantia mayor a cero (FR-002).
            'monto_garantia' => ['nullable', 'numeric', 'min:0'],
            // 'required'/'nullable' es implícito (fuerza la evaluación aunque el
            // valor esté vacío); un Closure normal se omite silenciosamente si el
            // campo llega vacío, por eso la condición se resuelve aquí en vez de
            // dentro de una regla de Closure (ver tasks.md de esta feature, Notes).
            'fecha_entrega_garantia' => [
                (float) ($this->input('monto_garantia') ?? 0) > 0 ? 'required' : 'nullable',
                'date',
            ],
            'medio_entrega_garantia' => ['nullable', 'in:efectivo,transferencia,cheque'],
        ];

        // Representantes (specs/003-representantes-contrato): solo se exigen al crear el
        // contrato (POST). Al editar (PUT), los representantes se gestionan de forma
        // atómica desde la vista de detalle (agregarRepresentante/quitarRepresentante),
        // igual que los documentos del contrato — ver research.md/tasks.md de 003, Notes.
        if ($this->isMethod('POST')) {
            $reglas += [
                'representantes' => ['required', 'array', 'min:1'],
                'representantes.*.representante_id' => ['nullable', 'integer', 'exists:representantes,id'],
                'representantes.*.apellidos' => ['required_without:representantes.*.representante_id', 'nullable', 'string', 'max:255'],
                'representantes.*.nombres' => ['required_without:representantes.*.representante_id', 'nullable', 'string', 'max:255'],
                'representantes.*.dni' => ['required_without:representantes.*.representante_id', 'nullable', 'string', 'regex:/^[0-9]{8}$/'],
                'representantes.*.fecha_nacimiento' => ['required_without:representantes.*.representante_id', 'nullable', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
                'principal_index' => ['nullable', 'integer'],
            ];
        }

        return $reglas;
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
            'costo_agua.numeric' => 'El costo de agua debe ser un número.',
            'costo_agua.min' => 'El costo de agua no puede ser negativo.',
            'costo_luz.numeric' => 'El costo de luz debe ser un número.',
            'costo_luz.min' => 'El costo de luz no puede ser negativo.',
            'costo_pasadizo.numeric' => 'El costo de pasadizo debe ser un número.',
            'costo_pasadizo.min' => 'El costo de pasadizo no puede ser negativo.',
            'costo_seguridad.numeric' => 'El costo de seguridad debe ser un número.',
            'costo_seguridad.min' => 'El costo de seguridad no puede ser negativo.',
            'monto_garantia.numeric' => 'El monto de garantía debe ser un número.',
            'monto_garantia.min' => 'El monto de garantía no puede ser negativo.',
            'fecha_entrega_garantia.date' => 'La fecha de entrega de garantía no es válida.',
            'fecha_entrega_garantia.required' => 'La fecha de entrega de garantía es obligatoria cuando se registra un monto de garantía.',
            'medio_entrega_garantia.in' => 'El medio de entrega de garantía seleccionado no es válido.',
            'representantes.required' => 'Debe asociar por lo menos un representante al contrato antes de guardar.',
            'representantes.min' => 'Debe asociar por lo menos un representante al contrato antes de guardar.',
            'representantes.*.apellidos.required_without' => 'Los apellidos del representante son obligatorios.',
            'representantes.*.nombres.required_without' => 'Los nombres del representante son obligatorios.',
            'representantes.*.dni.required_without' => 'El DNI del representante es obligatorio.',
            'representantes.*.dni.regex' => 'El DNI debe tener formato válido (8 dígitos numéricos).',
            'representantes.*.fecha_nacimiento.required_without' => 'La fecha de nacimiento del representante es obligatoria.',
            'representantes.*.fecha_nacimiento.before_or_equal' => 'El representante debe ser mayor de edad.',
        ];
    }
}

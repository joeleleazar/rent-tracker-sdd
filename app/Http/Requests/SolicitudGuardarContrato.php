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
     * Los valores de referencia de concepto no aplicables se registran como "S/ 0.00"
     * en vez de nulos (FR-002 de specs/004, extendido a specs/024), sin bloquear el
     * guardado.
     */
    protected function prepareForValidation(): void
    {
        $valores = collect($this->input('valores', []))
            ->map(fn ($valor) => ($valor === null || $valor === '') ? 0 : $valor)
            ->all();
        $this->merge(['valores' => $valores]);

        // Garantía (specs/009): a diferencia de los valores de concepto, es genuinamente
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
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'monto_renta' => ['required', 'numeric', 'gt:0'],
            'estado' => ['required', 'in:borrador,activo,vencido,rescindido'],
            // specs/024: valores[{concepto_gasto_fijo_id}] reemplaza los 4 campos fijos.
            'valores' => ['sometimes', 'array'],
            'valores.*' => ['numeric', 'min:0'],
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

        // Inquilinos (specs/003-representantes-contrato, corrección 2026-08-23: el
        // inquilino ES el representante del contrato): solo se exigen al crear el
        // contrato (POST). Al editar (PUT), los inquilinos se gestionan de forma
        // atómica desde la vista de detalle (agregarInquilino/quitarInquilino),
        // igual que los documentos del contrato — ver research.md/tasks.md de 003, Notes.
        if ($this->isMethod('POST')) {
            $reglas += [
                'inquilinos' => ['required', 'array', 'min:1'],
                'inquilinos.*.inquilino_id' => ['nullable', 'integer', 'exists:inquilinos,id'],
                'inquilinos.*.apellidos' => ['required_without:inquilinos.*.inquilino_id', 'nullable', 'string', 'max:255'],
                'inquilinos.*.nombres' => ['required_without:inquilinos.*.inquilino_id', 'nullable', 'string', 'max:255'],
                'inquilinos.*.dni' => ['required_without:inquilinos.*.inquilino_id', 'nullable', 'string', 'regex:/^[0-9]{8}$/'],
                'inquilinos.*.fecha_nacimiento' => ['required_without:inquilinos.*.inquilino_id', 'nullable', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
                'principal_index' => ['nullable', 'integer'],
            ];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'monto_renta.required' => 'El monto de renta es obligatorio.',
            'monto_renta.gt' => 'El monto de renta debe ser mayor a cero.',
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'valores.*.numeric' => 'El valor de referencia debe ser un número.',
            'valores.*.min' => 'El valor de referencia no puede ser negativo.',
            'monto_garantia.numeric' => 'El monto de garantía debe ser un número.',
            'monto_garantia.min' => 'El monto de garantía no puede ser negativo.',
            'fecha_entrega_garantia.date' => 'La fecha de entrega de garantía no es válida.',
            'fecha_entrega_garantia.required' => 'La fecha de entrega de garantía es obligatoria cuando se registra un monto de garantía.',
            'medio_entrega_garantia.in' => 'El medio de entrega de garantía seleccionado no es válido.',
            'inquilinos.required' => 'Debe asociar por lo menos un inquilino al contrato antes de guardar.',
            'inquilinos.min' => 'Debe asociar por lo menos un inquilino al contrato antes de guardar.',
            'inquilinos.*.apellidos.required_without' => 'Los apellidos del inquilino son obligatorios.',
            'inquilinos.*.nombres.required_without' => 'Los nombres del inquilino son obligatorios.',
            'inquilinos.*.dni.required_without' => 'El DNI del inquilino es obligatorio.',
            'inquilinos.*.dni.regex' => 'El DNI debe tener formato válido (8 dígitos numéricos).',
            'inquilinos.*.fecha_nacimiento.required_without' => 'La fecha de nacimiento del inquilino es obligatoria.',
            'inquilinos.*.fecha_nacimiento.before_or_equal' => 'El inquilino debe ser mayor de edad.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudActualizarConfiguracionGeneral extends FormRequest
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
            'correo_notificaciones_vencimiento' => ['required', 'email'],
            'tarifa_luz_por_unidad' => ['required', 'numeric', 'min:0'],
            'dias_anticipacion_alerta_pago' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo_notificaciones_vencimiento.required' => 'El correo de notificaciones es obligatorio.',
            'correo_notificaciones_vencimiento.email' => 'Debe ingresar un correo electrónico válido.',
            'tarifa_luz_por_unidad.required' => 'La tarifa de luz por unidad es obligatoria.',
            'tarifa_luz_por_unidad.numeric' => 'La tarifa de luz por unidad debe ser un número.',
            'tarifa_luz_por_unidad.min' => 'La tarifa de luz por unidad no puede ser negativa.',
            'dias_anticipacion_alerta_pago.required' => 'Los días de anticipación para la alerta de pago son obligatorios.',
            'dias_anticipacion_alerta_pago.integer' => 'Los días de anticipación deben ser un número entero.',
            'dias_anticipacion_alerta_pago.min' => 'Los días de anticipación deben ser al menos 1.',
        ];
    }
}

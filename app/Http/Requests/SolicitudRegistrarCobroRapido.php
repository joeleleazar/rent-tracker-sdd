<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * specs/044 (US3): formulario rápido de cobro por QR. Reglas mínimas — la
 * validación de saldo, recibo anulado y monto contra saldo pendiente la
 * aplica ServicioGestionPagosRecibo, igual que el registro desde
 * `recibos/show` (specs/032).
 */
class SolicitudRegistrarCobroRapido extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'medio_pago' => ['nullable', 'string', 'max:60'],
            'evidencia' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'Debe indicar el monto del pago.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'fecha_pago.required' => 'Debe indicar la fecha del pago.',
            'fecha_pago.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'evidencia.mimes' => 'La evidencia debe ser una imagen (JPG/PNG) o un PDF.',
            'evidencia.max' => 'La evidencia no puede superar los 5 MB.',
        ];
    }
}

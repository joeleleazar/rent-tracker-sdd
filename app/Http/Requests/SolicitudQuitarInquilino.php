<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la remoción de un inquilino de un contrato ya persistido. Si el
 * inquilino a quitar es el Principal y existen otros, `nuevo_principal_id`
 * es obligatorio (FR-009) — la validación de que efectivamente exista entre
 * los inquilinos restantes del contrato ocurre en
 * ServicioAsociacionInquilinosContrato::quitar().
 */
class SolicitudQuitarInquilino extends FormRequest
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
            'nuevo_principal_id' => ['nullable', 'integer', 'exists:inquilinos,id'],
        ];
    }
}

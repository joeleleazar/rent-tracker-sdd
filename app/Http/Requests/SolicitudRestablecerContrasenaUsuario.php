<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * specs/040 (FR-011): un Master fija una contraseña nueva para la cuenta
 * indicada. Misma política mínima que en el alta (al menos 8 caracteres).
 */
class SolicitudRestablecerContrasenaUsuario extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-usuarios') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'La contraseña nueva es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}

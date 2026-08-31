<?php

namespace App\Http\Requests;

use App\Enums\PerfilUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * specs/040 (FR-012): un Master cambia el perfil de una cuenta entre Master y
 * Administrador. Las invariantes del último Master activo y de la
 * auto-degradación se resuelven en ServicioAdministracionUsuarios, no aquí.
 */
class SolicitudCambiarPerfilUsuario extends FormRequest
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
            'perfil' => ['required', new Enum(PerfilUsuario::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'perfil.required' => 'Debe seleccionar un perfil.',
        ];
    }
}

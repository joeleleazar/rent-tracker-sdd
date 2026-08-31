<?php

namespace App\Http\Requests;

use App\Enums\PerfilUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

/**
 * specs/040: alta y edición de datos de una cuenta de usuario.
 *
 * - En alta (sin `{usuario}` en la ruta) se exigen además `perfil` y
 *   `password`.
 * - En edición sólo se aceptan `name` y `email`; el perfil y la contraseña se
 *   cambian por sus acciones dedicadas.
 *
 * El correo se normaliza (minúsculas, sin espacios) antes de validar, de modo
 * que la unicidad no dependa de la capitalización que haya escrito el Master.
 */
class SolicitudGuardarUsuario extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-usuarios') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->input('email'))) {
            $this->merge([
                'email' => mb_strtolower(trim($this->input('email'))),
            ]);
        }
    }

    private function esAlta(): bool
    {
        return $this->route('usuario') === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        $reglas = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($usuario),
            ],
        ];

        if ($this->esAlta()) {
            $reglas['perfil'] = ['required', new Enum(PerfilUsuario::class)];
            $reglas['password'] = ['required', 'confirmed', Password::min(8)];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'email.lowercase' => 'El correo electrónico debe ir en minúsculas.',
            'perfil.required' => 'Debe seleccionar un perfil.',
            'password.required' => 'La contraseña inicial es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}

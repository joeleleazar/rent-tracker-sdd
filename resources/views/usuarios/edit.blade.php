<x-layouts.app-bootstrap>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="fs-2 fw-bold mb-0">
                Editar usuario: {{ $usuario->name }}
            </h2>
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado
            </a>
        </div>
    </x-slot>

    @php($esCuentaPropia = $usuario->is(auth()->user()))

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            {{-- Datos de la cuenta --}}
            <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')
                    <h3 class="fs-4 fw-bold mb-0">Datos de la cuenta</h3>

                    <div>
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $usuario->name)" required autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Correo electrónico" />
                        <x-text-input id="email" name="email" type="email" :value="old('email', $usuario->email)" required autocomplete="off" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </div>
            </form>

            {{-- Cambiar perfil --}}
            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold mb-0">Perfil</h3>
                    <p class="text-secondary mb-0">
                        Perfil actual:
                        <span class="badge {{ $usuario->perfil->claseBadge() }}">{{ $usuario->perfil->etiqueta() }}</span>
                    </p>

                    <form id="form-cambiar-perfil" method="POST" action="{{ route('usuarios.perfil.update', $usuario) }}" class="d-flex flex-column gap-3">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="perfil" value="Nuevo perfil" />
                            <select id="perfil" name="perfil" class="form-select" required>
                                @foreach ($perfiles as $perfil)
                                    <option value="{{ $perfil->value }}" {{ old('perfil', $usuario->perfil->value) === $perfil->value ? 'selected' : '' }}>
                                        {{ $perfil->etiqueta() }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('perfil')" class="mt-2" />
                        </div>

                        <div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cambiar-perfil-usuario">
                                Cambiar perfil
                            </button>
                        </div>

                        <x-modal-bootstrap name="cambiar-perfil-usuario" focusable>
                            <div class="modal-header">
                                <h5 class="modal-title fs-5 fw-bold">¿Cambiar el perfil de "{{ $usuario->name }}"?</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body text-start">
                                <p class="mb-0">Cambiar el perfil ajusta qué puede hacer esta cuenta en el sistema. El cambio aplica en su siguiente petición.</p>
                            </div>
                            <div class="modal-footer">
                                <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                <button type="submit" form="form-cambiar-perfil" class="btn btn-primary">Sí, cambiar perfil</button>
                            </div>
                        </x-modal-bootstrap>
                    </form>
                </div>
            </div>

            {{-- Restablecer contraseña --}}
            <form method="POST" action="{{ route('usuarios.contrasena.update', $usuario) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')
                    <h3 class="fs-4 fw-bold mb-0">Restablecer contraseña</h3>

                    <div>
                        <x-input-label for="password" value="Contraseña nueva" />
                        <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                        <div class="form-text">Al menos 8 caracteres.</div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar contraseña nueva" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                    </div>

                    <div>
                        <x-primary-button>
                            <i class="bi bi-key" aria-hidden="true"></i> Restablecer contraseña
                        </x-primary-button>
                    </div>
                </div>
            </form>

            {{-- Estado de la cuenta --}}
            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold mb-0">Estado de la cuenta</h3>
                    <p class="text-secondary mb-0">
                        Estado actual:
                        @if ($usuario->estaActivo())
                            <span class="badge bg-success">Activa</span>
                        @else
                            <span class="badge bg-secondary">Inactiva</span>
                        @endif
                    </p>

                    @if ($esCuentaPropia)
                        <p class="text-secondary mb-0">No puedes desactivar tu propia cuenta.</p>
                    @elseif ($usuario->estaActivo())
                        <div>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#desactivar-usuario-edit">
                                <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar cuenta
                            </button>
                        </div>

                        <x-modal-bootstrap name="desactivar-usuario-edit" focusable>
                            <div class="modal-header">
                                <h5 class="modal-title fs-5 fw-bold">¿Desactivar a "{{ $usuario->name }}"?</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body text-start">
                                <p class="mb-0">La cuenta no podrá iniciar sesión hasta que se reactive. Se conserva toda su información y su historial.</p>
                            </div>
                            <div class="modal-footer">
                                <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                <form method="POST" action="{{ route('usuarios.estado.update', $usuario) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="activo" value="0">
                                    <button type="submit" class="btn btn-warning">Sí, desactivar usuario</button>
                                </form>
                            </div>
                        </x-modal-bootstrap>
                    @else
                        <form method="POST" action="{{ route('usuarios.estado.update', $usuario) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="activo" value="1">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reactivar cuenta
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app-bootstrap>

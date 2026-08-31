<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Agregar usuario
        </h2>
    </x-slot>

    <div class="col-12 col-lg-6" style="max-width: 32rem;">
        @if ($errors->any())
            <x-mensaje-alerta tipo="error" class="mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('usuarios.store') }}" class="card">
            <div class="card-body d-flex flex-column gap-3">
                @csrf

                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" name="name" type="text" :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Correo electrónico" />
                    <x-text-input id="email" name="email" type="email" :value="old('email')" required autocomplete="off" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="perfil" value="Perfil" />
                    <select id="perfil" name="perfil" class="form-select" required>
                        <option value="" disabled {{ old('perfil') ? '' : 'selected' }}>Selecciona un perfil…</option>
                        @foreach ($perfiles as $perfil)
                            <option value="{{ $perfil->value }}" {{ old('perfil') === $perfil->value ? 'selected' : '' }}>
                                {{ $perfil->etiqueta() }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        El perfil <strong>Master</strong> puede administrar usuarios; el perfil <strong>Administrador</strong>, no.
                    </div>
                    <x-input-error :messages="$errors->get('perfil')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Contraseña inicial" />
                    <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                    <div class="form-text">Al menos 8 caracteres.</div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <x-primary-button>
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Crear usuario
                    </x-primary-button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app-bootstrap>

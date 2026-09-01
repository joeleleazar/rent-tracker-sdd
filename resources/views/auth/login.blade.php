<x-layouts.guest-bootstrap>
    <x-slot name="title">Iniciar sesión</x-slot>
    <x-slot name="subtitle">Ingresa con tu cuenta para administrar contratos, recibos y pagos.</x-slot>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="d-flex flex-column gap-3">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="form-check">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label">Mantener la sesión iniciada</label>
        </div>

        <x-primary-button class="w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Iniciar sesión
        </x-primary-button>

        @if (Route::has('password.request'))
            <div class="text-center">
                <a class="small" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            </div>
        @endif
    </form>
</x-layouts.guest-bootstrap>

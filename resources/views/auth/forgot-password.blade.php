<x-layouts.guest-bootstrap>
    <x-slot name="title">Recuperar contraseña</x-slot>
    <x-slot name="subtitle">Indícanos tu correo y te enviaremos un enlace para elegir una nueva contraseña.</x-slot>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="d-flex flex-column gap-3">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-envelope-arrow-up" aria-hidden="true"></i> Enviar enlace de recuperación
        </x-primary-button>

        <div class="text-center">
            <a class="small" href="{{ route('login') }}">Volver a iniciar sesión</a>
        </div>
    </form>
</x-layouts.guest-bootstrap>

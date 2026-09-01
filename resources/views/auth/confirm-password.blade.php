<x-layouts.guest-bootstrap>
    <x-slot name="title">Confirma tu contraseña</x-slot>
    <x-slot name="subtitle">Esta es un área protegida. Vuelve a ingresar tu contraseña para continuar.</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}" class="d-flex flex-column gap-3">
        @csrf

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-shield-lock" aria-hidden="true"></i> Confirmar
        </x-primary-button>
    </form>
</x-layouts.guest-bootstrap>

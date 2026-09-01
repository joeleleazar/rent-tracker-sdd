<x-layouts.guest-bootstrap>
    <x-slot name="title">Verifica tu correo</x-slot>
    <x-slot name="subtitle">Te enviamos un enlace de verificación al correo de tu cuenta. Ábrelo para activarla. Si no lo recibiste, podemos enviarte otro.</x-slot>

    @if (session('status') == 'verification-link-sent')
        <div class="d-flex align-items-center gap-2 fw-semibold text-success" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            Enviamos un nuevo enlace de verificación al correo indicado en tu registro.
        </div>
    @endif

    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-envelope-arrow-up" aria-hidden="true"></i> Reenviar correo
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary w-100">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-layouts.guest-bootstrap>

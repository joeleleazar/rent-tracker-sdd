<section class="d-flex flex-column gap-3">
    <header>
        <h3 class="fs-4 fw-bold mb-1">
            Información del perfil
        </h3>

        <p class="text-secondary mb-0">
            Actualiza el nombre y el correo electrónico de tu cuenta.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="d-flex flex-column gap-3">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="mb-1">
                        Tu correo electrónico no está verificado.

                        <button form="send-verification" class="btn btn-link p-0 align-baseline">
                            Reenviar el correo de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="fw-semibold text-success mb-0">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            Enviamos un nuevo enlace de verificación a tu correo.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p class="text-success fw-semibold mb-0">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardado.
                </p>
            @endif
        </div>
    </form>
</section>

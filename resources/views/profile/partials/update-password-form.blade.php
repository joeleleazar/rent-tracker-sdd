<section class="d-flex flex-column gap-3">
    <header>
        <h3 class="fs-4 fw-bold mb-1">
            Cambiar contraseña
        </h3>

        <p class="text-secondary mb-0">
            Usa una contraseña larga y difícil de adivinar para mantener la cuenta segura.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="d-flex flex-column gap-3">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Contraseña actual" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Nueva contraseña" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <div class="form-text">Al menos 8 caracteres.</div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmar nueva contraseña" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'password-updated')
                <p class="text-success fw-semibold mb-0">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Guardado.
                </p>
            @endif
        </div>
    </form>
</section>

<section class="d-flex flex-column gap-3">
    <header>
        <h3 class="fs-4 fw-bold mb-1">
            Eliminar cuenta
        </h3>

        <p class="text-secondary mb-0">
            Al eliminar tu cuenta se borran de forma permanente todos sus datos. Antes de continuar, descarga cualquier información que quieras conservar.
        </p>
    </header>

    <x-danger-button
        type="button"
        class="align-self-start d-inline-flex align-items-center gap-2"
        data-bs-toggle="modal"
        data-bs-target="#confirm-user-deletion"
    ><i class="bi bi-trash" aria-hidden="true"></i> Eliminar cuenta</x-danger-button>

    <x-modal-bootstrap name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <div class="modal-header">
                <h5 class="modal-title fs-5 fw-bold">¿Eliminar tu cuenta?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body text-start d-flex flex-column gap-3">
                <p class="mb-0">
                    Al eliminar tu cuenta se borran de forma permanente todos sus datos. Ingresa tu contraseña para confirmar que quieres eliminarla.
                </p>

                <div>
                    <x-input-label for="password" value="Contraseña" class="visually-hidden" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Contraseña"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="modal-footer">
                <x-secondary-button type="button" data-bs-dismiss="modal">
                    No, cancelar
                </x-secondary-button>

                <x-danger-button>
                    Sí, eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal-bootstrap>
</section>

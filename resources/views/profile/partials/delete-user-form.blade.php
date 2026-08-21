<section class="d-flex flex-column gap-3">
    <header>
        <h2 class="fs-4 fw-bold">
            {{ __('Delete Account') }}
        </h2>

        <p class="fs-5">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        type="button"
        class="align-self-start"
        data-bs-toggle="modal"
        data-bs-target="#confirm-user-deletion"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal-bootstrap name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <div class="modal-body p-4 d-flex flex-column gap-3">
                <h2 class="fs-4 fw-bold">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="fs-5 mb-0">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div>
                    <x-input-label for="password" value="{{ __('Password') }}" class="visually-hidden" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="{{ __('Password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="modal-footer">
                <x-secondary-button type="button" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal-bootstrap>
</section>

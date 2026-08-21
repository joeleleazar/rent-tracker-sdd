<x-layouts.guest-bootstrap>
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="d-flex flex-column gap-3">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="form-check d-flex align-items-center gap-2">
            <input id="remember_me" type="checkbox" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" name="remember">
            <label for="remember_me" class="form-check-label fs-5">{{ __('Remember me') }}</label>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            @if (Route::has('password.request'))
                <a class="fs-5" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-layouts.guest-bootstrap>

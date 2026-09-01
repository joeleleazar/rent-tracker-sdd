<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">Cobro por QR</h2>
    </x-slot>

    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <p class="text-secondary mb-0">
                        Escanee el código del recibo con la cámara o ingrese su número para registrar el
                        pago.
                    </p>

                    {{-- Bloque de cámara: lo muestra cobro-qr.js solo si hay cámara, permiso y librería. --}}
                    <div id="bloque-camara-cobro" hidden>
                        <div id="lector-qr" class="border rounded mx-auto" style="max-width: 320px;"></div>
                    </div>

                    <p id="aviso-sin-camara" class="text-secondary small mb-0" hidden>
                        No se pudo usar la cámara (se necesita permiso y una conexión segura). Ingrese el
                        número de recibo abajo.
                    </p>

                    <hr class="my-1">

                    <form method="GET" action="{{ route('cobro.buscar') }}" class="d-flex flex-column gap-2">
                        <x-input-label for="numero-recibo-cobro" value="Número de recibo" />
                        @error('numero')
                            <div class="text-danger small" role="alert">{{ $message }}</div>
                        @enderror
                        <div class="input-group">
                            <span class="input-group-text">#</span>
                            <input
                                id="numero-recibo-cobro"
                                name="numero"
                                type="text"
                                inputmode="numeric"
                                class="form-control @error('numero') is-invalid @enderror"
                                value="{{ old('numero') }}"
                                autocomplete="off"
                                required
                            >
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search" aria-hidden="true"></i> Buscar recibo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/cobro-qr.js')
</x-layouts.app-bootstrap>

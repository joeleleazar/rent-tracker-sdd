<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Nuevo Concepto de Gasto Fijo
        </h2>
    </x-slot>

    <div class="col-12 col-lg-6" style="max-width: 32rem;">
        <form method="POST" action="{{ route('conceptosGastoFijo.store') }}" class="card">
            <div class="card-body d-flex flex-column gap-3">
                @csrf

                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" type="text" :value="old('nombre')" required autofocus />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="orden" value="Orden de aparición" />
                    <x-text-input id="orden" name="orden" type="number" min="0" :value="old('orden', 10)" required />
                    <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <x-primary-button>Crear Concepto</x-primary-button>
                    <a href="{{ route('conceptosGastoFijo.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app-bootstrap>

<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Locaciones
        </h2>
    </x-slot>

    <div class="col-12">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            <div class="d-flex justify-content-end">
                <a href="{{ route('locaciones.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva Locación</a>
            </div>

            @if (empty($raices))
                <p>Todavía no hay locaciones registradas.</p>
            @else
                <div class="tabla-arbol-locaciones">
                    <div class="tabla-arbol-locaciones__encabezado">
                        <div>Nombre / Locación</div>
                        <div>Estado</div>
                        <div>Tipo</div>
                        <div>Acciones</div>
                    </div>

                    @foreach ($raices as $nodo)
                        @include('locaciones.partials.fila-arbol-locacion', ['locacion' => $nodo['locacion'], 'hijos' => $nodo['hijos'], 'profundidad' => 0])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>

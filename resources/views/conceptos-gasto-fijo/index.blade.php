<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Conceptos de Gasto Fijo
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

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3">
                <p class="mb-0 text-secondary">
                    Estos son los conceptos disponibles para configurar en contratos y para incluir en recibos. "Renta" y "Luz" son conceptos protegidos: sus montos se calculan automáticamente y no pueden desactivarse ni eliminarse.
                </p>
                <a href="{{ route('conceptosGastoFijo.create') }}" class="btn btn-primary text-nowrap align-self-stretch align-self-sm-start">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo Concepto
                </a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Orden</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Estado</th>
                                <th scope="col">En uso</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conceptos as $concepto)
                                @php
                                    $enUso = $concepto->contratos_en_uso + $concepto->recibos_en_uso;
                                @endphp
                                <tr>
                                    <td class="cifra">{{ $concepto->orden }}</td>
                                    <td class="fw-semibold">
                                        {{ $concepto->nombre }}
                                        @if ($concepto->esProtegido())
                                            <span class="badge bg-info ms-1">Protegido</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($concepto->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>{{ $enUso }} {{ Str::plural('registro', $enUso) }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('conceptosGastoFijo.edit', $concepto) }}" class="btn btn-outline-secondary btn-sm" aria-label="Editar {{ $concepto->nombre }}">
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar
                                            </a>
                                            @if (! $concepto->esProtegido())
                                                @if ($enUso > 0)
                                                    <button type="button" class="btn btn-outline-danger btn-sm" disabled title="No se puede eliminar: está en uso en {{ $enUso }} {{ Str::plural('registro', $enUso) }}.">
                                                        <i class="bi bi-trash" aria-hidden="true"></i> Eliminar
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#eliminar-concepto-{{ $concepto->id }}" aria-label="Eliminar {{ $concepto->nombre }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i> Eliminar
                                                    </button>

                                                    <x-modal-bootstrap name="eliminar-concepto-{{ $concepto->id }}" focusable>
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fs-5 fw-bold">¿Eliminar "{{ $concepto->nombre }}"?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-0">Esta acción no se puede deshacer.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                                            <form method="POST" action="{{ route('conceptosGastoFijo.destroy', $concepto) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Sí, eliminar concepto</button>
                                                            </form>
                                                        </div>
                                                    </x-modal-bootstrap>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app-bootstrap>

<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Usuarios
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

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <p class="mb-0 text-secondary">
                    Cuentas con acceso al sistema. Sólo el perfil Master puede administrar usuarios.
                </p>
                <a href="{{ route('usuarios.create') }}" class="btn btn-primary text-nowrap">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar usuario
                </a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Correo electrónico</th>
                                <th scope="col">Perfil</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usuarios as $usuario)
                                @php($esCuentaPropia = $usuario->is(auth()->user()))
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $usuario->name }}
                                        @if ($esCuentaPropia)
                                            <span class="badge bg-light text-dark ms-1">Tú</span>
                                        @endif
                                    </td>
                                    <td>{{ $usuario->email }}</td>
                                    <td>
                                        <span class="badge {{ $usuario->perfil->claseBadge() }}">{{ $usuario->perfil->etiqueta() }}</span>
                                    </td>
                                    <td>
                                        @if ($usuario->estaActivo())
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-outline-secondary btn-sm" aria-label="Editar {{ $usuario->name }}">
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar
                                            </a>

                                            @if ($usuario->estaActivo())
                                                @unless ($esCuentaPropia)
                                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#desactivar-usuario-{{ $usuario->id }}" aria-label="Desactivar {{ $usuario->name }}">
                                                        <i class="bi bi-slash-circle" aria-hidden="true"></i> Desactivar
                                                    </button>
                                                @endunless
                                            @else
                                                <form method="POST" action="{{ route('usuarios.estado.update', $usuario) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="activo" value="1">
                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reactivar
                                                    </button>
                                                </form>
                                            @endif

                                            @unless ($esCuentaPropia)
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#eliminar-usuario-{{ $usuario->id }}" aria-label="Eliminar {{ $usuario->name }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i> Eliminar
                                                </button>
                                            @endunless
                                        </div>

                                        @unless ($esCuentaPropia)
                                            <x-modal-bootstrap name="desactivar-usuario-{{ $usuario->id }}" focusable>
                                                <div class="modal-header">
                                                    <h5 class="modal-title fs-5 fw-bold">¿Desactivar a "{{ $usuario->name }}"?</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p class="mb-0">La cuenta no podrá iniciar sesión hasta que se reactive. Se conserva toda su información y su historial.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                                    <form method="POST" action="{{ route('usuarios.estado.update', $usuario) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="activo" value="0">
                                                        <button type="submit" class="btn btn-warning">Sí, desactivar usuario</button>
                                                    </form>
                                                </div>
                                            </x-modal-bootstrap>

                                            <x-modal-bootstrap name="eliminar-usuario-{{ $usuario->id }}" focusable>
                                                <div class="modal-header">
                                                    <h5 class="modal-title fs-5 fw-bold">¿Eliminar a "{{ $usuario->name }}"?</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p class="mb-0">Esta acción no se puede deshacer. Si sólo quieres retirarle el acceso, desactiva la cuenta en su lugar.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Sí, eliminar usuario</button>
                                                    </form>
                                                </div>
                                            </x-modal-bootstrap>
                                        @endunless
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

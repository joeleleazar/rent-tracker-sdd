@props(['contrato'])

<div class="d-flex flex-column gap-3">
    @if ($contrato->documentos->first()->tipo_archivo === 'pdf')
        @php $documento = $contrato->documentos->first(); @endphp
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <a href="{{ route('contratos.documentos.show', [$contrato, $documento]) }}" target="_blank"
                   class="fs-5 fw-semibold">
                    {{ $documento->nombre_archivo }}
                </a>
                <button type="button" class="btn btn-danger btn-lg"
                        data-bs-toggle="modal"
                        data-bs-target="#confirmar-borrado-documento"
                        data-accion="{{ route('contratos.documentos.destroy', [$contrato, $documento]) }}">
                    Eliminar
                </button>
            </div>
        </div>
    @else
        <div class="row row-cols-2 row-cols-sm-3 g-3">
            @foreach ($contrato->documentos->sortBy('secuencia') as $documento)
                <div class="col">
                    <div class="card h-100">
                        <a href="{{ route('contratos.documentos.show', [$contrato, $documento]) }}" target="_blank">
                            <img src="{{ route('contratos.documentos.show', [$contrato, $documento]) }}"
                                 alt="{{ $documento->nombre_archivo }}"
                                 class="card-img-top object-fit-cover" style="height: 10rem;">
                        </a>
                        <div class="card-body p-2">
                            <button type="button" class="btn btn-danger btn-lg w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmar-borrado-documento"
                                    data-accion="{{ route('contratos.documentos.destroy', [$contrato, $documento]) }}">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{--
        Modal de confirmación de borrado, único y compartido por todas las
        miniaturas de arriba (Senior-First: FR-005, borrado nunca implícito).
        Ver resources/js/galeria-documentos.js para cómo se fija la acción
        del formulario según el botón que abrió el modal.
    --}}
    <x-modal-bootstrap name="confirmar-borrado-documento" focusable>
        <form method="POST">
            @csrf
            @method('DELETE')

            <div class="modal-body p-4">
                <h4 class="fs-4 fw-bold">¿Eliminar este documento?</h4>
                <p class="fs-5 mb-0">Esta acción no se puede deshacer.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger btn-lg">Sí, Eliminar</button>
            </div>
        </form>
    </x-modal-bootstrap>
</div>

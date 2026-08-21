{{--
    Sección de Representantes del Contrato (specs/003-representantes-contrato).

    - Modo "crear" ($contrato es null): editor dinámico con JS nativo (ver
      resources/js/representantes-contrato.js, reemplazo de la versión Alpine
      original), ya que el contrato aún no existe y los representantes se
      envían junto con el resto del formulario (arreglo `representantes[]`,
      con `principal_index` indicando cuál fila es la Principal). Se exige al
      menos una fila (FR-003).
    - Modo "gestionar" ($contrato existe): lista los representantes ya
      asociados con acciones inmediatas de alta/baja (idéntico patrón a
      "Documentos del Contrato"), persistidas de inmediato vía rutas
      dedicadas — no forma parte del formulario de edición del contrato (ver
      tasks.md de 003, sección Notes).
--}}

@if ($contrato === null)
    <div id="editor-representantes" class="card" data-buscar-url="{{ route('representantes.buscar') }}">
        <div class="card-body d-flex flex-column gap-3">
            <h3 class="fs-4 fw-bold">Representantes del Contrato</h3>
            <p class="fs-5 mb-0">
                Debe registrar al menos un representante. Si hay más de uno, señale cuál es el Principal.
            </p>

            <div data-filas-representantes class="d-flex flex-column gap-3">
                <div class="card fila-representante">
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <label class="form-label fw-semibold fs-5">DNI</label>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-lg campo-dni-fila" name="representantes[0][dni]" maxlength="8" inputmode="numeric">
                                <button type="button" class="btn btn-outline-secondary btn-lg btn-buscar-dni-fila">Buscar</button>
                            </div>
                            <input type="hidden" class="campo-representante-id-fila" name="representantes[0][representante_id]">
                        </div>

                        <div>
                            <label class="form-label fw-semibold fs-5">Apellidos</label>
                            <input type="text" class="form-control form-control-lg campo-apellidos-fila" name="representantes[0][apellidos]">
                        </div>

                        <div>
                            <label class="form-label fw-semibold fs-5">Nombres</label>
                            <input type="text" class="form-control form-control-lg campo-nombres-fila" name="representantes[0][nombres]">
                        </div>

                        <div>
                            <label class="form-label fw-semibold fs-5">Fecha de nacimiento</label>
                            <input type="date" class="form-control form-control-lg campo-fecha-nacimiento-fila" name="representantes[0][fecha_nacimiento]">
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="form-check d-flex align-items-center gap-2 fila-principal-wrapper d-none">
                                <input type="radio" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" name="principal_index" value="0" checked>
                                <label class="form-check-label fs-5">Representante Principal</label>
                            </div>

                            <button type="button" class="btn btn-danger btn-lg btn-quitar-fila-representante d-none">
                                Quitar Representante
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-lg" data-agregar-fila-representante>
                Agregar Otro Representante
            </button>
        </div>
    </div>

    {{-- Plantilla clonada por JS al agregar una fila (mismo marcado que la fila inicial de arriba). --}}
    <template id="plantilla-fila-representante">
        <div class="card fila-representante">
            <div class="card-body d-flex flex-column gap-3">
                <div>
                    <label class="form-label fw-semibold fs-5">DNI</label>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-lg campo-dni-fila" name="representantes[__index__][dni]" maxlength="8" inputmode="numeric">
                        <button type="button" class="btn btn-outline-secondary btn-lg btn-buscar-dni-fila">Buscar</button>
                    </div>
                    <input type="hidden" class="campo-representante-id-fila" name="representantes[__index__][representante_id]">
                </div>

                <div>
                    <label class="form-label fw-semibold fs-5">Apellidos</label>
                    <input type="text" class="form-control form-control-lg campo-apellidos-fila" name="representantes[__index__][apellidos]">
                </div>

                <div>
                    <label class="form-label fw-semibold fs-5">Nombres</label>
                    <input type="text" class="form-control form-control-lg campo-nombres-fila" name="representantes[__index__][nombres]">
                </div>

                <div>
                    <label class="form-label fw-semibold fs-5">Fecha de nacimiento</label>
                    <input type="date" class="form-control form-control-lg campo-fecha-nacimiento-fila" name="representantes[__index__][fecha_nacimiento]">
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check d-flex align-items-center gap-2 fila-principal-wrapper">
                        <input type="radio" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" name="principal_index" value="0">
                        <label class="form-check-label fs-5">Representante Principal</label>
                    </div>

                    <button type="button" class="btn btn-danger btn-lg btn-quitar-fila-representante">
                        Quitar Representante
                    </button>
                </div>
            </div>
        </div>
    </template>
@else
    <div class="card">
        <div class="card-body d-flex flex-column gap-3">
            <h3 class="fs-4 fw-bold">Representantes del Contrato</h3>

            @if ($contrato->representantes->isEmpty())
                <p class="fs-5 mb-0">Este contrato no tiene representantes asociados.</p>
            @else
                {{-- Grid de tarjetas individuales (specs/012, FR-005): ancho mínimo
                     consistente, dos por fila en pantallas medianas o más grandes. --}}
                <div class="row g-3">
                    @foreach ($contrato->representantes as $representante)
                        <div class="col-md-6" style="min-width: 200px;">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column gap-3">
                                <div>
                                    <p class="fs-5 fw-semibold mb-0">
                                        {{ $representante->nombreCompleto() }}
                                        @if ($representante->pivot->es_principal)
                                            <span class="badge text-bg-primary ms-2">Principal</span>
                                        @endif
                                    </p>
                                    <p class="fs-5 mb-0">DNI: {{ $representante->dni }}</p>
                                </div>

                                @if ($contrato->representantes->count() > 1)
                                    <x-danger-button
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#quitar-representante-{{ $representante->id }}"
                                    ><i class="bi bi-trash" aria-hidden="true"></i> Quitar Representante</x-danger-button>

                                    <x-modal-bootstrap name="quitar-representante-{{ $representante->id }}" focusable>
                                        <form method="POST" action="{{ route('contratos.representantes.destroy', [$contrato, $representante]) }}">
                                            @csrf
                                            @method('delete')

                                            <div class="modal-body p-4">
                                                <h2 class="fs-4 fw-bold">
                                                    ¿Quitar a "{{ $representante->nombreCompleto() }}" de este contrato?
                                                </h2>

                                                <p class="fs-5 mb-0">Esta acción no se puede deshacer.</p>
                                            </div>

                                            <div class="modal-footer">
                                                <x-secondary-button type="button" data-bs-dismiss="modal">
                                                    No, cancelar
                                                </x-secondary-button>

                                                <x-danger-button>
                                                    Sí, quitar representante
                                                </x-danger-button>
                                            </div>
                                        </form>
                                    </x-modal-bootstrap>
                                @else
                                    <p class="fs-5 mb-0">
                                        No se puede quitar: es el único representante del contrato.
                                    </p>
                                @endif
                            </div>
                        </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <x-primary-button
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#agregar-representante"
            ><i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar Otro Representante</x-primary-button>

            <x-modal-bootstrap name="agregar-representante" focusable>
                <form
                    method="POST"
                    action="{{ route('contratos.representantes.store', $contrato) }}"
                    id="formulario-agregar-representante"
                    data-buscar-url="{{ route('representantes.buscar') }}"
                >
                    @csrf
                    <input type="hidden" id="nuevo_representante_id" name="representante_id">

                    <div class="modal-body p-4 d-flex flex-column gap-3">
                        <h2 class="fs-4 fw-bold">Agregar Otro Representante</h2>

                        <div>
                            <x-input-label for="nuevo_dni" value="DNI" />
                            <div class="d-flex gap-2">
                                <x-text-input id="nuevo_dni" name="dni" maxlength="8" />
                                <button type="button" class="btn btn-outline-secondary btn-lg btn-buscar-dni-modal">Buscar</button>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="nuevo_apellidos" value="Apellidos" />
                            <x-text-input id="nuevo_apellidos" name="apellidos" />
                        </div>

                        <div>
                            <x-input-label for="nuevo_nombres" value="Nombres" />
                            <x-text-input id="nuevo_nombres" name="nombres" />
                        </div>

                        <div>
                            <x-input-label for="nueva_fecha_nacimiento" value="Fecha de nacimiento" />
                            <x-text-input id="nueva_fecha_nacimiento" name="fecha_nacimiento" type="date" />
                        </div>

                        <div class="form-check d-flex align-items-center gap-2">
                            <input type="checkbox" id="es_principal" name="es_principal" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;">
                            <label for="es_principal" class="form-check-label fs-5">Marcar como Representante Principal</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</x-secondary-button>
                        <x-primary-button>Guardar Representante</x-primary-button>
                    </div>
                </form>
            </x-modal-bootstrap>
        </div>
    </div>
@endif

{{--
    Sección de Inquilinos del Contrato (specs/003-representantes-contrato).

    Corrección 2026-08-23: el inquilino ES el representante del contrato; no
    existe una entidad "representante" separada. Un contrato puede tener
    varios inquilinos, pero exactamente uno debe ser el Principal.

    - Modo "crear" ($contrato es null): editor dinámico con JS nativo (ver
      resources/js/inquilinos-contrato.js), ya que el contrato aún no existe
      y los inquilinos se envían junto con el resto del formulario (arreglo
      `inquilinos[]`, con `principal_index` indicando cuál fila es la
      Principal). Se exige al menos una fila (FR-003).
    - Modo "gestionar" ($contrato existe): lista los inquilinos ya asociados
      con acciones inmediatas de alta/baja (idéntico patrón a "Documentos del
      Contrato"), persistidas de inmediato vía rutas dedicadas — no forma
      parte del formulario de edición del contrato (ver tasks.md de 003,
      sección Notes). Quitar al Principal cuando hay otros exige designar un
      reemplazo (FR-009).
--}}

@if ($contrato === null)
    <div id="editor-inquilinos" class="card" data-buscar-url="{{ route('inquilinos.buscar') }}">
        <div class="card-body d-flex flex-column gap-3">
            <h3 class="fs-4 fw-bold">Inquilinos del Contrato</h3>
            <p class="mb-0">
                Debe registrar al menos un inquilino. Si hay más de uno, señale cuál es el Principal.
            </p>

            <div data-filas-inquilinos class="d-flex flex-column gap-3">
                <div class="card fila-inquilino">
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <label class="form-label fw-semibold">DNI</label>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control campo-dni-fila" name="inquilinos[0][dni]" maxlength="8" inputmode="numeric">
                                <button type="button" class="btn btn-outline-secondary btn-buscar-dni-fila">Buscar</button>
                            </div>
                            <input type="hidden" class="campo-inquilino-id-fila" name="inquilinos[0][inquilino_id]">
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Apellidos</label>
                            <input type="text" class="form-control campo-apellidos-fila" name="inquilinos[0][apellidos]">
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Nombres</label>
                            <input type="text" class="form-control campo-nombres-fila" name="inquilinos[0][nombres]">
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Fecha de nacimiento</label>
                            <input type="date" class="form-control campo-fecha-nacimiento-fila" name="inquilinos[0][fecha_nacimiento]">
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="form-check d-flex align-items-center gap-2 fila-principal-wrapper d-none">
                                <input type="radio" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" name="principal_index" value="0" checked>
                                <label class="form-check-label">Inquilino Principal</label>
                            </div>

                            <button type="button" class="btn btn-danger btn-quitar-fila-inquilino d-none">
                                Quitar Inquilino
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary" data-agregar-fila-inquilino>
                Agregar Otro Inquilino
            </button>
        </div>
    </div>

    {{-- Plantilla clonada por JS al agregar una fila (mismo marcado que la fila inicial de arriba). --}}
    <template id="plantilla-fila-inquilino">
        <div class="card fila-inquilino">
            <div class="card-body d-flex flex-column gap-3">
                <div>
                    <label class="form-label fw-semibold">DNI</label>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control campo-dni-fila" name="inquilinos[__index__][dni]" maxlength="8" inputmode="numeric">
                        <button type="button" class="btn btn-outline-secondary btn-buscar-dni-fila">Buscar</button>
                    </div>
                    <input type="hidden" class="campo-inquilino-id-fila" name="inquilinos[__index__][inquilino_id]">
                </div>

                <div>
                    <label class="form-label fw-semibold">Apellidos</label>
                    <input type="text" class="form-control campo-apellidos-fila" name="inquilinos[__index__][apellidos]">
                </div>

                <div>
                    <label class="form-label fw-semibold">Nombres</label>
                    <input type="text" class="form-control campo-nombres-fila" name="inquilinos[__index__][nombres]">
                </div>

                <div>
                    <label class="form-label fw-semibold">Fecha de nacimiento</label>
                    <input type="date" class="form-control campo-fecha-nacimiento-fila" name="inquilinos[__index__][fecha_nacimiento]">
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check d-flex align-items-center gap-2 fila-principal-wrapper">
                        <input type="radio" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" name="principal_index" value="0">
                        <label class="form-check-label">Inquilino Principal</label>
                    </div>

                    <button type="button" class="btn btn-danger btn-quitar-fila-inquilino">
                        Quitar Inquilino
                    </button>
                </div>
            </div>
        </div>
    </template>
@else
    <div class="card">
        <div class="card-body d-flex flex-column gap-3">
            <h3 class="fs-4 fw-bold">Inquilinos del Contrato</h3>

            @if ($contrato->inquilinos->isEmpty())
                <p class="mb-0">Este contrato no tiene inquilinos asociados.</p>
            @else
                {{-- Grid de tarjetas individuales (specs/012, FR-005): ancho mínimo
                     consistente, dos por fila en pantallas medianas o más grandes. --}}
                <div class="row g-3">
                    @foreach ($contrato->inquilinos as $inquilino)
                        <div class="col-md-6" style="min-width: 200px;">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column gap-3">
                                <div>
                                    <p class="fw-semibold mb-0">
                                        {{ $inquilino->nombreCompleto() }}
                                        @if ($inquilino->pivot->es_principal)
                                            <span class="badge text-bg-primary ms-2">Principal</span>
                                        @endif
                                    </p>
                                    <p class="mb-0">DNI: {{ $inquilino->dni }}</p>
                                </div>

                                @if ($contrato->inquilinos->count() > 1)
                                    <x-danger-button
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#quitar-inquilino-{{ $inquilino->id }}"
                                    ><i class="bi bi-trash" aria-hidden="true"></i> Quitar Inquilino</x-danger-button>

                                    <x-modal-bootstrap name="quitar-inquilino-{{ $inquilino->id }}" focusable>
                                        <form method="POST" action="{{ route('contratos.inquilinos.destroy', [$contrato, $inquilino]) }}">
                                            @csrf
                                            @method('delete')

                                            <div class="modal-body p-4 d-flex flex-column gap-3">
                                                <h2 class="fs-4 fw-bold">
                                                    ¿Quitar a "{{ $inquilino->nombreCompleto() }}" de este contrato?
                                                </h2>

                                                @if ($inquilino->pivot->es_principal)
                                                    <p class="mb-0">
                                                        Este inquilino es el Principal. Debe designar un nuevo Principal entre los inquilinos restantes para poder quitarlo (FR-009).
                                                    </p>

                                                    <div>
                                                        <x-input-label for="nuevo_principal_id_{{ $inquilino->id }}" value="Nuevo Inquilino Principal" />
                                                        <select id="nuevo_principal_id_{{ $inquilino->id }}" name="nuevo_principal_id" class="form-select" required>
                                                            <option value="">Seleccione un reemplazo</option>
                                                            @foreach ($contrato->inquilinos as $otro)
                                                                @continue($otro->id === $inquilino->id)
                                                                <option value="{{ $otro->id }}">{{ $otro->nombreCompleto() }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @else
                                                    <p class="mb-0">Esta acción no se puede deshacer.</p>
                                                @endif
                                            </div>

                                            <div class="modal-footer">
                                                <x-secondary-button type="button" data-bs-dismiss="modal">
                                                    No, cancelar
                                                </x-secondary-button>

                                                <x-danger-button>
                                                    Sí, quitar inquilino
                                                </x-danger-button>
                                            </div>
                                        </form>
                                    </x-modal-bootstrap>
                                @else
                                    <p class="mb-0">
                                        No se puede quitar: es el único inquilino del contrato.
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
                data-bs-target="#agregar-inquilino"
            ><i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar Otro Inquilino</x-primary-button>

            <x-modal-bootstrap name="agregar-inquilino" focusable>
                <form
                    method="POST"
                    action="{{ route('contratos.inquilinos.store', $contrato) }}"
                    id="formulario-agregar-inquilino"
                    data-buscar-url="{{ route('inquilinos.buscar') }}"
                >
                    @csrf
                    <input type="hidden" id="nuevo_inquilino_id" name="inquilino_id">

                    <div class="modal-body p-4 d-flex flex-column gap-3">
                        <h2 class="fs-4 fw-bold">Agregar Otro Inquilino</h2>

                        <div>
                            <x-input-label for="nuevo_dni" value="DNI" />
                            <div class="d-flex gap-2">
                                <x-text-input id="nuevo_dni" name="dni" maxlength="8" />
                                <button type="button" class="btn btn-outline-secondary btn-buscar-dni-modal">Buscar</button>
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
                            <label for="es_principal" class="form-check-label">Marcar como Inquilino Principal</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</x-secondary-button>
                        <x-primary-button>Guardar Inquilino</x-primary-button>
                    </div>
                </form>
            </x-modal-bootstrap>
        </div>
    </div>
@endif

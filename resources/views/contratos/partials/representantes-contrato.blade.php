{{--
    Sección de Representantes del Contrato (specs/003-representantes-contrato).

    - Modo "crear" ($contrato es null): editor dinámico con Alpine.js, ya que el
      contrato aún no existe y los representantes se envían junto con el resto del
      formulario (arreglo `representantes[]`, con `principal_index` indicando cuál
      fila es la Principal). Se exige al menos una fila (FR-003).
    - Modo "gestionar" ($contrato existe): lista los representantes ya asociados con
      acciones inmediatas de alta/baja (idéntico patrón a "Documentos del Contrato"),
      persistidas de inmediato vía rutas dedicadas — no forma parte del formulario de
      edición del contrato (ver tasks.md de 003, sección Notes).
--}}

@if ($contrato === null)
    <div
        x-data="{
            filas: [{ apellidos: '', nombres: '', dni: '', fecha_nacimiento: '', representante_id: null }],
            principal: 0,
            agregarFila() {
                this.filas.push({ apellidos: '', nombres: '', dni: '', fecha_nacimiento: '', representante_id: null });
            },
            quitarFila(indice) {
                this.filas.splice(indice, 1);
                if (this.principal >= this.filas.length) {
                    this.principal = 0;
                }
            },
            async buscarPorDni(indice) {
                const dni = this.filas[indice].dni;
                if (!dni) return;
                const respuesta = await fetch(`{{ route('representantes.buscar') }}?q=${encodeURIComponent(dni)}`);
                const datos = await respuesta.json();
                if (datos.representantes && datos.representantes.length > 0) {
                    const encontrado = datos.representantes[0];
                    this.filas[indice].apellidos = encontrado.apellidos;
                    this.filas[indice].nombres = encontrado.nombres;
                    this.filas[indice].dni = encontrado.dni;
                    this.filas[indice].fecha_nacimiento = encontrado.fecha_nacimiento.substring(0, 10);
                    this.filas[indice].representante_id = encontrado.id;
                }
            },
        }"
        class="space-y-6 rounded-md border-2 border-gray-300 bg-white p-6"
    >
        <h3 class="text-xl font-bold text-gray-900">Representantes del Contrato</h3>
        <p class="text-lg text-gray-700">
            Debe registrar al menos un representante. Si hay más de uno, señale cuál es el Principal.
        </p>

        <template x-for="(fila, indice) in filas" :key="indice">
            <div class="space-y-4 rounded-md border-2 border-gray-300 p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="etiqueta-senior">DNI</label>
                        <div class="flex gap-2">
                            <input type="text" class="campo-senior" x-model="fila.dni" :name="'representantes[' + indice + '][dni]'" maxlength="8" inputmode="numeric">
                            <button type="button" class="btn-senior-secundario" @click="buscarPorDni(indice)">Buscar</button>
                        </div>
                        <input type="hidden" :name="'representantes[' + indice + '][representante_id]'" x-model="fila.representante_id">
                    </div>
                </div>

                <div>
                    <label class="etiqueta-senior">Apellidos</label>
                    <input type="text" class="campo-senior" x-model="fila.apellidos" :name="'representantes[' + indice + '][apellidos]'">
                </div>

                <div>
                    <label class="etiqueta-senior">Nombres</label>
                    <input type="text" class="campo-senior" x-model="fila.nombres" :name="'representantes[' + indice + '][nombres]'">
                </div>

                <div>
                    <label class="etiqueta-senior">Fecha de nacimiento</label>
                    <input type="date" class="campo-senior" x-model="fila.fecha_nacimiento" :name="'representantes[' + indice + '][fecha_nacimiento]'">
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 text-lg" x-show="filas.length > 1">
                        <input type="radio" name="principal_index" :value="indice" x-model.number="principal" class="h-6 w-6">
                        Representante Principal
                    </label>

                    <button type="button" class="btn-senior-peligro" x-show="filas.length > 1" @click="quitarFila(indice)">
                        Quitar Representante
                    </button>
                </div>
            </div>
        </template>

        <button type="button" class="btn-senior-primario" @click="agregarFila()">
            Agregar Otro Representante
        </button>
    </div>
@else
    <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
        <h3 class="text-xl font-bold text-gray-900">Representantes del Contrato</h3>

        @if ($contrato->representantes->isEmpty())
            <p class="text-lg text-gray-700">Este contrato no tiene representantes asociados.</p>
        @else
            <ul class="space-y-4">
                @foreach ($contrato->representantes as $representante)
                    <li class="flex flex-wrap items-center justify-between gap-4 rounded-md border-2 border-gray-300 p-4">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $representante->nombreCompleto() }}
                                @if ($representante->pivot->es_principal)
                                    <span class="ml-2 rounded-md border-2 border-blue-800 bg-blue-50 px-2 py-1 text-sm font-bold text-blue-900">Principal</span>
                                @endif
                            </p>
                            <p class="text-lg text-gray-700">DNI: {{ $representante->dni }}</p>
                        </div>

                        @if ($contrato->representantes->count() > 1)
                            <x-danger-button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'quitar-representante-{{ $representante->id }}')"
                            >Quitar Representante</x-danger-button>

                            <x-modal name="quitar-representante-{{ $representante->id }}" focusable>
                                <form method="POST" action="{{ route('contratos.representantes.destroy', [$contrato, $representante]) }}" class="p-6">
                                    @csrf
                                    @method('delete')

                                    <h2 class="text-xl font-bold text-gray-900">
                                        ¿Quitar a "{{ $representante->nombreCompleto() }}" de este contrato?
                                    </h2>

                                    <p class="mt-2 text-lg text-gray-700">Esta acción no se puede deshacer.</p>

                                    <div class="mt-6 flex justify-end gap-4">
                                        <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                            No, cancelar
                                        </x-secondary-button>

                                        <x-danger-button>
                                            Sí, quitar representante
                                        </x-danger-button>
                                    </div>
                                </form>
                            </x-modal>
                        @else
                            <p class="text-lg text-gray-700">
                                No se puede quitar: es el único representante del contrato.
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <x-primary-button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'agregar-representante')"
        >Agregar Otro Representante</x-primary-button>

        <x-modal name="agregar-representante" focusable>
            <form method="POST" action="{{ route('contratos.representantes.store', $contrato) }}" class="p-6 space-y-4"
                  x-data="{
                      representanteId: null,
                      async buscarPorDni(dni) {
                          if (!dni) return;
                          const respuesta = await fetch(`{{ route('representantes.buscar') }}?q=${encodeURIComponent(dni)}`);
                          const datos = await respuesta.json();
                          if (datos.representantes && datos.representantes.length > 0) {
                              const encontrado = datos.representantes[0];
                              this.$refs.apellidos.value = encontrado.apellidos;
                              this.$refs.nombres.value = encontrado.nombres;
                              this.$refs.fecha.value = encontrado.fecha_nacimiento.substring(0, 10);
                              this.representanteId = encontrado.id;
                          }
                      },
                  }"
            >
                @csrf
                <input type="hidden" name="representante_id" x-model="representanteId">

                <h2 class="text-xl font-bold text-gray-900">Agregar Otro Representante</h2>

                <div>
                    <x-input-label for="nuevo_dni" value="DNI" />
                    <div class="flex gap-2">
                        <x-text-input id="nuevo_dni" name="dni" maxlength="8" x-ref="dni" />
                        <button type="button" class="btn-senior-secundario" @click="buscarPorDni($refs.dni.value)">Buscar</button>
                    </div>
                </div>

                <div>
                    <x-input-label for="nuevo_apellidos" value="Apellidos" />
                    <x-text-input id="nuevo_apellidos" name="apellidos" x-ref="apellidos" />
                </div>

                <div>
                    <x-input-label for="nuevo_nombres" value="Nombres" />
                    <x-text-input id="nuevo_nombres" name="nombres" x-ref="nombres" />
                </div>

                <div>
                    <x-input-label for="nueva_fecha_nacimiento" value="Fecha de nacimiento" />
                    <x-text-input id="nueva_fecha_nacimiento" name="fecha_nacimiento" type="date" x-ref="fecha" />
                </div>

                <label class="flex items-center gap-2 text-lg">
                    <input type="checkbox" name="es_principal" value="1" class="h-6 w-6">
                    Marcar como Representante Principal
                </label>

                <div class="flex justify-end gap-4 pt-2">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <x-primary-button>Guardar Representante</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
@endif

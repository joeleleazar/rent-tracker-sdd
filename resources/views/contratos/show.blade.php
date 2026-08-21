<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Contrato #{{ $contrato->id }} — {{ $contrato->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        @if ($errors->any())
            <x-mensaje-alerta tipo="error">
                <ul class="list-disc space-y-1 pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <dl class="space-y-4">
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Locación</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->locacion->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Inquilino</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->inquilino->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Fecha de inicio</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->fecha_inicio->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Fecha de fin</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->fecha_fin->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Monto de renta</dt>
                    <dd class="text-lg text-gray-900">S/ {{ number_format((float) $contrato->monto_renta, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Garantía Entregada</dt>
                    <dd class="text-lg text-gray-900">
                        @if (! $contrato->tieneGarantia())
                            Sin garantía registrada
                        @else
                            S/ {{ number_format((float) $contrato->monto_garantia, 2) }}
                            @if ($contrato->fecha_entrega_garantia)
                                — entregada el {{ $contrato->fecha_entrega_garantia->format('d/m/Y') }}
                            @endif
                            @if ($contrato->medio_entrega_garantia)
                                ({{ ucfirst($contrato->medio_entrega_garantia) }})
                            @endif
                            <span class="ml-2 rounded-md border-2 border-gray-700 bg-gray-100 px-2 py-1 text-sm font-bold">
                                {{ $contrato->garantiaResuelta() ? 'Resuelta' : 'Entregada' }}
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Estado</dt>
                    <dd class="text-lg text-gray-900">
                        <span class="rounded-md border-2 border-gray-700 bg-gray-100 px-3 py-1 font-semibold">
                            {{ ucfirst($contrato->estado) }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-4 pt-2">
                <a href="{{ route('contratos.edit', $contrato) }}" class="btn-senior-primario">Editar Contrato</a>
                <a href="{{ route('contratos.index', $contrato->locacion) }}" class="btn-senior-secundario">Ver Historial</a>
                <a href="{{ route('locaciones.recibos.index', $contrato->locacion) }}" class="btn-senior-secundario">Ver Recibos</a>
                <a href="{{ route('locaciones.lecturas.index', $contrato->locacion) }}" class="btn-senior-secundario">Ver Lecturas de Medidor</a>
            </div>
        </div>

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <h3 class="text-xl font-bold text-gray-900">Costos Fijos de Referencia</h3>
            <p class="text-lg text-gray-700">
                Estos valores se usan como referencia inicial editable al generar un recibo; no afectan a recibos ya emitidos.
            </p>

            <form method="POST" action="{{ route('contratos.costos.update', $contrato) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="costo_agua" value="Costo de Agua" />
                    <x-text-input id="costo_agua" name="costo_agua" type="number" step="0.01" min="0" :value="old('costo_agua', $contrato->costo_agua)" />
                    <x-input-error :messages="$errors->get('costo_agua')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="costo_luz" value="Costo de Luz" />
                    <x-text-input id="costo_luz" name="costo_luz" type="number" step="0.01" min="0" :value="old('costo_luz', $contrato->costo_luz)" />
                    <x-input-error :messages="$errors->get('costo_luz')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="costo_pasadizo" value="Costo de Pasadizo" />
                    <x-text-input id="costo_pasadizo" name="costo_pasadizo" type="number" step="0.01" min="0" :value="old('costo_pasadizo', $contrato->costo_pasadizo)" />
                    <x-input-error :messages="$errors->get('costo_pasadizo')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="costo_seguridad" value="Costo de Seguridad" />
                    <x-text-input id="costo_seguridad" name="costo_seguridad" type="number" step="0.01" min="0" :value="old('costo_seguridad', $contrato->costo_seguridad)" />
                    <x-input-error :messages="$errors->get('costo_seguridad')" class="mt-2" />
                </div>

                <x-primary-button>Guardar Costos del Contrato</x-primary-button>
            </form>
        </div>

        @if ($contrato->tieneGarantia())
            <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
                <h3 class="text-xl font-bold text-gray-900">Resolución de Garantía</h3>

                @if ($contrato->garantiaResuelta())
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-lg font-semibold text-gray-700">Monto Devuelto</dt>
                            <dd class="text-lg text-gray-900">S/ {{ number_format((float) $contrato->monto_devuelto_garantia, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-lg font-semibold text-gray-700">Monto Retenido</dt>
                            <dd class="text-lg text-gray-900">S/ {{ number_format((float) $contrato->monto_retenido_garantia, 2) }}</dd>
                        </div>
                        @if ($contrato->motivo_retencion_garantia)
                            <div>
                                <dt class="text-lg font-semibold text-gray-700">Motivo de Retención</dt>
                                <dd class="text-lg text-gray-900">{{ $contrato->motivo_retencion_garantia }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-lg font-semibold text-gray-700">Fecha de Resolución</dt>
                            <dd class="text-lg text-gray-900">{{ $contrato->fecha_resolucion_garantia->format('d/m/Y') }}</dd>
                        </div>
                    </dl>

                    <x-secondary-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'corregir-resolucion-garantia')"
                    >Corregir Resolución de Garantía</x-secondary-button>

                    <x-modal name="corregir-resolucion-garantia" focusable>
                        <form method="POST" action="{{ route('contratos.garantia.resolucion', $contrato) }}" class="space-y-4 p-6">
                            @csrf
                            <input type="hidden" name="confirmado" value="1">

                            <h2 class="text-xl font-bold text-gray-900">¿Corregir la resolución de garantía ya registrada?</h2>
                            <p class="text-lg text-gray-700">Esta acción reemplazará los montos y el motivo ya guardados.</p>

                            <div>
                                <x-input-label for="monto_devuelto_garantia_modal" value="Monto Devuelto" />
                                <x-text-input id="monto_devuelto_garantia_modal" name="monto_devuelto_garantia" type="number" step="0.01" min="0" :value="old('monto_devuelto_garantia', $contrato->monto_devuelto_garantia)" required />
                            </div>

                            <div>
                                <x-input-label for="monto_retenido_garantia_modal" value="Monto Retenido" />
                                <x-text-input id="monto_retenido_garantia_modal" name="monto_retenido_garantia" type="number" step="0.01" min="0" :value="old('monto_retenido_garantia', $contrato->monto_retenido_garantia)" required />
                            </div>

                            <div>
                                <x-input-label for="motivo_retencion_garantia_modal" value="Motivo de Retención (obligatorio si hay retención)" />
                                <textarea id="motivo_retencion_garantia_modal" name="motivo_retencion_garantia" class="campo-senior">{{ old('motivo_retencion_garantia', $contrato->motivo_retencion_garantia) }}</textarea>
                            </div>

                            <div class="flex justify-end gap-4 pt-2">
                                <x-secondary-button type="button" x-on:click="$dispatch('close')">No, cancelar</x-secondary-button>
                                <x-primary-button>Sí, corregir resolución</x-primary-button>
                            </div>
                        </form>
                    </x-modal>
                @else
                    <p class="text-lg text-gray-700">
                        Registre cómo se resolvió la garantía de S/ {{ number_format((float) $contrato->monto_garantia, 2) }} al finalizar el contrato.
                    </p>

                    <form method="POST" action="{{ route('contratos.garantia.resolucion', $contrato) }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="monto_devuelto_garantia" value="Monto Devuelto" />
                            <x-text-input id="monto_devuelto_garantia" name="monto_devuelto_garantia" type="number" step="0.01" min="0" :value="old('monto_devuelto_garantia')" required />
                            <x-input-error :messages="$errors->get('monto_devuelto_garantia')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="monto_retenido_garantia" value="Monto Retenido" />
                            <x-text-input id="monto_retenido_garantia" name="monto_retenido_garantia" type="number" step="0.01" min="0" :value="old('monto_retenido_garantia', 0)" required />
                            <x-input-error :messages="$errors->get('monto_retenido_garantia')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="motivo_retencion_garantia" value="Motivo de Retención (obligatorio si hay retención)" />
                            <textarea id="motivo_retencion_garantia" name="motivo_retencion_garantia" class="campo-senior">{{ old('motivo_retencion_garantia') }}</textarea>
                            <x-input-error :messages="$errors->get('motivo_retencion_garantia')" class="mt-2" />
                        </div>

                        <x-primary-button>Registrar Resolución de Garantía</x-primary-button>
                    </form>
                @endif
            </div>
        @endif

        @include('contratos.partials.representantes-contrato', ['contrato' => $contrato])

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <h3 class="text-xl font-bold text-gray-900">Documentos del Contrato</h3>

            @php
                $tienePdf = $contrato->documentos->contains('tipo_archivo', 'pdf');
                $totalImagenes = $contrato->documentos->where('tipo_archivo', 'imagen')->count();
            @endphp

            @if ($contrato->documentos->isNotEmpty())
                @include('contratos.partials.galeria-documentos', ['contrato' => $contrato])
            @endif

            @unless ($tienePdf)
                <form method="POST" action="{{ route('contratos.documentos.store', $contrato) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="flex flex-wrap gap-4">
                        @if ($totalImagenes === 0)
                            <label class="btn-senior-primario cursor-pointer">
                                Seleccionar PDF del Contrato
                                <input type="file" name="archivo_pdf" accept="application/pdf" class="hidden" onchange="this.form.requestSubmit()">
                            </label>
                        @endif
                        @if ($totalImagenes < 10)
                            <label class="btn-senior-primario cursor-pointer">
                                Subir Foto de Página
                                <input type="file" name="archivo_imagenes[]" accept="image/jpeg,image/png" multiple class="hidden" onchange="this.form.requestSubmit()">
                            </label>
                        @endif
                    </div>
                </form>
            @endunless
        </div>
    </div>
</x-app-layout>

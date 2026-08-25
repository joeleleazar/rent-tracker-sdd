<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Contrato #{{ $contrato->id }} — {{ $contrato->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
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

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-semibold">Locación</dt>
                        <dd class="col-sm-8">{{ $contrato->locacion->nombre }}</dd>

                        <dt class="col-sm-4 fw-semibold">Inquilino Principal</dt>
                        <dd class="col-sm-8">{{ $contrato->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}</dd>

                        <dt class="col-sm-4 fw-semibold">Fecha de inicio</dt>
                        <dd class="col-sm-8">{{ $contrato->fecha_inicio->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4 fw-semibold">Fecha de fin</dt>
                        <dd class="col-sm-8">{{ $contrato->fecha_fin->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4 fw-semibold">Monto de renta</dt>
                        <dd class="col-sm-8 cifra">S/ {{ number_format((float) $contrato->monto_renta, 2) }}</dd>

                        <dt class="col-sm-4 fw-semibold">Garantía Entregada</dt>
                        <dd class="col-sm-8 cifra">
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
                                <span class="badge text-bg-secondary ms-2">
                                    {{ $contrato->garantiaResuelta() ? 'Resuelta' : 'Entregada' }}
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 fw-semibold">Estado</dt>
                        <dd class="col-sm-8">
                            <span class="badge text-bg-secondary">
                                {{ ucfirst($contrato->estado) }}
                            </span>
                        </dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-3 pt-2">
                        <a href="{{ route('contratos.edit', $contrato) }}" class="btn btn-primary"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar Contrato</a>
                        <a href="{{ route('contratos.index', $contrato->locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history" aria-hidden="true"></i> Ver Historial</a>
                        <a href="{{ route('locaciones.recibos.index', $contrato->locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-receipt" aria-hidden="true"></i> Ver Recibos</a>
                        <a href="{{ route('locaciones.lecturas.index', $contrato->locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-speedometer2" aria-hidden="true"></i> Ver Lecturas de Medidor</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold">Costos Fijos de Referencia</h3>
                    <p class="mb-0">
                        Estos valores se usan como referencia inicial editable al generar un recibo; no afectan a recibos ya emitidos.
                    </p>

                    <form method="POST" action="{{ route('contratos.costos.update', $contrato) }}" class="d-flex flex-column gap-3 costos-fijos-grid">
                        @csrf
                        @method('PATCH')

                        <div class="row g-4">
                            <div class="col-md-6">
                                <x-input-label for="costo_agua" value="Costo de Agua" />
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <x-text-input id="costo_agua" name="costo_agua" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_agua', $contrato->costo_agua)" />
                                </div>
                                <x-input-error :messages="$errors->get('costo_agua')" class="mt-2" />
                            </div>

                            <div class="col-md-6">
                                <x-input-label for="costo_luz" value="Costo de Luz" />
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <x-text-input id="costo_luz" name="costo_luz" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_luz', $contrato->costo_luz)" />
                                </div>
                                <x-input-error :messages="$errors->get('costo_luz')" class="mt-2" />
                            </div>

                            <div class="col-md-6">
                                <x-input-label for="costo_pasadizo" value="Costo de Pasadizo" />
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <x-text-input id="costo_pasadizo" name="costo_pasadizo" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_pasadizo', $contrato->costo_pasadizo)" />
                                </div>
                                <x-input-error :messages="$errors->get('costo_pasadizo')" class="mt-2" />
                            </div>

                            <div class="col-md-6">
                                <x-input-label for="costo_seguridad" value="Costo de Seguridad" />
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <x-text-input id="costo_seguridad" name="costo_seguridad" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_seguridad', $contrato->costo_seguridad)" />
                                </div>
                                <x-input-error :messages="$errors->get('costo_seguridad')" class="mt-2" />
                            </div>

                            <div class="col-md-6">
                                <x-input-label for="costo_total_referencia_show" value="Total de Referencia" />
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input id="costo_total_referencia_show" type="text" class="form-control costo-fijo-total" readonly value="0.00">
                                </div>
                                <small class="text-secondary d-block mt-2">Suma de los 4 costos de arriba</small>
                            </div>
                        </div>

                        <x-primary-button class="align-self-start">Guardar Costos del Contrato</x-primary-button>
                    </form>
                </div>
            </div>

            @if ($contrato->tieneGarantia())
                <div class="card">
                    <div class="card-body d-flex flex-column gap-3">
                        <h3 class="fs-4 fw-bold">Resolución de Garantía</h3>

                        @if ($contrato->garantiaResuelta())
                            <dl class="row mb-0">
                                <dt class="col-sm-4 fw-semibold">Monto Devuelto</dt>
                                <dd class="col-sm-8">S/ {{ number_format((float) $contrato->monto_devuelto_garantia, 2) }}</dd>

                                <dt class="col-sm-4 fw-semibold">Monto Retenido</dt>
                                <dd class="col-sm-8">S/ {{ number_format((float) $contrato->monto_retenido_garantia, 2) }}</dd>

                                @if ($contrato->motivo_retencion_garantia)
                                    <dt class="col-sm-4 fw-semibold">Motivo de Retención</dt>
                                    <dd class="col-sm-8">{{ $contrato->motivo_retencion_garantia }}</dd>
                                @endif

                                <dt class="col-sm-4 fw-semibold">Fecha de Resolución</dt>
                                <dd class="col-sm-8">{{ $contrato->fecha_resolucion_garantia->format('d/m/Y') }}</dd>
                            </dl>

                            <x-secondary-button
                                type="button"
                                class="align-self-start"
                                data-bs-toggle="modal"
                                data-bs-target="#corregir-resolucion-garantia"
                            >Corregir Resolución de Garantía</x-secondary-button>

                            <x-modal-bootstrap name="corregir-resolucion-garantia" focusable>
                                <form method="POST" action="{{ route('contratos.garantia.resolucion', $contrato) }}">
                                    @csrf
                                    <input type="hidden" name="confirmado" value="1">

                                    <div class="modal-body p-4 d-flex flex-column gap-3">
                                        <h2 class="fs-4 fw-bold">¿Corregir la resolución de garantía ya registrada?</h2>
                                        <p class="mb-0">Esta acción reemplazará los montos y el motivo ya guardados.</p>

                                        <div>
                                            <x-input-label for="monto_devuelto_garantia_modal" value="Monto Devuelto" />
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <x-text-input id="monto_devuelto_garantia_modal" name="monto_devuelto_garantia" type="number" step="0.01" min="0" :value="old('monto_devuelto_garantia', $contrato->monto_devuelto_garantia)" required />
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label for="monto_retenido_garantia_modal" value="Monto Retenido" />
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <x-text-input id="monto_retenido_garantia_modal" name="monto_retenido_garantia" type="number" step="0.01" min="0" :value="old('monto_retenido_garantia', $contrato->monto_retenido_garantia)" required />
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label for="motivo_retencion_garantia_modal" value="Motivo de Retención (obligatorio si hay retención)" />
                                            <textarea id="motivo_retencion_garantia_modal" name="motivo_retencion_garantia" class="form-control">{{ old('motivo_retencion_garantia', $contrato->motivo_retencion_garantia) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                        <x-primary-button>Sí, corregir resolución</x-primary-button>
                                    </div>
                                </form>
                            </x-modal-bootstrap>
                        @else
                            <p class="mb-0">
                                Registre cómo se resolvió la garantía de S/ {{ number_format((float) $contrato->monto_garantia, 2) }} al finalizar el contrato.
                            </p>

                            <form method="POST" action="{{ route('contratos.garantia.resolucion', $contrato) }}" class="d-flex flex-column gap-3">
                                @csrf

                                <div>
                                    <x-input-label for="monto_devuelto_garantia" value="Monto Devuelto" />
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <x-text-input id="monto_devuelto_garantia" name="monto_devuelto_garantia" type="number" step="0.01" min="0" :value="old('monto_devuelto_garantia')" required />
                                    </div>
                                    <x-input-error :messages="$errors->get('monto_devuelto_garantia')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="monto_retenido_garantia" value="Monto Retenido" />
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <x-text-input id="monto_retenido_garantia" name="monto_retenido_garantia" type="number" step="0.01" min="0" :value="old('monto_retenido_garantia', 0)" required />
                                    </div>
                                    <x-input-error :messages="$errors->get('monto_retenido_garantia')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="motivo_retencion_garantia" value="Motivo de Retención (obligatorio si hay retención)" />
                                    <textarea id="motivo_retencion_garantia" name="motivo_retencion_garantia" class="form-control">{{ old('motivo_retencion_garantia') }}</textarea>
                                    <x-input-error :messages="$errors->get('motivo_retencion_garantia')" class="mt-2" />
                                </div>

                                <x-primary-button class="align-self-start">Registrar Resolución de Garantía</x-primary-button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @include('contratos.partials.inquilinos-contrato', ['contrato' => $contrato])

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold">Documentos del Contrato</h3>

                    @php
                        $tienePdf = $contrato->documentos->contains('tipo_archivo', 'pdf');
                        $totalImagenes = $contrato->documentos->where('tipo_archivo', 'imagen')->count();
                    @endphp

                    @if ($contrato->documentos->isNotEmpty())
                        @include('contratos.partials.galeria-documentos', ['contrato' => $contrato])
                    @endif

                    @unless ($tienePdf)
                        {{-- Dropzone visual (specs/012, FR-001): solo presentación, sin
                             arrastrar-soltar funcional (no exigido por ningún Acceptance
                             Scenario) — ver research.md §7. --}}
                        <div class="border border-dashed rounded-3 p-4 text-center">
                            <p class="fw-semibold mb-3">Seleccionar Documento</p>
                            <form method="POST" action="{{ route('contratos.documentos.store', $contrato) }}" enctype="multipart/form-data" class="d-flex flex-column gap-3">
                                @csrf
                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    @if ($totalImagenes === 0)
                                        <label class="btn btn-outline-primary mb-0" style="cursor: pointer; min-height: 60px;">
                                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Seleccionar PDF del Contrato (máx 15MB)
                                            <input type="file" name="archivo_pdf" accept="application/pdf" class="d-none" onchange="this.form.requestSubmit()">
                                        </label>
                                    @endif
                                    @if ($totalImagenes < 10)
                                        <label class="btn btn-outline-primary mb-0" style="cursor: pointer; min-height: 60px;">
                                            <i class="bi bi-camera" aria-hidden="true"></i> Subir Fotos de Páginas (máx 10, 5MB c/u)
                                            <input type="file" name="archivo_imagenes[]" accept="image/jpeg,image/png" multiple class="d-none" onchange="this.form.requestSubmit()">
                                        </label>
                                    @endif
                                </div>
                                <p class="text-secondary mb-0">O arrastra archivos aquí</p>
                            </form>
                        </div>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/inquilinos-contrato.js', 'resources/js/galeria-documentos.js', 'resources/js/costos-fijos-contrato.js'])
    @endpush
</x-layouts.app-bootstrap>

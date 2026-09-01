@php
    // Calculado antes del layout: se usa tanto en el x-slot name="header" (badge
    // de estado junto al título) como en la línea "Estado" del resumen más abajo.
    $claseEstado = match ($recibo->estado) {
        'pagado' => 'text-bg-success',
        'anulado' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
@endphp

<x-layouts.app-bootstrap>
    <x-slot name="header">
        <nav aria-label="breadcrumb" class="breadcrumb-discreta small mb-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('recibos.registroMasivo.index') }}" class="text-decoration-none">Emitir Recibos</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Recibo #{{ $recibo->id }}</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h2 class="fs-2 fw-bold mb-0 d-flex flex-wrap align-items-center gap-2">
                Recibo #{{ $recibo->id }} — {{ $recibo->locacion->nombre }}
                <span class="badge {{ $claseEstado }}">{{ ucfirst($recibo->estado) }}</span>
            </h2>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('locaciones.recibos.index', $recibo->locacion) }}" class="btn btn-link text-secondary text-decoration-none">
                    <i class="bi bi-clock-history" aria-hidden="true"></i> Ver Historial
                </a>
                {{-- hx-boost="false": el comprobante es una página standalone con su
                     propio <head>/CSS (ver comprobante.blade.php), no el layout compartido;
                     debe cargarse con una navegación clásica completa, no un swap parcial. --}}
                <a href="{{ route('recibos.comprobante', $recibo) }}" class="btn btn-outline-secondary" hx-boost="false">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Ver Comprobante
                </a>
                <a href="{{ route('recibos.edit', $recibo) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar Recibo
                </a>
            </div>
        </div>
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

            {{--
                specs/038: dos columnas — resumen del recibo a la izquierda, gestión de pagos y
                estado del recibo a la derecha (research.md Decisión 1). Se apila por debajo de
                `lg` (992px) sin CSS a medida, considerando el sidebar fijo del layout.
            --}}
            <div class="row g-3">
            <div class="col-lg-7">
            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="titulo-seccion mb-0">Resumen del Recibo</h3>

                    <div class="detalle-recibo__fichas">
                        <div class="detalle-recibo__ficha">
                            <span class="detalle-recibo__ficha-icono"><i class="bi bi-house" aria-hidden="true"></i></span>
                            <div>
                                <div class="detalle-recibo__ficha-etiqueta">Locación</div>
                                <div class="fw-semibold">{{ $recibo->locacion->nombre }}</div>
                            </div>
                        </div>
                        <div class="detalle-recibo__ficha">
                            <span class="detalle-recibo__ficha-icono"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                            <div>
                                <div class="detalle-recibo__ficha-etiqueta">Periodo</div>
                                <div class="fw-semibold">{{ $recibo->periodo->translatedFormat('F Y') }}</div>
                            </div>
                        </div>
                        <div class="detalle-recibo__ficha">
                            <span class="detalle-recibo__ficha-icono"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                            <div>
                                <div class="detalle-recibo__ficha-etiqueta">Emisión</div>
                                <div class="fw-semibold">{{ $recibo->fecha_emision->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="detalle-recibo__linea">
                            <span class="text-secondary">Estado</span>
                            <span class="badge {{ $claseEstado }}">{{ ucfirst($recibo->estado) }}</span>
                        </div>

                        @if ($recibo->monto_renta !== null)
                            <div class="detalle-recibo__linea">
                                <span class="text-secondary">Renta</span>
                                <span class="fw-semibold cifra">S/ {{ number_format((float) $recibo->monto_renta, 2) }}</span>
                            </div>
                        @endif
                        @foreach ($recibo->conceptos->sortBy('conceptoGastoFijo.orden') as $reciboConcepto)
                            <div class="detalle-recibo__linea">
                                <span class="text-secondary">{{ $reciboConcepto->conceptoGastoFijo?->nombre ?? 'Concepto eliminado' }}</span>
                                <span class="fw-semibold cifra">S/ {{ number_format((float) $reciboConcepto->monto, 2) }}</span>
                            </div>
                        @endforeach

                        <div class="detalle-recibo__linea-total">
                            <span class="titulo-seccion mb-0">Total</span>
                            <span class="fw-bold cifra fs-5 text-primary">S/ {{ number_format($recibo->total(), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="col-lg-5 d-flex flex-column gap-3">
            @if ($recibo->estado !== 'anulado')
                <div class="card">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h3 class="titulo-seccion mb-0">Pagos</h3>
                            @if ($recibo->estado === 'pagado')
                                <span class="badge bg-success">Pagado en su totalidad</span>
                            @else
                                <span class="badge bg-warning">
                                    S/ {{ number_format($recibo->montoPagado(), 2) }} de S/ {{ number_format($recibo->total(), 2) }} pagado
                                </span>
                            @endif
                        </div>

                        <x-barra-progreso-pago :monto-pagado="$recibo->montoPagado()" :monto-total="$recibo->total()" />

                        {{--
                            specs/032 (FR-006): el estado Pendiente/Pagado ya no se elige a mano —
                            se recalcula automáticamente a partir de la suma de estos pagos
                            (ServicioGestionPagosRecibo). Solo Anular/Reactivar siguen siendo
                            transiciones manuales, más abajo.
                        --}}
                        @if ($recibo->pagos->isEmpty())
                            <p class="text-secondary mb-0">Todavía no se registró ningún pago para este recibo.</p>
                        @else
                            <div class="d-flex flex-column gap-2">
                                @foreach ($recibo->pagos->sortByDesc('fecha_pago') as $pago)
                                    <div class="pago-item d-flex align-items-start gap-3">
                                        <span class="pago-item__icono {{ $pago->tieneEvidencia() ? 'pago-item__icono--con-evidencia' : '' }}">
                                            <i class="bi {{ $pago->tieneEvidencia() ? 'bi-check-lg' : 'bi-cash-stack' }}" aria-hidden="true"></i>
                                        </span>

                                        <div class="flex-grow-1">
                                            <div class="fw-semibold cifra">S/ {{ number_format((float) $pago->monto, 2) }}</div>
                                            <div class="text-secondary small">
                                                {{ $pago->fecha_pago->format('d/m/Y') }}
                                                @if ($pago->registradoPor)
                                                    · {{ $pago->registradoPor->name }}
                                                @endif
                                            </div>
                                            {{-- specs/035: evidencia (imagen o PDF) del comprobante ya firmado — un único
                                                 archivo por pago, se reemplaza al subir uno nuevo (FR-007). --}}
                                            <div class="mt-1">
                                                @if ($pago->tieneEvidencia())
                                                    <span class="badge bg-success">Con evidencia</span>
                                                @else
                                                    <span class="badge bg-secondary">Sin evidencia</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="dropdown flex-shrink-0">
                                            {{--
                                                specs/041: el ícono es refuerzo visual de la etiqueta "Más",
                                                nunca su reemplazo (Principio VI) — a diferencia de un botón
                                                "⋮" sin texto visible.
                                            --}}
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Más acciones para este pago">
                                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i> Más
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="{{ route('pagos.comprobante', $pago) }}" class="dropdown-item" hx-boost="false">
                                                        <i class="bi bi-receipt me-2" aria-hidden="true"></i>Ver Comprobante
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editar-pago-{{ $pago->id }}">
                                                        <i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Editar
                                                    </button>
                                                </li>
                                                @if ($pago->tieneEvidencia())
                                                    <li>
                                                        <a href="{{ route('pagos.evidencia.show', $pago) }}" class="dropdown-item" hx-boost="false" target="_blank">
                                                            <i class="bi bi-file-earmark-check me-2" aria-hidden="true"></i>Ver Evidencia
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#subir-evidencia-pago-{{ $pago->id }}">
                                                            <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Reemplazar Evidencia
                                                        </button>
                                                    </li>
                                                @else
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#subir-evidencia-pago-{{ $pago->id }}">
                                                            <i class="bi bi-upload me-2" aria-hidden="true"></i>Subir Evidencia
                                                        </button>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#eliminar-pago-{{ $pago->id }}">
                                                        <i class="bi bi-trash me-2" aria-hidden="true"></i>Eliminar
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @foreach ($recibo->pagos as $pago)
                                <x-modal-bootstrap name="subir-evidencia-pago-{{ $pago->id }}" focusable>
                                    <form method="POST" action="{{ route('pagos.evidencia.store', $pago) }}" enctype="multipart/form-data">
                                        @csrf

                                        <div class="modal-body p-4 d-flex flex-column gap-3">
                                            <h2 class="fs-4 fw-bold mb-0">{{ $pago->tieneEvidencia() ? 'Reemplazar' : 'Subir' }} Evidencia del Pago</h2>
                                            <p class="mb-0 text-secondary">
                                                Foto o escaneo del comprobante de este pago ya firmado por quien lo recibió (JPG, PNG o PDF, hasta 10 MB).
                                            </p>
                                            <div>
                                                <x-input-label for="archivo-{{ $pago->id }}" value="Archivo" />
                                                <input id="archivo-{{ $pago->id }}" name="archivo" type="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                            <x-primary-button>Subir</x-primary-button>
                                        </div>
                                    </form>
                                </x-modal-bootstrap>
                            @endforeach

                            @foreach ($recibo->pagos as $pago)
                                <x-modal-bootstrap name="editar-pago-{{ $pago->id }}" focusable>
                                    <form method="POST" action="{{ route('pagos.update', $pago) }}">
                                        @csrf
                                        @method('put')

                                        <div class="modal-body p-4 d-flex flex-column gap-3">
                                            <h2 class="fs-4 fw-bold mb-0">Editar Pago</h2>
                                            <div>
                                                <x-input-label for="monto-{{ $pago->id }}" value="Monto del Pago (S/)" />
                                                <div class="input-group">
                                                    <span class="input-group-text">S/</span>
                                                    <x-text-input id="monto-{{ $pago->id }}" name="monto" type="number" step="0.01" min="0.01" :value="$pago->monto" required />
                                                </div>
                                            </div>
                                            <div>
                                                <x-input-label for="fecha_pago-{{ $pago->id }}" value="Fecha del Pago" />
                                                <x-text-input id="fecha_pago-{{ $pago->id }}" name="fecha_pago" type="date" :value="$pago->fecha_pago->format('Y-m-d')" max="{{ now()->format('Y-m-d') }}" required />
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                            <x-primary-button>Guardar Cambios</x-primary-button>
                                        </div>
                                    </form>
                                </x-modal-bootstrap>

                                <x-modal-bootstrap name="eliminar-pago-{{ $pago->id }}" focusable>
                                    <form method="POST" action="{{ route('pagos.destroy', $pago) }}">
                                        @csrf
                                        @method('delete')

                                        <div class="modal-body p-4">
                                            <h2 class="fs-4 fw-bold">¿Eliminar este pago?</h2>
                                            <p class="mb-0">
                                                Se eliminará el pago de S/ {{ number_format((float) $pago->monto, 2) }} del
                                                {{ $pago->fecha_pago->format('d/m/Y') }}, y el avance de pago del recibo se recalculará.
                                            </p>
                                        </div>

                                        <div class="modal-footer">
                                            <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                            <x-danger-button>Sí, eliminar pago</x-danger-button>
                                        </div>
                                    </form>
                                </x-modal-bootstrap>
                            @endforeach
                        @endif

                        @if (! $recibo->estaPagadoPorCompleto())
                            <button type="button" class="btn btn-primary align-self-start" data-bs-toggle="modal" data-bs-target="#registrar-pago">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar Pago
                            </button>

                            <x-modal-bootstrap name="registrar-pago" :show="$errors->has('monto') || $errors->has('fecha_pago')" focusable>
                                <form method="POST" action="{{ route('pagos.store', $recibo) }}">
                                    @csrf

                                    <div class="modal-body p-4 d-flex flex-column gap-3">
                                        <h2 class="fs-4 fw-bold mb-0">Registrar Pago</h2>
                                        <div>
                                            <x-input-label for="monto" value="Monto del Pago (S/)" />
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <x-text-input id="monto" name="monto" type="number" step="0.01" min="0.01" max="{{ $recibo->saldoPendiente() }}" :value="old('monto')" required />
                                            </div>
                                            <x-input-error :messages="$errors->get('monto')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="fecha_pago" value="Fecha del Pago" />
                                            <x-text-input id="fecha_pago" name="fecha_pago" type="date" :value="old('fecha_pago', now()->format('Y-m-d'))" max="{{ now()->format('Y-m-d') }}" required />
                                            <x-input-error :messages="$errors->get('fecha_pago')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                                        <x-primary-button>Registrar Pago</x-primary-button>
                                    </div>
                                </form>
                            </x-modal-bootstrap>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="titulo-seccion mb-0 {{ $recibo->estado === 'anulado' ? '' : 'text-danger' }}">Estado del Recibo</h3>

                    @if ($recibo->estado === 'anulado')
                        <p class="text-secondary mb-0">
                            Este recibo está anulado y no cuenta para los reportes de cobranza. Podés reactivarlo si fue un error.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-danger" disabled aria-pressed="true">Anulado</button>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reactivar-recibo">Reactivar Recibo</button>
                        </div>
                    @else
                        <p class="text-secondary mb-0">
                            Anular este recibo lo marcará como inválido y dejará de contar para los reportes de cobranza. Podrás revertirlo manualmente si fue un error.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#anular-recibo">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Anular Recibo
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            </div>
            </div>

            <x-modal-bootstrap name="anular-recibo" focusable>
                <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="nuevo_estado" value="anulado">
                    <input type="hidden" name="confirmado" value="1">

                    <div class="modal-body p-4">
                        <h2 class="fs-4 fw-bold">¿Anular este recibo?</h2>
                        <p class="mb-0">
                            Un recibo anulado se marcará visiblemente como "ANULADO" en su comprobante y
                            dejará de admitir nuevos pagos. Los pagos ya registrados se conservan.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                        <x-danger-button>Sí, anular recibo</x-danger-button>
                    </div>
                </form>
            </x-modal-bootstrap>

            <x-modal-bootstrap name="reactivar-recibo" focusable>
                <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="nuevo_estado" value="activo">
                    <input type="hidden" name="confirmado" value="1">

                    <div class="modal-body p-4">
                        <h2 class="fs-4 fw-bold">¿Reactivar este recibo?</h2>
                        <p class="mb-0">
                            El recibo volverá a estar vigente. Su estado Pendiente/Pagado se recalculará
                            automáticamente a partir de los pagos que ya tenía registrados.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                        <x-primary-button>Sí, reactivar recibo</x-primary-button>
                    </div>
                </form>
            </x-modal-bootstrap>
        </div>
    </div>
</x-layouts.app-bootstrap>

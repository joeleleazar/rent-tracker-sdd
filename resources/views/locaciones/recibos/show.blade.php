<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Recibo #{{ $recibo->id }} — {{ $recibo->locacion->nombre }}
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
                        <dt class="col-sm-4 fw-semibold">Estado</dt>
                        <dd class="col-sm-8">
                            @php
                                $claseEstado = match ($recibo->estado) {
                                    'pagado' => 'text-bg-success',
                                    'anulado' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $claseEstado }}">
                                {{ ucfirst($recibo->estado) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4 fw-semibold">Locación</dt>
                        <dd class="col-sm-8">{{ $recibo->locacion->nombre }}</dd>

                        <dt class="col-sm-4 fw-semibold">Periodo</dt>
                        <dd class="col-sm-8">{{ $recibo->periodo->translatedFormat('F Y') }}</dd>

                        <dt class="col-sm-4 fw-semibold">Fecha de emisión</dt>
                        <dd class="col-sm-8">{{ $recibo->fecha_emision->format('d/m/Y') }}</dd>

                        @if ($recibo->monto_renta !== null)
                            <dt class="col-sm-4 fw-semibold">Monto de Renta</dt>
                            <dd class="col-sm-8 cifra">S/ {{ number_format((float) $recibo->monto_renta, 2) }}</dd>
                        @endif
                        @foreach ($recibo->conceptos->sortBy('conceptoGastoFijo.orden') as $reciboConcepto)
                            <dt class="col-sm-4 fw-semibold">Monto de {{ $reciboConcepto->conceptoGastoFijo?->nombre ?? 'concepto eliminado' }}</dt>
                            <dd class="col-sm-8 cifra">S/ {{ number_format((float) $reciboConcepto->monto, 2) }}</dd>
                        @endforeach

                        <dt class="col-sm-4 fw-bold">Total</dt>
                        <dd class="col-sm-8 fw-bold cifra">S/ {{ number_format($recibo->total(), 2) }}</dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-3 pt-2">
                        <a href="{{ route('recibos.edit', $recibo) }}" class="btn btn-primary"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar Recibo</a>
                        {{-- hx-boost="false": el comprobante es una página standalone con su
                             propio <head>/CSS (ver comprobante.blade.php), no el layout compartido;
                             debe cargarse con una navegación clásica completa, no un swap parcial. --}}
                        <a href="{{ route('recibos.comprobante', $recibo) }}" class="btn btn-primary" hx-boost="false">Ver Comprobante</a>
                        <a href="{{ route('locaciones.recibos.index', $recibo->locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history" aria-hidden="true"></i> Ver Historial de Recibos</a>
                    </div>
                </div>
            </div>

            @if ($recibo->estado !== 'anulado')
                <div class="card">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h3 class="fs-4 fw-bold mb-0">Pagos</h3>
                            @if ($recibo->estado === 'pagado')
                                <span class="badge bg-success">Pagado en su totalidad</span>
                            @else
                                <span class="badge bg-warning">
                                    S/ {{ number_format($recibo->montoPagado(), 2) }} de S/ {{ number_format($recibo->total(), 2) }} pagado
                                </span>
                            @endif
                        </div>

                        {{--
                            specs/032 (FR-006): el estado Pendiente/Pagado ya no se elige a mano —
                            se recalcula automáticamente a partir de la suma de estos pagos
                            (ServicioGestionPagosRecibo). Solo Anular/Reactivar siguen siendo
                            transiciones manuales, más abajo.
                        --}}
                        @if ($recibo->pagos->isEmpty())
                            <p class="text-secondary mb-0">Todavía no se registró ningún pago para este recibo.</p>
                        @else
                            <ul class="list-group">
                                @foreach ($recibo->pagos->sortByDesc('fecha_pago') as $pago)
                                    <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div>
                                            <span class="fw-semibold cifra">S/ {{ number_format((float) $pago->monto, 2) }}</span>
                                            <span class="text-secondary"> · {{ $pago->fecha_pago->format('d/m/Y') }}</span>
                                            @if ($pago->registradoPor)
                                                <span class="text-secondary"> · Registrado por {{ $pago->registradoPor->name }}</span>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editar-pago-{{ $pago->id }}">
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#eliminar-pago-{{ $pago->id }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Eliminar
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

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
                            <form method="POST" action="{{ route('pagos.store', $recibo) }}" class="d-flex flex-wrap align-items-end gap-3">
                                @csrf
                                <div>
                                    <x-input-label for="monto" value="Monto del Pago (S/)" />
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <x-text-input id="monto" name="monto" type="number" step="0.01" min="0.01" max="{{ $recibo->saldoPendiente() }}" required />
                                    </div>
                                    <x-input-error :messages="$errors->get('monto')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="fecha_pago" value="Fecha del Pago" />
                                    <x-text-input id="fecha_pago" name="fecha_pago" type="date" :value="now()->format('Y-m-d')" max="{{ now()->format('Y-m-d') }}" required />
                                    <x-input-error :messages="$errors->get('fecha_pago')" class="mt-2" />
                                </div>
                                <x-primary-button>Registrar Pago</x-primary-button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold">Estado del Recibo</h3>

                    <div class="d-flex flex-wrap gap-2">
                        @if ($recibo->estado === 'anulado')
                            <button type="button" class="btn btn-danger" disabled aria-pressed="true">Anulado</button>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reactivar-recibo">Reactivar Recibo</button>
                        @else
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#anular-recibo">Anular Recibo</button>
                        @endif
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

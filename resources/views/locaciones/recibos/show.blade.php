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
                        <dt class="col-sm-4 fs-5 fw-semibold">Estado</dt>
                        <dd class="col-sm-8 fs-5">
                            @php
                                $claseEstado = match ($recibo->estado) {
                                    'pagado' => 'text-bg-success',
                                    'anulado' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $claseEstado }} fs-6">
                                {{ ucfirst($recibo->estado) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4 fs-5 fw-semibold">Locación</dt>
                        <dd class="col-sm-8 fs-5">{{ $recibo->locacion->nombre }}</dd>

                        <dt class="col-sm-4 fs-5 fw-semibold">Periodo</dt>
                        <dd class="col-sm-8 fs-5">{{ $recibo->periodo->translatedFormat('F Y') }}</dd>

                        <dt class="col-sm-4 fs-5 fw-semibold">Fecha de emisión</dt>
                        <dd class="col-sm-8 fs-5">{{ $recibo->fecha_emision->format('d/m/Y') }}</dd>

                        @if ($recibo->incluye_alquiler)
                            <dt class="col-sm-4 fs-5 fw-semibold">Monto de Renta</dt>
                            <dd class="col-sm-8 fs-5">S/ {{ number_format((float) $recibo->monto_renta, 2) }}</dd>
                        @endif
                        @if ($recibo->incluye_agua)
                            <dt class="col-sm-4 fs-5 fw-semibold">Monto de Agua</dt>
                            <dd class="col-sm-8 fs-5">S/ {{ number_format((float) $recibo->monto_agua, 2) }}</dd>
                        @endif
                        @if ($recibo->incluye_luz)
                            <dt class="col-sm-4 fs-5 fw-semibold">Monto de Luz</dt>
                            <dd class="col-sm-8 fs-5">S/ {{ number_format((float) $recibo->monto_luz, 2) }}</dd>
                        @endif
                        @if ($recibo->incluye_pasadizo)
                            <dt class="col-sm-4 fs-5 fw-semibold">Monto de Luz de Pasadizo</dt>
                            <dd class="col-sm-8 fs-5">S/ {{ number_format((float) $recibo->monto_pasadizo, 2) }}</dd>
                        @endif
                        @if ($recibo->incluye_seguridad)
                            <dt class="col-sm-4 fs-5 fw-semibold">Monto de Seguridad</dt>
                            <dd class="col-sm-8 fs-5">S/ {{ number_format((float) $recibo->monto_seguridad, 2) }}</dd>
                        @endif

                        <dt class="col-sm-4 fs-5 fw-bold">Total</dt>
                        <dd class="col-sm-8 fs-5 fw-bold">S/ {{ number_format($recibo->total(), 2) }}</dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-3 pt-2">
                        <a href="{{ route('recibos.edit', $recibo) }}" class="btn btn-primary btn-lg">Editar Recibo</a>
                        <a href="{{ route('recibos.comprobante', $recibo) }}" class="btn btn-primary btn-lg">Ver Comprobante</a>
                        <a href="{{ route('locaciones.recibos.index', $recibo->locacion) }}" class="btn btn-outline-secondary btn-lg">Ver Historial de Recibos</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold">Estado de Pago</h3>

                    {{--
                        Nota de migración: el inventario sugiere `btn-group`/`btn-check`
                        para este selector, pero cada acción es en realidad un
                        formulario POST independiente con su propio conjunto de campos
                        ocultos (no un único grupo de radios), así que se preserva esa
                        estructura (sin cambios de comportamiento) y `btn-group` se usa
                        solo como agrupador visual de los botones de acción.
                    --}}
                    <div class="btn-group flex-wrap gap-3" role="group" aria-label="Acciones de estado del recibo">
                        @if ($recibo->estado === 'pendiente')
                            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="nuevo_estado" value="pagado">
                                <input type="hidden" name="confirmado" value="1">
                                <x-primary-button>Marcar como Pagado</x-primary-button>
                            </form>

                            <x-danger-button
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#anular-recibo"
                            >Anular Recibo</x-danger-button>
                        @elseif ($recibo->estado === 'pagado')
                            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="nuevo_estado" value="pendiente">
                                <input type="hidden" name="confirmado" value="1">
                                <x-secondary-button>Marcar como Pendiente</x-secondary-button>
                            </form>

                            <x-danger-button
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#anular-recibo"
                            >Anular Recibo</x-danger-button>
                        @else
                            <x-primary-button
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#revertir-pendiente"
                            >Revertir Anulación a Pendiente</x-primary-button>

                            <x-primary-button
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#revertir-pagado"
                            >Revertir Anulación a Pagado</x-primary-button>
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
                        <p class="fs-5 mb-0">
                            Un recibo anulado se marcará visiblemente como "ANULADO" en su comprobante.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                        <x-danger-button>Sí, anular recibo</x-danger-button>
                    </div>
                </form>
            </x-modal-bootstrap>

            <x-modal-bootstrap name="revertir-pendiente" focusable>
                <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="nuevo_estado" value="pendiente">
                    <input type="hidden" name="confirmado" value="1">

                    <div class="modal-body p-4">
                        <h2 class="fs-4 fw-bold">¿Revertir la anulación de este recibo a Pendiente?</h2>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                        <x-primary-button>Sí, revertir a pendiente</x-primary-button>
                    </div>
                </form>
            </x-modal-bootstrap>

            <x-modal-bootstrap name="revertir-pagado" focusable>
                <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="nuevo_estado" value="pagado">
                    <input type="hidden" name="confirmado" value="1">

                    <div class="modal-body p-4">
                        <h2 class="fs-4 fw-bold">¿Revertir la anulación de este recibo a Pagado?</h2>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal">No, cancelar</x-secondary-button>
                        <x-primary-button>Sí, revertir a pagado</x-primary-button>
                    </div>
                </form>
            </x-modal-bootstrap>
        </div>
    </div>
</x-layouts.app-bootstrap>

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

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <h3 class="fs-4 fw-bold">Estado de Pago</h3>

                    {{--
                        Selector unificado con las 3 opciones simultáneamente visibles
                        (specs/012, FR-007): la opción vigente queda resaltada y
                        deshabilitada; seleccionar otra dispara la misma transición ya
                        validada por ReciboController@actualizarEstado, preservando
                        exactamente qué transiciones exigen confirmación explícita (a
                        "Anulado" siempre, y cualquier reversión desde "Anulado") y
                        cuáles no (Pendiente ⇄ Pagado directo), sin cambios de negocio.
                    --}}
                    <div class="btn-group flex-wrap" role="group" aria-label="Estado del recibo">
                        @if ($recibo->estado === 'pendiente')
                            <button type="button" class="btn btn-warning" disabled aria-pressed="true">Pendiente</button>

                            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}" class="d-inline">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="nuevo_estado" value="pagado">
                                <input type="hidden" name="confirmado" value="1">
                                <button type="submit" class="btn btn-outline-success">Pagado</button>
                            </form>

                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#anular-recibo">Anulado</button>
                        @elseif ($recibo->estado === 'pagado')
                            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}" class="d-inline">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="nuevo_estado" value="pendiente">
                                <input type="hidden" name="confirmado" value="1">
                                <button type="submit" class="btn btn-outline-warning">Pendiente</button>
                            </form>

                            <button type="button" class="btn btn-success" disabled aria-pressed="true">Pagado</button>

                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#anular-recibo">Anulado</button>
                        @else
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#revertir-pendiente">Pendiente</button>

                            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#revertir-pagado">Pagado</button>

                            <button type="button" class="btn btn-danger" disabled aria-pressed="true">Anulado</button>
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

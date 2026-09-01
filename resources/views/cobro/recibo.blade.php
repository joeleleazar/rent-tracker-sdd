<x-layouts.app-bootstrap>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h2 class="fs-2 fw-bold mb-0">Cobro del recibo #{{ $recibo->id }}</h2>
            <a href="{{ route('cobro.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Escanear otro
            </a>
        </div>
    </x-slot>

    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <div class="card">
                <div class="card-body">
                    <h3 class="fs-5 fw-bold mb-3">{{ $recibo->locacion->nombre }}</h3>
                    <dl class="row mb-0">
                        <dt class="col-5 col-sm-4 text-secondary fw-normal">Periodo</dt>
                        <dd class="col-7 col-sm-8">{{ $recibo->periodo->format('m/Y') }}</dd>

                        <dt class="col-5 col-sm-4 text-secondary fw-normal">Total del recibo</dt>
                        <dd class="col-7 col-sm-8 cifra">S/ {{ number_format($recibo->total(), 2) }}</dd>

                        <dt class="col-5 col-sm-4 text-secondary fw-normal">Pagado</dt>
                        <dd class="col-7 col-sm-8 cifra">S/ {{ number_format($recibo->montoPagado(), 2) }}</dd>

                        <dt class="col-5 col-sm-4 text-secondary fw-normal">Saldo pendiente</dt>
                        <dd class="col-7 col-sm-8 cifra fw-bold">S/ {{ number_format($recibo->saldoPendiente(), 2) }}</dd>
                    </dl>
                </div>
            </div>

            @if ($bloqueo !== null)
                <x-mensaje-alerta tipo="error">{{ $bloqueo }}</x-mensaje-alerta>
            @else
                <div class="card">
                    <div class="card-body">
                        <h3 class="fs-5 fw-bold mb-3">Registrar pago</h3>

                        <form method="POST" action="{{ route('cobro.pago.store', $recibo) }}" enctype="multipart/form-data" class="d-flex flex-column gap-3">
                            @csrf

                            <div>
                                <x-input-label for="monto" value="Monto" />
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input
                                        id="monto"
                                        name="monto"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="form-control @error('monto') is-invalid @enderror"
                                        value="{{ old('monto', number_format($recibo->saldoPendiente(), 2, '.', '')) }}"
                                        required
                                    >
                                </div>
                                @error('monto')
                                    <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="fecha_pago" value="Fecha del pago" />
                                <input
                                    id="fecha_pago"
                                    name="fecha_pago"
                                    type="date"
                                    class="form-control @error('fecha_pago') is-invalid @enderror"
                                    value="{{ old('fecha_pago', now()->format('Y-m-d')) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    required
                                >
                                @error('fecha_pago')
                                    <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-input-label for="medio_pago" value="Medio de pago (opcional)" />
                                <select id="medio_pago" name="medio_pago" class="form-select">
                                    <option value="">— Sin especificar —</option>
                                    @foreach (['Efectivo', 'Transferencia', 'Depósito', 'Yape / Plin', 'Otro'] as $medio)
                                        <option value="{{ $medio }}" @selected(old('medio_pago') === $medio)>{{ $medio }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="evidencia" value="Evidencia (opcional)" />
                                <input
                                    id="evidencia"
                                    name="evidencia"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    class="form-control @error('evidencia') is-invalid @enderror"
                                >
                                @error('evidencia')
                                    <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
                                @enderror
                            </div>

                            <x-primary-button class="align-self-start">
                                <i class="bi bi-cash-coin" aria-hidden="true"></i> Registrar pago
                            </x-primary-button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>

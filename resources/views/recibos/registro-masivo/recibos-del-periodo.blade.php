<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Recibos de {{ $locacion->nombre }} — {{ $periodo->translatedFormat('F Y') }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-9" style="max-width: 48rem;">
        <div class="d-flex flex-column gap-3">
            <p class="text-secondary mb-0">
                Esta locación tiene más de un recibo para este periodo. Elija cuál ver.
            </p>

            <div class="d-flex flex-column gap-3">
                @foreach ($recibos as $recibo)
                    <a href="{{ route('recibos.show', $recibo) }}" class="card text-decoration-none text-body">
                        <div class="card-body d-flex flex-column gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if ($recibo->estado === 'pendiente')
                                    <span class="badge bg-warning">Pendiente</span>
                                @elseif ($recibo->estado === 'pagado')
                                    <span class="badge bg-success">Pagado</span>
                                @else
                                    <span class="badge bg-danger">Anulado</span>
                                @endif
                                <span class="fw-semibold">
                                    {{ $recibo->monto_renta !== null ? 'Renta' : '' }}{{ $recibo->monto_renta !== null && $recibo->conceptos->isNotEmpty() ? ' + ' : '' }}{{ $recibo->conceptos->map(fn ($rc) => $rc->conceptoGastoFijo?->nombre ?? 'Concepto eliminado')->implode(', ') }}
                                </span>
                            </div>
                            <p class="mb-0">Total: S/ {{ number_format($recibo->total(), 2) }}</p>
                            <p class="mb-0 text-secondary">Emitido el {{ $recibo->fecha_emision->format('d/m/Y') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <a href="{{ route('recibos.registroMasivo.index', ['periodo' => $periodo->format('Y-m')]) }}" class="btn btn-outline-secondary align-self-start">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al registro masivo
            </a>
        </div>
    </div>
</x-layouts.app-bootstrap>

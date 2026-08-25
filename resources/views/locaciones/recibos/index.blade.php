<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Recibos — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-9" style="max-width: 48rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('locaciones.recibos.create', $locacion) }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Emitir Recibo</a>
            </div>

            @if ($recibos->isEmpty())
                <x-estado-vacio icono="bi-receipt">Esta locación todavía no tiene recibos emitidos.</x-estado-vacio>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($recibos as $recibo)
                        <a href="{{ route('recibos.show', $recibo) }}" class="card text-decoration-none text-body">
                            <div class="card-body">
                                <p class="fw-semibold mb-1">
                                    Periodo: {{ $recibo->periodo->translatedFormat('F Y') }}
                                </p>
                                <p class="mb-1">
                                    Total cobrado: S/ {{ number_format($recibo->total(), 2) }}
                                </p>
                                <p class="mb-0">
                                    Emitido el {{ $recibo->fecha_emision->format('d/m/Y') }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>

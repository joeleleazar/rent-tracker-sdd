<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante de Pago #{{ $pago->id }} — Recibo #{{ $recibo->id }}</title>
    {{ \Illuminate\Support\Facades\Vite::fonts() }}
    {{--
        specs/035 (research.md Decisión 1): a diferencia de locaciones/recibos/comprobante.blade.php
        (specs/007/031), esta vista NO necesita capturarse con html2canvas (no se comparte por
        WhatsApp), así que no hereda la restricción de evitar Bootstrap/Tailwind por el conflicto
        con oklch() — carga la hoja de estilos real de Bootstrap 5 y cumple el Principio VI sin
        ninguna excepción.
    --}}
    @vite(['resources/css/bootstrap.scss'])
</head>
<body>
    <div class="container py-4">
        <div class="mx-auto d-flex flex-column gap-3" style="max-width: 42rem;">
            <div class="d-flex flex-wrap gap-3 d-print-none">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer" aria-hidden="true"></i> Imprimir Comprobante
                </button>
                <a href="{{ route('recibos.show', $recibo) }}" class="btn btn-outline-secondary">
                    Volver al Recibo
                </a>
            </div>

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    {{-- Encabezado --}}
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" style="height: 2.5rem; width: auto;">
                        <h1 class="fs-3 fw-bold mb-0">Comprobante de Pago</h1>
                    </div>

                    <hr class="my-0">

                    {{-- Metadatos --}}
                    <dl class="row mb-0">
                        <dt class="col-sm-5 fw-semibold">N.° de recibo</dt>
                        <dd class="col-sm-7 cifra">{{ $recibo->id }}</dd>

                        <dt class="col-sm-5 fw-semibold">N.° de pago</dt>
                        <dd class="col-sm-7 cifra">{{ $pago->id }}</dd>

                        <dt class="col-sm-5 fw-semibold">Fecha del pago</dt>
                        <dd class="col-sm-7">{{ $pago->fecha_pago->format('d/m/Y') }}</dd>
                    </dl>

                    <hr class="my-0">

                    {{-- Partes --}}
                    <dl class="row mb-0">
                        <dt class="col-sm-5 fw-semibold">Recibí de</dt>
                        <dd class="col-sm-7">{{ $recibo->contrato->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}</dd>

                        <dt class="col-sm-5 fw-semibold">Locación</dt>
                        <dd class="col-sm-7">{{ $recibo->locacion->nombre }}</dd>

                        @if (filled($nombrePropietario))
                            <dt class="col-sm-5 fw-semibold">Recibido por</dt>
                            <dd class="col-sm-7">{{ $nombrePropietario }}</dd>
                        @endif
                    </dl>

                    <hr class="my-0">

                    {{-- Monto de este pago — el elemento más destacado del documento (spec.md FR-002) --}}
                    <div class="bg-primary text-white rounded p-3 text-center">
                        <p class="mb-1 small">Monto de este pago</p>
                        <p class="mb-0 fs-1 fw-bold cifra">S/ {{ number_format((float) $pago->monto, 2) }}</p>
                    </div>

                    {{-- Avance del recibo --}}
                    <dl class="row mb-0">
                        <dt class="col-sm-5 fw-semibold">Total del recibo</dt>
                        <dd class="col-sm-7 cifra">S/ {{ number_format($recibo->total(), 2) }}</dd>

                        <dt class="col-sm-5 fw-semibold">Pagado hasta ahora</dt>
                        <dd class="col-sm-7 cifra">S/ {{ number_format($pago->montoAcumuladoHastaEstePago(), 2) }}</dd>

                        <dt class="col-sm-5 fw-bold">Saldo pendiente</dt>
                        <dd class="col-sm-7 fw-bold cifra">S/ {{ number_format($pago->saldoPendienteHastaEstePago(), 2) }}</dd>
                    </dl>

                    <hr class="my-0">

                    {{-- Firma — área en blanco de altura fija para dejar espacio real para firmar a
                         mano, no solo un margen (specs/039) --}}
                    <div class="text-center mt-3">
                        <div style="height: 5rem;"></div>
                        <div class="border-top border-dark mx-auto mb-1" style="width: 65%;"></div>
                        <p class="mb-0 small text-secondary">Firma de quien recibe el pago</p>
                    </div>

                    <p class="text-center fst-italic text-secondary mb-0">
                        Gracias por su pago puntual.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

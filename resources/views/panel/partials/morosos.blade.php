{{-- specs/043: bloque de inquilinos morosos (US1). Recibos no anulados con
     saldo pendiente cuya fecha límite de pago ya venció. --}}
@php
    $badgeTramo = fn (string $t) => match ($t) {
        '1-30' => 'text-bg-warning',
        '31-60' => 'text-bg-warning',
        default => 'text-bg-danger',
    };
    $etiquetaTramo = ['1-30' => '1 a 30 días', '31-60' => '31 a 60 días', '61-90' => '61 a 90 días', '90+' => 'más de 90 días'];
@endphp

<section aria-labelledby="titulo-morosos" id="bloque-morosos">
    <h3 id="titulo-morosos" class="titulo-seccion mb-3">Inquilinos morosos</h3>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Recibos morosos</div>
                <div class="fs-3 fw-bold cifra">{{ $resumenMorosidad['cantidadRecibos'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Inquilinos con deuda vencida</div>
                <div class="fs-3 fw-bold cifra">{{ $resumenMorosidad['cantidadInquilinos'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Monto total adeudado vencido</div>
                <div class="fs-3 fw-bold cifra text-danger">S/ {{ number_format($resumenMorosidad['montoAdeudadoVencido'] ?? 0, 2) }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach (['1-30' => '1 a 30 días', '31-60' => '31 a 60 días', '61-90' => '61 a 90 días', '90+' => 'más de 90 días'] as $clave => $rotulo)
            <div class="col-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-secondary small">{{ $rotulo }}</div>
                    <div class="fw-bold cifra">S/ {{ number_format($resumenMorosidad['porTramo'][$clave]['monto'] ?? 0, 2) }}</div>
                    <div class="text-secondary small cifra">{{ $resumenMorosidad['porTramo'][$clave]['cantidad'] ?? 0 }} recibo(s)</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-end gap-3">
            <div>
                <x-input-label for="filtro_tramo" value="Tramo de antigüedad" />
                <select id="filtro_tramo" name="tramo" class="form-select"
                        hx-get="{{ route('dashboard') }}" hx-trigger="change"
                        hx-select="#bloque-morosos" hx-target="#bloque-morosos" hx-swap="outerHTML">
                    <option value="">Todos</option>
                    @foreach (['1-30' => '1 a 30 días', '31-60' => '31 a 60 días', '61-90' => '61 a 90 días', '90+' => 'más de 90 días'] as $clave => $rotulo)
                        <option value="{{ $clave }}" @selected(($filtros['tramo'] ?? null) === $clave)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="filtro_locacion" value="Rama de locación" />
                <select id="filtro_locacion" name="locacion" class="form-select"
                        hx-get="{{ route('dashboard') }}" hx-trigger="change"
                        hx-select="#bloque-morosos" hx-target="#bloque-morosos" hx-swap="outerHTML">
                    <option value="">Todas</option>
                    @foreach ($filtros['locacionesDisponibles'] as $loc)
                        <option value="{{ $loc->id }}" @selected(($filtros['locacion'] ?? null) === $loc->id)>{{ $loc->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-funnel" aria-hidden="true"></i> Filtrar
            </button>
        </div>
    </form>

    @if ($morosos->isEmpty())
        <x-estado-vacio icono="bi-check-circle">
            @if ($filtros['hayFiltro'] ?? false)
                Ningún recibo moroso coincide con el filtro aplicado.
            @else
                No hay recibos vencidos impagos.
            @endif
        </x-estado-vacio>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Inquilino</th>
                            <th scope="col">Local</th>
                            <th scope="col">Periodo</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col" class="text-end">Pagado</th>
                            <th scope="col" class="text-end">Saldo pendiente</th>
                            <th scope="col">Fecha límite</th>
                            <th scope="col" class="text-end">Días de atraso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($morosos as $fila)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('recibos.show', $fila->recibo) }}">{{ $fila->inquilino ?? '—' }}</a>
                                </td>
                                <td><x-ruta-jerarquia-locacion :ruta="$fila->locacion->rutaJerarquiaTruncada()" /></td>
                                <td>{{ ucfirst($fila->periodo->translatedFormat('F Y')) }}</td>
                                <td class="text-end cifra">S/ {{ number_format($fila->montoTotal, 2) }}</td>
                                <td class="text-end cifra">S/ {{ number_format($fila->montoPagado, 2) }}</td>
                                <td class="text-end cifra fw-semibold">S/ {{ number_format($fila->saldoPendiente, 2) }}</td>
                                <td>{{ $fila->fechaLimite->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <span class="badge {{ $badgeTramo($fila->tramoAntiguedad) }}">
                                        {{ $fila->diasDeAtraso }} d · {{ $etiquetaTramo[$fila->tramoAntiguedad] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

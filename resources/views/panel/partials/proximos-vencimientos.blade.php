{{-- specs/043: bloque de próximos vencimientos de pago (US2). Recibos no
     anulados con saldo pendiente cuya fecha límite aún no venció. --}}
<section aria-labelledby="titulo-proximos">
    <h3 id="titulo-proximos" class="titulo-seccion mb-3">Próximos vencimientos de pago</h3>

    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-4">
        <div>
            <div class="text-secondary small">Recibos en plazo</div>
            <div class="fs-4 fw-bold cifra">{{ $resumenProximos['cantidad'] ?? 0 }}</div>
        </div>
        <div>
            <div class="text-secondary small">Saldo pendiente aún en plazo</div>
            <div class="fs-4 fw-bold cifra">S/ {{ number_format($resumenProximos['montoTotal'] ?? 0, 2) }}</div>
        </div>
    </div></div>

    @if ($proximos->isEmpty())
        <x-estado-vacio icono="bi-calendar-check">No hay pagos próximos a vencer.</x-estado-vacio>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Inquilino</th>
                            <th scope="col">Local</th>
                            <th scope="col">Periodo</th>
                            <th scope="col" class="text-end">Saldo pendiente</th>
                            <th scope="col">Fecha límite</th>
                            <th scope="col" class="text-end">Días restantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proximos as $fila)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('recibos.show', $fila->recibo) }}">{{ $fila->inquilino ?? '—' }}</a>
                                </td>
                                <td><x-ruta-jerarquia-locacion :ruta="$fila->locacion->rutaJerarquiaTruncada()" /></td>
                                <td>{{ ucfirst($fila->periodo->translatedFormat('F Y')) }}</td>
                                <td class="text-end cifra fw-semibold">S/ {{ number_format($fila->saldoPendiente, 2) }}</td>
                                <td>{{ $fila->fechaLimite->format('d/m/Y') }}</td>
                                <td class="text-end cifra">{{ $fila->diasRestantes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

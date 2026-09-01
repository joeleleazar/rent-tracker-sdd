{{-- specs/043: indicadores generales de cobranza (US3) — mes calendario en
     curso — más los contratos por vencer en 7 / 15 / 30 días (grupos
     acumulativos). --}}
<section aria-labelledby="titulo-indicadores">
    <h3 id="titulo-indicadores" class="titulo-seccion mb-3">Indicadores de cobranza del mes</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Facturado del periodo</div>
                <div class="fs-5 fw-bold cifra">S/ {{ number_format($indicadores['facturadoDelPeriodo'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Cobrado de recibos del periodo</div>
                <div class="fs-5 fw-bold cifra">S/ {{ number_format($indicadores['cobradoDeRecibosDelPeriodo'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Recaudado este mes</div>
                <div class="fs-5 fw-bold cifra">S/ {{ number_format($indicadores['recaudadoEsteMes'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Tasa de cobranza</div>
                <div class="fs-5 fw-bold cifra">
                    @if (($indicadores['tasaDeCobranza'] ?? null) === null)
                        <span class="text-secondary">— sin datos</span>
                    @else
                        {{ number_format($indicadores['tasaDeCobranza'], 1) }} %
                    @endif
                </div>
            </div></div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Cartera total por cobrar</div>
                <div class="fs-5 fw-bold cifra text-danger">S/ {{ number_format($indicadores['carteraTotalPorCobrar'] ?? 0, 2) }}</div>
            </div></div>
        </div>
    </div>

    <h3 class="titulo-seccion mb-3">Contratos por vencer</h3>
    <div class="row g-3">
        @foreach (['dentro7' => 'Vencen en 7 días', 'dentro15' => 'Vencen en 15 días', 'dentro30' => 'Vencen en 30 días'] as $clave => $rotulo)
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">{{ $rotulo }}
                            <span class="badge text-bg-secondary cifra">{{ $contratosPorVencer[$clave]->count() }}</span>
                        </div>
                        @forelse ($contratosPorVencer[$clave] as $item)
                            <div class="d-flex flex-wrap justify-content-between align-items-baseline column-gap-3 border-top py-2">
                                <a href="{{ route('contratos.show', $item->contrato) }}">
                                    {{ $item->inquilino ?? '—' }} · {{ $item->locacion?->nombre ?? '—' }}
                                </a>
                                <span class="text-secondary small text-nowrap">
                                    {{ $item->fechaFin->format('d/m/Y') }} ({{ $item->diasRestantes }} d)
                                </span>
                            </div>
                        @empty
                            <div class="text-secondary small">Ninguno.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

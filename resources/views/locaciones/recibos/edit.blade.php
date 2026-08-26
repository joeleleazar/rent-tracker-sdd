<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Editar Recibo #{{ $recibo->id }} — {{ $recibo->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        <div class="d-flex flex-column gap-3">
            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            <p>
                Periodo: <strong>{{ $recibo->periodo->translatedFormat('F Y') }}</strong>
            </p>

            <form method="POST" action="{{ route('recibos.update', $recibo) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="periodo" value="{{ $recibo->periodo->format('Y-m-d') }}">

                    @foreach ($conceptosDisponibles as $concepto)
                        @php
                            $yaIncluido = $concepto->esRenta()
                                ? $recibo->monto_renta !== null
                                : $recibo->conceptos->contains('concepto_gasto_fijo_id', $concepto->id);
                            $montoActual = $concepto->esRenta()
                                ? $recibo->monto_renta
                                : $recibo->conceptos->firstWhere('concepto_gasto_fijo_id', $concepto->id)?->monto;
                            $nombreCheckbox = $concepto->esRenta() ? 'incluye_alquiler' : "conceptos[{$concepto->id}][incluido]";
                            $nombreMonto = $concepto->esRenta() ? 'monto_renta' : "conceptos[{$concepto->id}][monto]";
                            $idCampo = $concepto->esRenta() ? 'monto_renta' : "concepto_{$concepto->id}";
                        @endphp
                        <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                            <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                <input type="checkbox" id="incluir_{{ $idCampo }}" name="{{ $nombreCheckbox }}" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old(str_replace(['[', ']'], ['.', ''], $nombreCheckbox), $yaIncluido))>
                                <label for="incluir_{{ $idCampo }}" class="form-check-label fw-semibold">
                                    Incluir {{ $concepto->nombre }}
                                </label>
                            </div>
                            <div class="input-group" style="max-width: 16rem;">
                                <span class="input-group-text">S/</span>
                                <x-text-input :id="$idCampo" :name="$nombreMonto" type="number" step="0.01" min="0" :value="old(str_replace(['[', ']'], ['.', ''], $nombreMonto), $montoActual)" />
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <x-input-label for="fecha_emision" value="Fecha de Emisión" />
                        <x-text-input id="fecha_emision" name="fecha_emision" type="date" :value="old('fecha_emision', $recibo->fecha_emision->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <x-primary-button>Guardar Cambios del Recibo</x-primary-button>
                        <a href="{{ route('recibos.show', $recibo) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app-bootstrap>

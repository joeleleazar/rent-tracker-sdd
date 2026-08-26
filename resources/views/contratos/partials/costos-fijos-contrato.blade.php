{{--
    specs/024: valores de referencia por concepto de gasto fijo dinámico (reemplaza los 4
    campos fijos costo_agua/costo_luz/costo_pasadizo/costo_seguridad). Incluido dentro del
    <form> de contratos/create.blade.php y contratos/edit.blade.php — este archivo solo
    renderiza los campos, no su propio <form>.

    Props esperadas:
    - $contrato (?App\Models\Contrato) — null en creación, el contrato existente en edición.
    - $conceptosConfigurables (Illuminate\Support\Collection<int, App\Models\ConceptoGastoFijo>)
      conceptos activos y no protegidos (Renta/Luz nunca aparecen aquí).
--}}
<div class="costos-fijos-grid d-flex flex-column gap-3">
    <h3 class="fs-5 fw-bold mb-0">Costos Fijos de Referencia</h3>
    <p class="text-secondary mb-0">
        Estos valores se usan como referencia inicial editable al generar un recibo; no afectan a recibos ya emitidos.
    </p>

    <div class="row g-4">
        @foreach ($conceptosConfigurables as $concepto)
            @php
                $valorActual = $contrato?->valorDeConcepto($concepto);
            @endphp
            <div class="col-md-6">
                <x-input-label for="valor_concepto_{{ $concepto->id }}" value="Costo de {{ $concepto->nombre }}" />
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <x-text-input
                        id="valor_concepto_{{ $concepto->id }}"
                        name="valores[{{ $concepto->id }}]"
                        type="number"
                        step="0.01"
                        min="0"
                        class="costo-fijo-campo"
                        :value="old('valores.' . $concepto->id, $valorActual)"
                    />
                </div>
                <x-input-error :messages="$errors->get('valores.' . $concepto->id)" class="mt-2" />
            </div>
        @endforeach

        <div class="col-md-6">
            <x-input-label for="costo_total_referencia" value="Total de Referencia" />
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input id="costo_total_referencia" type="text" class="form-control costo-fijo-total" readonly value="0.00">
            </div>
            <small class="text-secondary d-block mt-2">Suma de los costos de arriba</small>
        </div>
    </div>
</div>

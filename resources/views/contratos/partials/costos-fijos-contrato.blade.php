@php
    $contrato = $contrato ?? null;
@endphp

<div class="card">
    <div class="card-body d-flex flex-column gap-3">
        <h3 class="fs-4 fw-bold">Costos Fijos de Referencia</h3>
        <p class="mb-0">
            Déjelo en blanco si no aplica; se registrará como S/ 0.00.
        </p>

        <div class="row g-4 costos-fijos-grid">
            <div class="col-md-6">
                <x-input-label for="costo_agua" value="Costo de Agua" />
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <x-text-input id="costo_agua" name="costo_agua" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_agua', $contrato?->costo_agua)" />
                </div>
                <x-input-error :messages="$errors->get('costo_agua')" class="mt-2" />
            </div>

            <div class="col-md-6">
                <x-input-label for="costo_luz" value="Costo de Luz" />
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <x-text-input id="costo_luz" name="costo_luz" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_luz', $contrato?->costo_luz)" />
                </div>
                <x-input-error :messages="$errors->get('costo_luz')" class="mt-2" />
            </div>

            <div class="col-md-6">
                <x-input-label for="costo_pasadizo" value="Costo de Pasadizo" />
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <x-text-input id="costo_pasadizo" name="costo_pasadizo" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_pasadizo', $contrato?->costo_pasadizo)" />
                </div>
                <x-input-error :messages="$errors->get('costo_pasadizo')" class="mt-2" />
            </div>

            <div class="col-md-6">
                <x-input-label for="costo_seguridad" value="Costo de Seguridad" />
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <x-text-input id="costo_seguridad" name="costo_seguridad" type="number" step="0.01" min="0" class="costo-fijo-campo" :value="old('costo_seguridad', $contrato?->costo_seguridad)" />
                </div>
                <x-input-error :messages="$errors->get('costo_seguridad')" class="mt-2" />
            </div>

            <div class="col-md-6">
                <x-input-label for="costo_total_referencia" value="Total de Referencia" />
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <input id="costo_total_referencia" type="text" class="form-control costo-fijo-total" readonly value="0.00">
                </div>
                <small class="text-secondary d-block mt-2">Suma de los 4 costos de arriba</small>
            </div>
        </div>
    </div>
</div>

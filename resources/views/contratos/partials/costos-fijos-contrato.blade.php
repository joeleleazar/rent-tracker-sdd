@php
    $contrato = $contrato ?? null;
@endphp

<div class="space-y-4 rounded-md border-2 border-gray-300 p-4">
    <h3 class="text-xl font-bold text-gray-900">Costos Fijos de Referencia</h3>
    <p class="text-lg text-gray-700">
        Déjelo en blanco si no aplica; se registrará como S/ 0.00.
    </p>

    <div>
        <x-input-label for="costo_agua" value="Costo de Agua" />
        <x-text-input id="costo_agua" name="costo_agua" type="number" step="0.01" min="0" :value="old('costo_agua', $contrato?->costo_agua)" />
        <x-input-error :messages="$errors->get('costo_agua')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="costo_luz" value="Costo de Luz" />
        <x-text-input id="costo_luz" name="costo_luz" type="number" step="0.01" min="0" :value="old('costo_luz', $contrato?->costo_luz)" />
        <x-input-error :messages="$errors->get('costo_luz')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="costo_pasadizo" value="Costo de Pasadizo" />
        <x-text-input id="costo_pasadizo" name="costo_pasadizo" type="number" step="0.01" min="0" :value="old('costo_pasadizo', $contrato?->costo_pasadizo)" />
        <x-input-error :messages="$errors->get('costo_pasadizo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="costo_seguridad" value="Costo de Seguridad" />
        <x-text-input id="costo_seguridad" name="costo_seguridad" type="number" step="0.01" min="0" :value="old('costo_seguridad', $contrato?->costo_seguridad)" />
        <x-input-error :messages="$errors->get('costo_seguridad')" class="mt-2" />
    </div>
</div>

@php
    $contrato = $contrato ?? null;
@endphp

<div class="space-y-4 rounded-md border-2 border-gray-300 p-4">
    <h3 class="text-xl font-bold text-gray-900">Garantía Entregada</h3>
    <p class="text-lg text-gray-700">
        Opcional. Déjelo en blanco si el contrato no tiene garantía.
    </p>

    <div>
        <x-input-label for="monto_garantia" value="Monto de Garantía Entregada" />
        <x-text-input id="monto_garantia" name="monto_garantia" type="number" step="0.01" min="0" :value="old('monto_garantia', $contrato?->monto_garantia)" />
        <x-input-error :messages="$errors->get('monto_garantia')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="fecha_entrega_garantia" value="Fecha de Entrega de Garantía" />
        <x-text-input id="fecha_entrega_garantia" name="fecha_entrega_garantia" type="date" :value="old('fecha_entrega_garantia', $contrato?->fecha_entrega_garantia?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('fecha_entrega_garantia')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="medio_entrega_garantia" value="Medio de Entrega de Garantía" />
        <select id="medio_entrega_garantia" name="medio_entrega_garantia" class="campo-senior">
            <option value="">Seleccione un medio (opcional)</option>
            @foreach (['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque'] as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('medio_entrega_garantia', $contrato?->medio_entrega_garantia) === $valor)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('medio_entrega_garantia')" class="mt-2" />
    </div>
</div>

<?php

use App\Models\ConceptoGastoFijo;

test('el sembrado inicial tiene exactamente los 5 conceptos esperados', function () {
    expect(ConceptoGastoFijo::count())->toBe(5);

    $renta = ConceptoGastoFijo::where('clave', 'renta')->first();
    $luz = ConceptoGastoFijo::where('clave', 'luz')->first();

    expect($renta)->not->toBeNull();
    expect($renta->nombre)->toBe('Renta');
    expect($renta->orden)->toBe(1);
    expect($renta->esProtegido())->toBeTrue();
    expect($renta->esRenta())->toBeTrue();

    expect($luz)->not->toBeNull();
    expect($luz->nombre)->toBe('Luz');
    expect($luz->esProtegido())->toBeTrue();
    expect($luz->esLuz())->toBeTrue();

    expect(ConceptoGastoFijo::whereNull('clave')->pluck('nombre')->sort()->values()->all())
        ->toBe(['Agua', 'Luz de Pasadizo', 'Seguridad']);
});

test('activos excluye los conceptos desactivados', function () {
    ConceptoGastoFijo::where('nombre', 'Seguridad')->update(['activo' => false]);

    expect(ConceptoGastoFijo::activos()->count())->toBe(4);
    expect(ConceptoGastoFijo::activos()->pluck('nombre'))->not->toContain('Seguridad');
});

test('ordenados devuelve los conceptos en el orden configurado', function () {
    expect(ConceptoGastoFijo::ordenados()->pluck('nombre')->all())
        ->toBe(['Renta', 'Agua', 'Luz', 'Luz de Pasadizo', 'Seguridad']);
});

test('un concepto regular no esta protegido', function () {
    $concepto = ConceptoGastoFijo::factory()->create(['nombre' => 'Internet']);

    expect($concepto->esProtegido())->toBeFalse();
    expect($concepto->esRenta())->toBeFalse();
    expect($concepto->esLuz())->toBeFalse();
});

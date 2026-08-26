<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\ValorConceptoContrato;

test('un recibo pertenece a un contrato y a una locacion', function () {
    $locacion = Locacion::factory()->create();
    $contrato = Contrato::factory()->create(['locacion_id' => $locacion->id]);

    $recibo = Recibo::factory()->create([
        'contrato_id' => $contrato->id,
        'locacion_id' => $locacion->id,
    ]);

    expect($recibo->contrato->id)->toBe($contrato->id);
    expect($recibo->locacion->id)->toBe($locacion->id);
});

test('total suma la renta mas todos los conceptos incluidos', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
    $seguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');

    $recibo = Recibo::factory()->create(['monto_renta' => 1000]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $agua->id, 'monto' => 50]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $luz->id, 'monto' => 80]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $pasadizo->id, 'monto' => 30]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $seguridad->id, 'monto' => 40]);

    expect($recibo->fresh()->total())->toBe(1200.0);
});

test('total excluye los conceptos que el recibo no incluye', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');

    // "luz" y "seguridad" no incluidos: simplemente no tienen fila en recibo_conceptos
    // (specs/024 abandona el patron "excluido pero con monto recordado" de specs/005).
    $recibo = Recibo::factory()->create(['monto_renta' => 1000]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $agua->id, 'monto' => 50]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $pasadizo->id, 'monto' => 30]);

    expect($recibo->fresh()->total())->toBe(1080.0);
});

test('total no incluye renta si el recibo no la cubre', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');

    $recibo = Recibo::factory()->create(['monto_renta' => null]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $agua->id, 'monto' => 50]);

    expect($recibo->fresh()->total())->toBe(50.0);
});

test('editar el valor de referencia del contrato despues de emitir un recibo no altera el recibo ya emitido', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $contrato = Contrato::factory()->create();
    ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $agua->id, 'valor' => 50]);

    $recibo = Recibo::factory()->create([
        'contrato_id' => $contrato->id,
        'locacion_id' => $contrato->locacion_id,
    ]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $agua->id, 'monto' => 50]);

    ValorConceptoContrato::where('contrato_id', $contrato->id)->where('concepto_gasto_fijo_id', $agua->id)->update(['valor' => 999]);

    expect($recibo->fresh()->conceptos->firstWhere('concepto_gasto_fijo_id', $agua->id)->monto)->toBe('50.00');
    expect($contrato->fresh()->valorDeConcepto($agua))->toBe(999.0);
});

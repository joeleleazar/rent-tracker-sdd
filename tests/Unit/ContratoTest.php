<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\ValorConceptoContrato;

test('un contrato pertenece a una locacion y tiene un inquilino principal', function () {
    $locacion = Locacion::factory()->create();
    $inquilino = Inquilino::factory()->create();

    $contrato = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
    ]);

    expect($contrato->locacion)->toBeInstanceOf(Locacion::class);
    expect($contrato->locacion->id)->toBe($locacion->id);
    expect($contrato->inquilinoPrincipal())->toBeInstanceOf(Inquilino::class);
    expect($contrato->inquilinoPrincipal()->id)->toBe($inquilino->id);
});

test('monto_renta se castea como decimal con dos posiciones', function () {
    $contrato = Contrato::factory()->create(['monto_renta' => 1500]);

    expect($contrato->fresh()->monto_renta)->toBe('1500.00');
});

test('estado por defecto es borrador', function () {
    $contrato = Contrato::factory()->create();
    $contrato->estado = null;

    $nuevo = new Contrato($contrato->only(['locacion_id', 'fecha_inicio', 'fecha_fin', 'monto_renta']));

    expect($nuevo->estado)->toBe('borrador');
});

test('acepta unicamente los valores de estado definidos', function (string $estado) {
    $contrato = Contrato::factory()->create(['estado' => $estado]);

    expect($contrato->fresh()->estado)->toBe($estado);
})->with(['borrador', 'activo', 'vencido', 'rescindido']);

test('rechaza un valor de estado invalido a nivel de base de datos', function () {
    $contrato = Contrato::factory()->create();

    $contrato->estado = 'invalido';

    expect(fn () => $contrato->saveQuietly())->toThrow(\Illuminate\Database\QueryException::class);
});

test('valorDeConcepto es nulo por defecto y refleja el valor configurado una vez guardado', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $contrato = Contrato::factory()->create();

    expect($contrato->valorDeConcepto($agua))->toBeNull();

    ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $agua->id, 'valor' => 55]);

    expect($contrato->fresh()->valorDeConcepto($agua))->toBe(55.0);
});

test('valorDeConcepto nunca aplica a renta ni a luz, que se manejan aparte', function () {
    $renta = ConceptoGastoFijo::firstWhere('clave', 'renta');
    $luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $contrato = Contrato::factory()->create();

    expect($renta->esProtegido())->toBeTrue();
    expect($luz->esProtegido())->toBeTrue();
    expect($contrato->valorDeConcepto($renta))->toBeNull();
    expect($contrato->valorDeConcepto($luz))->toBeNull();
});

test('los hitos de notificacion de vencimiento se castean como datetime nulo por defecto', function () {
    $contrato = Contrato::factory()->create();

    expect($contrato->notificado_30_dias_en)->toBeNull();
    expect($contrato->notificado_15_dias_en)->toBeNull();
    expect($contrato->notificado_7_dias_en)->toBeNull();
});

test('un contrato sin garantia registrada indica tieneGarantia false', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => null]);

    expect($contrato->tieneGarantia())->toBeFalse();
});

test('un monto de garantia igual a cero se trata como sin garantia', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 0]);

    expect($contrato->tieneGarantia())->toBeFalse();
});

test('un monto de garantia mayor a cero indica tieneGarantia true', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    expect($contrato->tieneGarantia())->toBeTrue();
    expect($contrato->fresh()->monto_garantia)->toBe('1500.00');
});

test('garantiaResuelta refleja el estado_garantia del contrato', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500, 'estado_garantia' => 'entregada']);
    expect($contrato->garantiaResuelta())->toBeFalse();

    $contrato->update(['estado_garantia' => 'resuelta']);
    expect($contrato->fresh()->garantiaResuelta())->toBeTrue();
});

<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\ReciboConcepto;
use App\Models\ValorConceptoContrato;
use App\Services\ServicioPlantillaRecibos;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->servicio = app(ServicioPlantillaRecibos::class);
    $this->luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
});

function contratoActivoParaPlantilla(Locacion $locacion): Contrato
{
    return Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => Inquilino::factory()->create()->id,
        'estado' => 'activo',
        'fecha_inicio' => Carbon::parse('2026-07-01'),
        'fecha_fin' => Carbon::parse('2027-01-01'),
        'monto_renta' => 1500,
    ]);
}

test('los encabezados incluyen periodo, columnas fijas y una por concepto activo no protegido', function () {
    $encabezados = $this->servicio->encabezados();

    expect($encabezados[0])->toBe('periodo')
        ->and($encabezados)->toContain('Renta')
        ->and($encabezados)->toContain('Luz')
        ->and($encabezados)->toContain('Agua')
        ->and($encabezados)->toContain('Seguridad')
        ->and(end($encabezados))->toBe('Total')
        ->and(collect($encabezados)->filter(fn ($e) => $e === 'Renta'))->toHaveCount(1);
});

test('genera una fila por locacion con contrato activo, con la renta del contrato cuando no hay recibo', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = contratoActivoParaPlantilla($local);
    ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $this->agua->id, 'valor' => 45]);
    Locacion::factory()->create(['es_alquilable' => true]); // sin contrato → no debe aparecer

    $filas = $this->servicio->filas(Carbon::parse('2026-08-01'));

    expect($filas)->toHaveCount(1)
        ->and($filas[0]['local_id'])->toBe($local->id)
        ->and($filas[0]['Renta'])->toBe('1500.00')
        ->and($filas[0]['Agua'])->toBe('45.00');
});

test('precarga los montos del recibo vigente cuando existe uno solo', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = contratoActivoParaPlantilla($local);
    $recibo = Recibo::factory()->create([
        'locacion_id' => $local->id,
        'contrato_id' => $contrato->id,
        'periodo' => '2026-08-01',
        'monto_renta' => 1200,
    ]);
    ReciboConcepto::create(['recibo_id' => $recibo->id, 'concepto_gasto_fijo_id' => $this->luz->id, 'monto' => 80]);
    ReciboConcepto::create(['recibo_id' => $recibo->id, 'concepto_gasto_fijo_id' => $this->agua->id, 'monto' => 30]);

    $fila = $this->servicio->filas(Carbon::parse('2026-08-01'))[0];

    expect($fila['Renta'])->toBe('1200.00')
        ->and($fila['Luz'])->toBe('80.00')
        ->and($fila['Agua'])->toBe('30.00')
        ->and($fila['Total'])->toBe('1310.00');
});

test('marca la fila cuando la locacion tiene varios recibos vigentes', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = contratoActivoParaPlantilla($local);
    Recibo::factory()->count(2)->create([
        'locacion_id' => $local->id,
        'contrato_id' => $contrato->id,
        'periodo' => '2026-08-01',
    ]);

    $fila = $this->servicio->filas(Carbon::parse('2026-08-01'))[0];

    expect($fila['Contrato'])->toContain('varios recibos');
});

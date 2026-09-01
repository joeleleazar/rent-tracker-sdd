<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Pago;
use App\Models\Recibo;
use App\Services\ServicioCalculoFechaLimitePago;
use App\Services\ServicioPanelCobranza;
use Illuminate\Support\Carbon;

/**
 * specs/043: cálculos de solo lectura del panel de inicio.
 */

function servicioPanel(): ServicioPanelCobranza
{
    return app(ServicioPanelCobranza::class);
}

/** Crea un recibo con un contrato/locación/inquilino, un monto de renta y un periodo dados. */
function reciboConDatos(array $atributos = [], ?Locacion $locacion = null): Recibo
{
    $contrato = Contrato::factory()->create($locacion ? ['locacion_id' => $locacion->id] : []);

    return Recibo::factory()->create(array_merge([
        'contrato_id' => $contrato->id,
        'locacion_id' => $contrato->locacion_id,
        'monto_renta' => 1000,
    ], $atributos));
}

beforeEach(function () {
    Carbon::setTestNow('2026-08-15');
});

afterEach(function () {
    Carbon::setTestNow();
});

// ----------------------------------------------------------------------------
// Grupo morosos (T006)
// ----------------------------------------------------------------------------

test('un recibo anulado nunca es moroso', function () {
    reciboConDatos(['estado' => 'anulado', 'periodo' => '2026-05-01']);

    expect(servicioPanel()->recibosMorosos())->toHaveCount(0);
});

test('un recibo pagado por completo nunca es moroso', function () {
    $recibo = reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 800]);
    Pago::factory()->create(['recibo_id' => $recibo->id, 'monto' => 800]);

    expect(servicioPanel()->recibosMorosos())->toHaveCount(0);
});

test('un recibo con fecha limite futura no es moroso sino proximo vencimiento', function () {
    // periodo del propio mes en curso: su último sábado es posterior al 15/08.
    reciboConDatos(['periodo' => '2026-08-01']);

    expect(servicioPanel()->recibosMorosos())->toHaveCount(0);
});

test('un recibo no anulado con saldo pendiente y fecha limite pasada es moroso', function () {
    reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 1000]);

    $morosos = servicioPanel()->recibosMorosos();

    expect($morosos)->toHaveCount(1);
    expect($morosos->first()->saldoPendiente)->toBe(1000.0);
});

test('el saldo pendiente nunca es negativo y un sobrepago no genera morosidad', function () {
    $recibo = reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 500]);
    Pago::factory()->create(['recibo_id' => $recibo->id, 'monto' => 700]);

    expect(servicioPanel()->recibosMorosos())->toHaveCount(0);
});

test('los dias de atraso se cuentan desde el ultimo sabado del mes del periodo hasta hoy', function () {
    reciboConDatos(['periodo' => '2026-06-01', 'monto_renta' => 1000]);

    $fechaLimiteEsperada = app(ServicioCalculoFechaLimitePago::class)->calcular(Carbon::parse('2026-06-01'));
    $diasEsperados = (int) $fechaLimiteEsperada->diffInDays(Carbon::parse('2026-08-15'));

    $fila = servicioPanel()->recibosMorosos()->first();

    expect($fila->fechaLimite->toDateString())->toBe($fechaLimiteEsperada->toDateString());
    expect($fila->diasDeAtraso)->toBe($diasEsperados);
});

test('la clasificacion por tramo de antiguedad respeta los limites 30/60/90', function () {
    $servicio = servicioPanel();

    expect($servicio->tramoDeAntiguedad(1))->toBe('1-30');
    expect($servicio->tramoDeAntiguedad(30))->toBe('1-30');
    expect($servicio->tramoDeAntiguedad(31))->toBe('31-60');
    expect($servicio->tramoDeAntiguedad(60))->toBe('31-60');
    expect($servicio->tramoDeAntiguedad(61))->toBe('61-90');
    expect($servicio->tramoDeAntiguedad(90))->toBe('61-90');
    expect($servicio->tramoDeAntiguedad(91))->toBe('90+');
});

test('el listado de morosos se ordena por dias de atraso de mayor a menor', function () {
    reciboConDatos(['periodo' => '2026-07-01', 'monto_renta' => 1000]); // menos atraso
    reciboConDatos(['periodo' => '2026-03-01', 'monto_renta' => 1000]); // más atraso
    reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 1000]); // intermedio

    $dias = servicioPanel()->recibosMorosos()->pluck('diasDeAtraso')->all();

    expect($dias)->toBe(collect($dias)->sortDesc()->values()->all());
});

test('el resumen de morosidad cuadra: cantidades, inquilinos distintos y suma por tramos', function () {
    $inquilino = Inquilino::factory()->create();
    $contrato = Contrato::factory()->create(['inquilino_id' => $inquilino->id]);

    // dos recibos morosos del mismo inquilino/contrato -> 1 inquilino, 2 recibos
    Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $contrato->locacion_id, 'monto_renta' => 1000, 'periodo' => '2026-05-01']);
    Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $contrato->locacion_id, 'monto_renta' => 1000, 'periodo' => '2026-06-01']);
    // un tercer recibo moroso de otro inquilino
    reciboConDatos(['periodo' => '2026-04-01', 'monto_renta' => 500]);

    $morosos = servicioPanel()->recibosMorosos();
    $resumen = servicioPanel()->resumenMorosidad($morosos);

    expect($resumen['cantidadRecibos'])->toBe(3);
    expect($resumen['cantidadInquilinos'])->toBe(2);
    expect($resumen['montoAdeudadoVencido'])->toBe(2500.0);

    $sumaTramos = collect($resumen['porTramo'])->sum('monto');
    $cantTramos = collect($resumen['porTramo'])->sum('cantidad');
    expect($sumaTramos)->toBe($resumen['montoAdeudadoVencido']);
    expect($cantTramos)->toBe($resumen['cantidadRecibos']);
});

test('el filtro por tramo y por rama de locacion se aplican y recalculan el resumen', function () {
    $galeria = Locacion::factory()->create();
    $local = Locacion::factory()->create(['locacion_padre_id' => $galeria->id]);
    $otra = Locacion::factory()->create();

    reciboConDatos(['periodo' => '2026-07-01', 'monto_renta' => 1000], $local);   // rama galería
    reciboConDatos(['periodo' => '2026-01-01', 'monto_renta' => 1000], $otra);    // fuera de la rama, muy atrasado

    $soloRama = servicioPanel()->recibosMorosos(null, [$galeria->id, $local->id]);
    expect($soloRama)->toHaveCount(1);
    expect(servicioPanel()->resumenMorosidad($soloRama)['montoAdeudadoVencido'])->toBe(1000.0);
});

// ----------------------------------------------------------------------------
// Grupo próximos vencimientos (T017)
// ----------------------------------------------------------------------------

test('un recibo con saldo y fecha limite hoy o futura es proximo vencimiento y no moroso', function () {
    reciboConDatos(['periodo' => '2026-08-01', 'monto_renta' => 1000]);

    expect(servicioPanel()->recibosMorosos())->toHaveCount(0);
    expect(servicioPanel()->proximosVencimientos())->toHaveCount(1);
});

test('los proximos vencimientos se ordenan por fecha limite ascendente', function () {
    reciboConDatos(['periodo' => '2026-10-01', 'monto_renta' => 1000]);
    reciboConDatos(['periodo' => '2026-08-01', 'monto_renta' => 1000]);
    reciboConDatos(['periodo' => '2026-09-01', 'monto_renta' => 1000]);

    $fechas = servicioPanel()->proximosVencimientos()->pluck('fechaLimite')->map->toDateString()->all();

    expect($fechas)->toBe(collect($fechas)->sort()->values()->all());
});

// ----------------------------------------------------------------------------
// Grupo indicadores (T021)
// ----------------------------------------------------------------------------

test('facturado y cobrado del periodo cuentan solo recibos del mes en curso', function () {
    $delMes = reciboConDatos(['periodo' => '2026-08-01', 'monto_renta' => 1000]);
    Pago::factory()->create(['recibo_id' => $delMes->id, 'monto' => 400, 'fecha_pago' => '2026-08-10']);
    // recibo de un mes anterior, no cuenta en "del periodo"
    reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 999]);

    $ind = servicioPanel()->indicadoresDelPeriodo();

    expect($ind['facturadoDelPeriodo'])->toBe(1000.0);
    expect($ind['cobradoDeRecibosDelPeriodo'])->toBe(400.0);
    expect($ind['tasaDeCobranza'])->toBe(40.0);
});

test('recaudado este mes cuenta pagos por fecha de pago de cualquier periodo y excluye recibos anulados', function () {
    $viejo = reciboConDatos(['periodo' => '2026-03-01', 'monto_renta' => 1000]);
    Pago::factory()->create(['recibo_id' => $viejo->id, 'monto' => 300, 'fecha_pago' => '2026-08-05']);

    $anulado = reciboConDatos(['estado' => 'anulado', 'periodo' => '2026-08-01', 'monto_renta' => 1000]);
    Pago::factory()->create(['recibo_id' => $anulado->id, 'monto' => 999, 'fecha_pago' => '2026-08-06']);

    $pagoViejo = reciboConDatos(['periodo' => '2026-07-01', 'monto_renta' => 1000]);
    Pago::factory()->create(['recibo_id' => $pagoViejo->id, 'monto' => 100, 'fecha_pago' => '2026-07-30']); // mes anterior

    expect(servicioPanel()->indicadoresDelPeriodo()['recaudadoEsteMes'])->toBe(300.0);
});

test('la tasa de cobranza es null cuando no hay facturado del periodo', function () {
    reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 1000]);

    expect(servicioPanel()->indicadoresDelPeriodo()['tasaDeCobranza'])->toBeNull();
});

test('la cartera total por cobrar suma el saldo clampado por recibo', function () {
    $a = reciboConDatos(['periodo' => '2026-05-01', 'monto_renta' => 1000]);
    Pago::factory()->create(['recibo_id' => $a->id, 'monto' => 1200]); // sobrepago -> aporta 0, no -200
    reciboConDatos(['periodo' => '2026-08-01', 'monto_renta' => 700]); // aporta 700

    expect(servicioPanel()->indicadoresDelPeriodo()['carteraTotalPorCobrar'])->toBe(700.0);
});

// ----------------------------------------------------------------------------
// Grupo contratos por vencer (T022)
// ----------------------------------------------------------------------------

test('los grupos de contratos por vencer son acumulativos 7 en 15 en 30', function () {
    Contrato::factory()->create(['estado' => 'activo', 'fecha_fin' => '2026-08-20']); // 5 días

    $grupos = servicioPanel()->contratosPorVencer();

    expect($grupos['dentro7'])->toHaveCount(1);
    expect($grupos['dentro15'])->toHaveCount(1);
    expect($grupos['dentro30'])->toHaveCount(1);
});

test('un contrato que vence en 20 dias solo aparece en el grupo de 30', function () {
    Contrato::factory()->create(['estado' => 'activo', 'fecha_fin' => '2026-09-04']); // 20 días

    $grupos = servicioPanel()->contratosPorVencer();

    expect($grupos['dentro7'])->toHaveCount(0);
    expect($grupos['dentro15'])->toHaveCount(0);
    expect($grupos['dentro30'])->toHaveCount(1);
});

test('un contrato ya vencido o no activo no aparece en ningun grupo', function () {
    Contrato::factory()->create(['estado' => 'activo', 'fecha_fin' => '2026-08-14']); // ayer
    Contrato::factory()->create(['estado' => 'borrador', 'fecha_fin' => '2026-08-18']);
    Contrato::factory()->create(['estado' => 'rescindido', 'fecha_fin' => '2026-08-18']);

    $grupos = servicioPanel()->contratosPorVencer();

    expect($grupos['dentro30'])->toHaveCount(0);
});

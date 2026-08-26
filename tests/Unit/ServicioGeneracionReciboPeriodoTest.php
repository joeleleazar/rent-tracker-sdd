<?php

use App\Exceptions\ConceptosReciboYaCubiertosException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Models\ConceptoGastoFijo;
use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioCalculoProrrateoContrato;
use App\Services\ServicioGeneracionReciboPeriodo;

beforeEach(function () {
    $this->servicio = new ServicioGeneracionReciboPeriodo(new ServicioCalculoProrrateoContrato());
    $this->locacion = Locacion::factory()->create();
    $this->inquilino = Inquilino::factory()->create();
    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $this->luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $this->pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
    $this->seguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');
});

function datosBaseRecibo(): array
{
    return [
        'incluye_alquiler' => true,
        'monto_renta' => 1500,
        'fecha_emision' => now()->format('Y-m-d'),
        'conceptos' => [
            /* concepto_gasto_fijo_id => monto, se completa en cada test con los ids reales */
        ],
    ];
}

test('bloquea la generacion si no hay contrato activo en el periodo', function () {
    expect(fn () => $this->servicio->generar($this->locacion, now()->startOfMonth(), datosBaseRecibo()))
        ->toThrow(SinContratoActivoEnPeriodoException::class);
});

test('bloquea un segundo recibo que repite un concepto ya cubierto', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
    ]);

    $periodo = now()->startOfMonth();
    $datos = array_merge(datosBaseRecibo(), [
        'conceptos' => [$this->agua->id => 50, $this->luz->id => 0, $this->pasadizo->id => 30, $this->seguridad->id => 40],
    ]);
    $this->servicio->generar($this->locacion, $periodo, $datos);

    expect(fn () => $this->servicio->generar($this->locacion, $periodo, $datos))
        ->toThrow(ConceptosReciboYaCubiertosException::class);
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('permite un segundo recibo para la misma locacion y periodo con conceptos distintos', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
    ]);

    $periodo = now()->startOfMonth();
    $soloAlquiler = array_merge(datosBaseRecibo(), ['conceptos' => []]);
    $resto = array_merge(datosBaseRecibo(), [
        'incluye_alquiler' => false,
        'conceptos' => [$this->agua->id => 50, $this->luz->id => 0, $this->pasadizo->id => 30, $this->seguridad->id => 40],
    ]);

    $this->servicio->generar($this->locacion, $periodo, $soloAlquiler);
    $this->servicio->generar($this->locacion, $periodo, $resto);

    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(2);
});

test('conceptosDisponibles excluye renta cuando ya esta cubierta y los conceptos ya cubiertos por un recibo existente', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
    ]);

    $periodo = now()->startOfMonth();
    $soloAlquiler = array_merge(datosBaseRecibo(), ['conceptos' => []]);
    $this->servicio->generar($this->locacion, $periodo, $soloAlquiler);

    $disponibles = $this->servicio->conceptosDisponibles($this->locacion, $periodo);

    expect($disponibles->pluck('nombre')->all())->toBe(['Agua', 'Luz', 'Luz de Pasadizo', 'Seguridad']);
});

test('un recibo anulado no cubre sus conceptos: conceptosDisponibles, reciboQueCubre y generar (specs/026)', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
    ]);

    $periodo = now()->startOfMonth();
    $datos = array_merge(datosBaseRecibo(), [
        'conceptos' => [$this->agua->id => 50, $this->luz->id => 0, $this->pasadizo->id => 30, $this->seguridad->id => 40],
    ]);
    $recibo = $this->servicio->generar($this->locacion, $periodo, $datos);
    $recibo->update(['estado' => 'anulado']);

    $disponibles = $this->servicio->conceptosDisponibles($this->locacion, $periodo);
    expect($disponibles->pluck('nombre')->all())->toBe(['Renta', 'Agua', 'Luz', 'Luz de Pasadizo', 'Seguridad']);

    $reciboQueCubre = $this->servicio->reciboQueCubre($this->locacion, $periodo);
    expect($reciboQueCubre)->toBeEmpty();

    // generar() no debe lanzar ConceptosReciboYaCubiertosException: el unico recibo que
    // cubria estos conceptos esta anulado.
    $nuevo = $this->servicio->generar($this->locacion, $periodo, $datos);
    expect($nuevo->id)->not->toBe($recibo->id);
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(2);
});

test('FR-008: la segunda de dos confirmaciones casi simultaneas con el mismo concepto es rechazada', function () {
    // Simula la condicion de carrera de FR-008 con dos llamadas secuenciales dentro de un
    // unico proceso Pest: no reproduce una concurrencia real de dos procesos (para eso haria
    // falta Dusk/Playwright, fuera de alcance — mismo limite ya documentado en specs/016
    // research.md H2), pero SI confirma que generar() relee el estado real de la base de datos
    // en el momento de confirmar (dentro de la transaccion, con lockForUpdate) en vez de confiar
    // en un estado leido antes — que es la parte de la regla que este test puede verificar.
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
    ]);

    $periodo = now()->startOfMonth();
    $soloAlquiler = array_merge(datosBaseRecibo(), ['conceptos' => []]);

    // Ambas "confirmaciones" leen el mismo estado inicial (ningun concepto cubierto) antes de
    // que cualquiera de las dos se confirme — como pasaria con dos pestañas abiertas al mismo
    // tiempo con el modal ya cargado.
    $disponiblesAntes = $this->servicio->conceptosDisponibles($this->locacion, $periodo);
    expect($disponiblesAntes->pluck('nombre'))->toContain('Agua');

    $this->servicio->generar($this->locacion, $periodo, $soloAlquiler);

    expect(fn () => $this->servicio->generar($this->locacion, $periodo, $soloAlquiler))
        ->toThrow(ConceptosReciboYaCubiertosException::class);
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('el monto de luz sugerido usa el total ya persistido en la lectura, no lo recalcula', function () {
    // specs/019 FR-006: el total ya se fijó al registrar la lectura (consumo x tarifa vigente
    // EN ESE MOMENTO); cambiar la tarifa despues no debe alterar el monto sugerido del recibo.
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 0.75]);
    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'total' => 75,
    ]);

    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 5]);

    expect($this->servicio->calcularMontoLuzSugerido($lectura))->toBe(75.0);
});

test('el monto de luz sugerido es 0 sin lectura', function () {
    expect($this->servicio->calcularMontoLuzSugerido(null))->toBe(0.0);
});

test('el monto de luz sugerido no recalcula aunque el total no coincida con consumo por tarifa vigente', function () {
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 0.75]);
    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'total' => 200,
    ]);

    expect($this->servicio->calcularMontoLuzSugerido($lectura))->toBe(200.0);
});

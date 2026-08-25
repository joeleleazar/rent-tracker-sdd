<?php

use App\Models\ConfiguracionGeneral;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * specs/018 (FR-007): tras el rediseño a tabla clave-valor, el invariante ya
 * no es "una fila con id=1" (eso era una particularidad de la forma anterior,
 * nunca parte del contrato público) sino lo descrito en
 * contracts/contrato-configuracion-general.md — lectura con defaults,
 * escritura parcial sin afectar otros atributos, y extensibilidad sin
 * migraciones de esquema.
 */
test('actual devuelve los valores por defecto cuando no existe ninguna configuracion guardada', function () {
    $configuracion = ConfiguracionGeneral::actual();

    expect($configuracion)->toBeInstanceOf(ConfiguracionGeneral::class);
    expect($configuracion->tarifa_luz_por_unidad)->toBe('0.0000');
    expect($configuracion->dias_anticipacion_alerta_pago)->toBe(5);
    expect($configuracion->alerta_pago_mes_enviada_en)->toBeNull();
    expect($configuracion->correo_notificaciones_vencimiento)->toBe(config('mail.from.address', 'hello@example.com'));
});

test('actualizar un atributo lo persiste sin afectar a los demas', function () {
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => '0.9000']);
    ConfiguracionGeneral::actual()->update(['dias_anticipacion_alerta_pago' => 12]);

    $configuracion = ConfiguracionGeneral::actual();
    expect($configuracion->tarifa_luz_por_unidad)->toBe('0.9000');
    expect($configuracion->dias_anticipacion_alerta_pago)->toBe(12);
    expect($configuracion->correo_notificaciones_vencimiento)->toBe(config('mail.from.address', 'hello@example.com'));
});

test('actualizar varios atributos a la vez persiste todos sin perder los demas', function () {
    ConfiguracionGeneral::actual()->update(['dias_anticipacion_alerta_pago' => 3]);

    ConfiguracionGeneral::actual()->update([
        'tarifa_luz_por_unidad' => '1.2500',
        'correo_notificaciones_vencimiento' => 'ops@ejemplo.com',
    ]);

    $configuracion = ConfiguracionGeneral::actual();
    expect($configuracion->tarifa_luz_por_unidad)->toBe('1.2500');
    expect($configuracion->correo_notificaciones_vencimiento)->toBe('ops@ejemplo.com');
    expect($configuracion->dias_anticipacion_alerta_pago)->toBe(3);
});

test('los casts de decimal entero y fecha siguen aplicando sobre los valores leidos', function () {
    ConfiguracionGeneral::actual()->update([
        'tarifa_luz_por_unidad' => '2',
        'dias_anticipacion_alerta_pago' => '7',
        'alerta_pago_mes_enviada_en' => '2026-08-01 00:00:00',
    ]);

    $configuracion = ConfiguracionGeneral::actual();
    expect($configuracion->tarifa_luz_por_unidad)->toBe('2.0000');
    expect($configuracion->dias_anticipacion_alerta_pago)->toBeInt();
    expect($configuracion->alerta_pago_mes_enviada_en)->toBeInstanceOf(Carbon::class);
    expect($configuracion->alerta_pago_mes_enviada_en->format('Y-m-d'))->toBe('2026-08-01');
});

test('agregar una configuracion nueva se logra con una fila de datos sin ninguna migracion de esquema', function () {
    DB::table('configuracion_general')->insert([
        'clave' => 'nueva_configuracion_de_prueba',
        'valor' => json_encode('valor-de-prueba'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('configuracion_general')->where('clave', 'nueva_configuracion_de_prueba')->value('valor'))
        ->toBe('"valor-de-prueba"');

    // La clave nueva no interfiere con la lectura normal de las configuraciones ya conocidas.
    expect(ConfiguracionGeneral::actual()->tarifa_luz_por_unidad)->toBe('0.0000');
});

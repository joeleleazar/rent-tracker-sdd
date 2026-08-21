<?php

use App\Mail\AlertaFechaLimitePago;
use App\Models\ConfiguracionGeneral;
use Illuminate\Support\Facades\Mail;

test('el comando pagos:alertar-fecha-limite envia la alerta cuando corresponde', function () {
    Mail::fake();
    ConfiguracionGeneral::actual()->update([
        'correo_notificaciones_vencimiento' => 'admin@ejemplo.com',
        'dias_anticipacion_alerta_pago' => 5,
    ]);

    // Setiembre 2026: fecha límite = 26/09. A 5 días = 21/09.
    Carbon\Carbon::setTestNow('2026-09-21');

    $this->artisan('pagos:alertar-fecha-limite')
        ->assertExitCode(0);

    Mail::assertSent(AlertaFechaLimitePago::class);
    expect(ConfiguracionGeneral::actual()->alerta_pago_mes_enviada_en)->not->toBeNull();

    Carbon\Carbon::setTestNow();
});

test('el comando no reenvia la alerta duplicada', function () {
    Mail::fake();
    ConfiguracionGeneral::actual()->update([
        'correo_notificaciones_vencimiento' => 'admin@ejemplo.com',
        'dias_anticipacion_alerta_pago' => 5,
    ]);

    Carbon\Carbon::setTestNow('2026-09-21');
    $this->artisan('pagos:alertar-fecha-limite')->assertExitCode(0);

    Mail::fake();
    $this->artisan('pagos:alertar-fecha-limite')->assertExitCode(0);
    Mail::assertNothingSent();

    Carbon\Carbon::setTestNow();
});

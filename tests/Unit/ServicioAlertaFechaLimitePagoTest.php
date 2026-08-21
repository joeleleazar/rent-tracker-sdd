<?php

use App\Mail\AlertaFechaLimitePago;
use App\Models\ConfiguracionGeneral;
use App\Services\ServicioAlertaFechaLimitePago;
use App\Services\ServicioCalculoFechaLimitePago;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->servicio = new ServicioAlertaFechaLimitePago(new ServicioCalculoFechaLimitePago());
    ConfiguracionGeneral::actual()->update([
        'correo_notificaciones_vencimiento' => 'admin@ejemplo.com',
        'dias_anticipacion_alerta_pago' => 5,
    ]);
});

test('envia la alerta cuando faltan los dias de anticipacion configurados', function () {
    // Setiembre 2026: fecha límite = 26/09. A 5 días = 21/09.
    Carbon\Carbon::setTestNow('2026-09-21');

    $enviada = $this->servicio->verificarYAlertar();

    expect($enviada)->toBeTrue();
    Mail::assertSent(AlertaFechaLimitePago::class, function ($mail) {
        return $mail->hasTo('admin@ejemplo.com') && $mail->fechaLimite->format('Y-m-d') === '2026-09-26';
    });
    expect(ConfiguracionGeneral::actual()->alerta_pago_mes_enviada_en)->not->toBeNull();

    Carbon\Carbon::setTestNow();
});

test('no envia la alerta antes de la ventana de anticipacion', function () {
    Carbon\Carbon::setTestNow('2026-09-10');

    $enviada = $this->servicio->verificarYAlertar();

    expect($enviada)->toBeFalse();
    Mail::assertNothingSent();

    Carbon\Carbon::setTestNow();
});

test('no reenvia la alerta duplicada dentro del mismo mes', function () {
    Carbon\Carbon::setTestNow('2026-09-21');
    $this->servicio->verificarYAlertar();

    Mail::fake();
    $segundaVez = $this->servicio->verificarYAlertar();

    expect($segundaVez)->toBeFalse();
    Mail::assertNothingSent();

    Carbon\Carbon::setTestNow();
});

test('respeta el cambio de anticipacion configurada', function () {
    ConfiguracionGeneral::actual()->update(['dias_anticipacion_alerta_pago' => 10]);
    // A 10 días de 26/09 = 16/09.
    Carbon\Carbon::setTestNow('2026-09-16');

    $enviada = $this->servicio->verificarYAlertar();

    expect($enviada)->toBeTrue();

    Carbon\Carbon::setTestNow();
});

test('envia la alerta en la primera ejecucion disponible si la anticipacion excede los dias del mes', function () {
    ConfiguracionGeneral::actual()->update(['dias_anticipacion_alerta_pago' => 60]);
    Carbon\Carbon::setTestNow('2026-09-01');

    $enviada = $this->servicio->verificarYAlertar();

    expect($enviada)->toBeTrue();

    Carbon\Carbon::setTestNow();
});

test('vuelve a alertar en un mes nuevo aunque el mes anterior ya haya enviado la suya', function () {
    Carbon\Carbon::setTestNow('2026-09-21');
    $this->servicio->verificarYAlertar();

    Mail::fake();
    // Octubre 2026: fecha límite = 31/10 (Edge Case: mes que termina en sábado).
    Carbon\Carbon::setTestNow('2026-10-31');
    $enviada = $this->servicio->verificarYAlertar();

    expect($enviada)->toBeTrue();

    Carbon\Carbon::setTestNow();
});

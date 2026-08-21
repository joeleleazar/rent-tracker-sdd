<?php

use App\Mail\ContratoProximoAVencer;
use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use App\Services\ServicioNotificacionVencimientoContrato;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    ConfiguracionGeneral::actual()->update(['correo_notificaciones_vencimiento' => 'admin@ejemplo.com']);
    $this->servicio = new ServicioNotificacionVencimientoContrato();
});

test('envia notificacion cuando el contrato esta a exactamente 30 dias de vencer', function () {
    $contrato = Contrato::factory()->create([
        'estado' => 'activo',
        'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $total = $this->servicio->verificarYNotificar();

    expect($total)->toBe(1);
    Mail::assertSent(ContratoProximoAVencer::class, function ($mail) use ($contrato) {
        return $mail->contrato->id === $contrato->id
            && $mail->diasAnticipacion === 30
            && $mail->hasTo('admin@ejemplo.com');
    });
    expect($contrato->fresh()->notificado_30_dias_en)->not->toBeNull();
});

test('no reenvia el mismo hito en una segunda ejecucion', function () {
    Contrato::factory()->create([
        'estado' => 'activo',
        'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $this->servicio->verificarYNotificar();
    Mail::fake();
    $totalSegundaVez = $this->servicio->verificarYNotificar();

    expect($totalSegundaVez)->toBe(0);
    Mail::assertNothingSent();
});

test('envia el hito de 15 dias como notificacion distinta tras el de 30', function () {
    $contrato = Contrato::factory()->create([
        'estado' => 'activo',
        'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $this->servicio->verificarYNotificar();

    $contrato->update(['fecha_fin' => now()->addDays(15)->format('Y-m-d')]);
    // Simula el paso del tiempo sin disparar el reinicio de hitos (eso lo hace el
    // controlador al editar fecha_fin, no el modelo directamente).
    Mail::fake();
    $total = $this->servicio->verificarYNotificar();

    expect($total)->toBe(1);
    Mail::assertSent(ContratoProximoAVencer::class, fn ($mail) => $mail->diasAnticipacion === 15);
    expect($contrato->fresh()->notificado_15_dias_en)->not->toBeNull();
});

test('envia todos los hitos ya alcanzados en una sola ejecucion si el contrato se crea muy cerca del vencimiento', function () {
    $contrato = Contrato::factory()->create([
        'estado' => 'activo',
        'fecha_fin' => now()->addDays(10)->format('Y-m-d'),
    ]);

    $total = $this->servicio->verificarYNotificar();

    expect($total)->toBe(2);
    Mail::assertSent(ContratoProximoAVencer::class, 2);
    expect($contrato->fresh()->notificado_30_dias_en)->not->toBeNull();
    expect($contrato->fresh()->notificado_15_dias_en)->not->toBeNull();
    expect($contrato->fresh()->notificado_7_dias_en)->toBeNull();
});

test('no notifica contratos rescindidos', function () {
    Contrato::factory()->create([
        'estado' => 'rescindido',
        'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $total = $this->servicio->verificarYNotificar();

    expect($total)->toBe(0);
    Mail::assertNothingSent();
});

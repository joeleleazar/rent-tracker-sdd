<?php

use App\Mail\ContratoProximoAVencer;
use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use Illuminate\Support\Facades\Mail;

test('el comando contratos:verificar-vencimientos envia correos para hitos alcanzados', function () {
    Mail::fake();
    ConfiguracionGeneral::actual()->update(['correo_notificaciones_vencimiento' => 'admin@ejemplo.com']);

    $contrato = Contrato::factory()->create([
        'estado' => 'activo',
        'fecha_fin' => now()->addDays(7)->format('Y-m-d'),
    ]);

    $this->artisan('contratos:verificar-vencimientos')
        ->assertExitCode(0);

    Mail::assertSent(ContratoProximoAVencer::class, 3);
    expect($contrato->fresh()->notificado_30_dias_en)->not->toBeNull();
    expect($contrato->fresh()->notificado_15_dias_en)->not->toBeNull();
    expect($contrato->fresh()->notificado_7_dias_en)->not->toBeNull();
});

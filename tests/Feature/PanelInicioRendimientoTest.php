<?php

use App\Models\Contrato;
use App\Models\Pago;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * specs/043 SC-006: el panel se arma con un número acotado de consultas que NO
 * crece con la cantidad de recibos/contratos (sin N+1), gracias a la consulta
 * base única con `withSum`. Con un patrón por fila, 60 recibos dispararían
 * decenas de consultas extra y esta prueba fallaría.
 */
test('el panel se arma con un número acotado de consultas, sin N+1', function () {
    Recibo::factory()->count(60)->create();

    Recibo::query()->inRandomOrder()->limit(30)->get()
        ->each(fn (Recibo $r) => Pago::factory()->create(['recibo_id' => $r->id, 'monto' => 250]));

    for ($i = 0; $i < 10; $i++) {
        Contrato::factory()->create(['estado' => 'activo', 'fecha_fin' => now()->addDays(5 + $i)->toDateString()]);
    }

    $usuario = User::factory()->create();

    DB::enableQueryLog();
    $inicio = microtime(true);

    $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

    $ms = (microtime(true) - $inicio) * 1000;
    $consultas = count(DB::getQueryLog());

    fwrite(STDERR, sprintf("\n[panel] render=%dms consultas=%d recibos=%d\n", (int) $ms, $consultas, Recibo::count()));

    // presupuesto del plan: ~5 consultas de datos + auth/sesión/vista
    expect($consultas)->toBeLessThanOrEqual(12);
    expect($ms)->toBeLessThan(2000.0);
})->group('rendimiento');

<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;

/**
 * specs/042 (US1): el componente x-mensaje-alerta pasó a ser un alert de
 * Bootstrap descartable (autocierre a los 8 s con pausa por hover, más botón
 * de cierre manual). Estas pruebas fijan el contrato del HTML renderizado
 * (contracts/comportamiento-ui.md §A) y verifican la no-regresión del texto
 * del mensaje en una vista real (§C).
 */

test('la notificación de éxito se renderiza como alert descartable con botón de cierre', function () {
    $html = Blade::render('<x-mensaje-alerta tipo="exito">Operación completada</x-mensaje-alerta>');

    expect($html)
        ->toContain('alert-success')
        ->toContain('alert-dismissible')
        ->toContain('fade')
        ->toContain('show')
        ->toContain('role="alert"')
        ->toContain('bi-check-circle-fill')
        ->toContain('data-bs-dismiss="alert"')
        ->toContain('aria-label="Cerrar"')
        ->toContain('Operación completada');
});

test('la notificación de error se renderiza como alert descartable con su ícono', function () {
    $html = Blade::render('<x-mensaje-alerta tipo="error">Algo salió mal</x-mensaje-alerta>');

    expect($html)
        ->toContain('alert-danger')
        ->toContain('alert-dismissible')
        ->toContain('data-bs-dismiss="alert"')
        ->toContain('bi-exclamation-triangle-fill')
        ->toContain('Algo salió mal');
});

test('las clases pasadas por atributo se siguen fusionando', function () {
    $html = Blade::render('<x-mensaje-alerta tipo="exito" class="mb-4">Hola</x-mensaje-alerta>');

    expect($html)->toContain('mb-4');
});

test('una vista real sigue mostrando el texto del mensaje flash', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->withSession(['mensaje' => 'Concepto guardado correctamente'])
        ->get(route('conceptosGastoFijo.index'))
        ->assertOk()
        ->assertSee('Concepto guardado correctamente');
});

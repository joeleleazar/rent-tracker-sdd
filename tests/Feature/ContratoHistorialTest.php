<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\User;

test('el historial muestra los contratos en orden cronologico inverso y destaca el activo', function () {
    $admin = User::factory()->create();
    $locacion = Locacion::factory()->create();
    $inquilino = Inquilino::factory()->create();

    $vencido = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-12-31',
        'estado' => 'vencido',
    ]);

    $activo = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
        'fecha_inicio' => '2025-01-01',
        'fecha_fin' => '2025-12-31',
        'estado' => 'activo',
    ]);

    $futuro = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
        'fecha_inicio' => '2027-01-01',
        'fecha_fin' => '2027-12-31',
        'estado' => 'borrador',
    ]);

    $respuesta = $this->actingAs($admin)->get(route('contratos.index', $locacion));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder([
        "Contrato #{$futuro->id}",
        "Contrato #{$activo->id}",
        "Contrato #{$vencido->id}",
    ]);
    $respuesta->assertSee('Activo');
});

test('el historial requiere autenticacion', function () {
    $locacion = Locacion::factory()->create();

    $respuesta = $this->get(route('contratos.index', $locacion));

    $respuesta->assertRedirect(route('login'));
});

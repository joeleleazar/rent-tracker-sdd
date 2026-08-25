<?php

use App\Models\Contrato;
use App\Models\Locacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * specs/018 (FR-005/FR-006): verifica que las columnas de fecha/hora de
 * negocio son `timestamptz` y que un instante guardado no sufre desplazamiento
 * al releerlo (contrato implícito en spec.md User Story 3, research.md R1).
 */
test('las columnas de fecha hora de negocio son timestamp with time zone', function () {
    $columnasEsperadas = [
        'locaciones' => ['created_at', 'updated_at'],
        'contratos' => ['created_at', 'updated_at', 'notificado_30_dias_en', 'notificado_15_dias_en', 'notificado_7_dias_en', 'fecha_resolucion_garantia'],
        'documentos_contrato' => ['created_at', 'updated_at'],
        'inquilinos' => ['created_at', 'updated_at'],
        'contrato_inquilino' => ['created_at', 'updated_at'],
        'recibos' => ['created_at', 'updated_at', 'fecha_pago', 'fecha_anulacion'],
        'lecturas_medidor' => ['created_at', 'updated_at', 'fecha_registro'],
        'borradores_lectura_medidor' => ['created_at', 'updated_at'],
        'users' => ['created_at', 'updated_at', 'email_verified_at'],
    ];

    foreach ($columnasEsperadas as $tabla => $columnas) {
        foreach ($columnas as $columna) {
            $tipo = DB::selectOne(
                'SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                [$tabla, $columna]
            );

            expect($tipo)->not->toBeNull("La columna {$tabla}.{$columna} no existe.");
            expect($tipo->data_type)->toBe('timestamp with time zone', "La columna {$tabla}.{$columna} debería ser timestamptz.");
        }
    }
});

test('un timestamp de negocio guardado no cambia de instante al releerlo tras la migracion', function () {
    $instante = Carbon::parse('2026-03-10 15:30:00', 'UTC');

    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'notificado_30_dias_en' => $instante,
    ]);

    expect($contrato->fresh()->notificado_30_dias_en->equalTo($instante))->toBeTrue();
    expect($contrato->fresh()->notificado_30_dias_en->toIso8601String())->toBe($instante->toIso8601String());
});

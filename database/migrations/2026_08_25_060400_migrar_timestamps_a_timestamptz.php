<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FR-005/FR-006: convierte cada columna timestamp (sin zona horaria) de las
 * tablas de dominio a timestamptz, reinterpretando los valores ya guardados
 * como UTC (config('app.timezone') === 'UTC', ver research.md R1). No incluye
 * `configuracion_general`: esa tabla se reconstruye en su forma clave-valor
 * (specs/018 US4) ya con timestamptz nativo desde el origen, así que esta
 * migración nunca necesita tocarla, sin importar el orden de ejecución entre
 * ambas migraciones.
 */
return new class extends Migration
{
    private const COLUMNAS_POR_TABLA = [
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

    public function up(): void
    {
        foreach (self::COLUMNAS_POR_TABLA as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                DB::statement(
                    "ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE timestamptz USING {$columna} AT TIME ZONE 'UTC'"
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNAS_POR_TABLA as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                DB::statement(
                    "ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE timestamp USING {$columna} AT TIME ZONE 'UTC'"
                );
            }
        }
    }
};

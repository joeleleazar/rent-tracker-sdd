<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FR-007b / FR-010: antes de restringir, verifica que ninguna fila existente
 * viole la regla de negocio ya aplicada por la app (Carbon::startOfMonth() en
 * cada controlador que escribe "periodo") de que periodo siempre sea el
 * primer día de un mes calendario. Un mensaje explícito por tabla evita
 * depender del error genérico de Postgres al fallar el ADD CONSTRAINT.
 */
return new class extends Migration
{
    private const TABLAS = ['lecturas_medidor', 'recibos', 'borradores_lectura_medidor'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            $violaciones = DB::table($tabla)
                ->whereRaw('EXTRACT(DAY FROM periodo) != 1')
                ->count();

            if ($violaciones > 0) {
                throw new RuntimeException(
                    "No se puede agregar el CHECK de integridad: la tabla \"{$tabla}\" tiene "
                    ."{$violaciones} fila(s) cuyo \"periodo\" no es el día 1 del mes. "
                    .'Corrige esos datos antes de volver a ejecutar esta migración.'
                );
            }

            DB::statement(
                "ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_periodo_dia_uno_check CHECK (EXTRACT(DAY FROM periodo) = 1)"
            );
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT {$tabla}_periodo_dia_uno_check");
        }
    }
};

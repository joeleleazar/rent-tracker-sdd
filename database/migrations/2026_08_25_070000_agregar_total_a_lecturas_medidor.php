<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * specs/019 FR-004/FR-008: agrega el monto final de luz de cada lectura,
 * hoy solo calculado en el navegador (consumo × tarifa) y nunca guardado —
 * pasa a persistirse para que la generación de recibos (specs/005) use este
 * valor fijo en vez de recalcularlo con la tarifa vigente al momento de
 * generar el recibo. Backfill dentro de la misma migración (mismo patrón ya
 * usado en 2026_08_21_044219_refine_lecturas_medidor_anterior_actual.php)
 * para no dejar ningún entorno con esquema nuevo y datos a medio migrar.
 *
 * La tarifa histórica real de cada lectura no está disponible (el sistema no
 * guarda un historial de tarifas, ver specs/019/spec.md Assumptions) — el
 * backfill usa la tarifa vigente al momento de correr esta migración como
 * única fuente disponible, documentado aquí para que no se interprete como
 * el valor exacto que pudo haber regido en cada periodo histórico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->nullable()->after('consumo_calculado');
        });

        $tarifaJson = DB::table('configuracion_general')->where('clave', 'tarifa_luz_por_unidad')->value('valor');
        $tarifa = $tarifaJson !== null ? (float) json_decode($tarifaJson, true) : 0.0;

        DB::table('lecturas_medidor')->orderBy('id')->chunkById(200, function ($filas) use ($tarifa) {
            foreach ($filas as $fila) {
                $consumo = $fila->consumo_calculado ?? $fila->lectura_actual;

                DB::table('lecturas_medidor')
                    ->where('id', $fila->id)
                    ->update(['total' => round((float) $consumo * $tarifa, 2)]);
            }
        });

        DB::statement('ALTER TABLE lecturas_medidor ALTER COLUMN total SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
};

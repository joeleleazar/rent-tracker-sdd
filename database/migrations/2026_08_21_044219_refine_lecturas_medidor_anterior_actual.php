<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->renameColumn('lectura', 'lectura_actual');
        });

        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->decimal('lectura_anterior', 12, 2)->nullable()->after('lectura_actual');
        });

        // Migración de datos (specs/006, Asunción A-002, research.md §1): para filas ya
        // existentes de specs/005, se puebla lectura_anterior con la lectura_actual de
        // la fila cronológicamente previa de la misma locación, y se recalcula
        // consumo_calculado en consecuencia. Ejecutada dentro de esta misma migración
        // (Laravel envuelve DDL+DML de PostgreSQL en una transacción) para no dejar
        // ningún entorno con esquema nuevo y datos a medio migrar.
        $locacionIds = DB::table('lecturas_medidor')->distinct()->pluck('locacion_id');

        foreach ($locacionIds as $locacionId) {
            $filas = DB::table('lecturas_medidor')
                ->where('locacion_id', $locacionId)
                ->orderBy('periodo')
                ->get(['id', 'lectura_actual']);

            $lecturaPrevia = null;

            foreach ($filas as $fila) {
                $consumo = $lecturaPrevia === null ? null : round((float) $fila->lectura_actual - (float) $lecturaPrevia, 2);

                DB::table('lecturas_medidor')
                    ->where('id', $fila->id)
                    ->update([
                        'lectura_anterior' => $lecturaPrevia,
                        'consumo_calculado' => $consumo,
                    ]);

                $lecturaPrevia = (float) $fila->lectura_actual;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->dropColumn('lectura_anterior');
        });

        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->renameColumn('lectura_actual', 'lectura');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/021: `consumo_calculado` deja de persistirse — es matemáticamente
 * derivable de `lectura_actual`/`lectura_anterior`, que ya viven en la misma
 * fila (LecturaMedidor::consumoCalculado(), accessor). Sin backfill: el valor
 * eliminado nunca dependió de nada externo a la propia fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->dropColumn('consumo_calculado');
        });
    }

    public function down(): void
    {
        Schema::table('lecturas_medidor', function (Blueprint $table) {
            $table->decimal('consumo_calculado', 12, 2)->nullable()->after('lectura_actual');
        });
    }
};

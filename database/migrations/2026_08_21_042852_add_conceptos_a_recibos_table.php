<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            // locacion_id ya existe desde specs/004 (creado ya locación-céntrico, ver
            // tasks.md de 004, sección Notes) — aquí solo se agrega la restricción
            // UNIQUE que exige FR-009 de esta especificación.
            $table->dropIndex(['locacion_id', 'periodo']);
            $table->unique(['locacion_id', 'periodo']);

            $table->foreignId('lectura_medidor_id')->nullable()->constrained('lecturas_medidor')->nullOnDelete();
            $table->boolean('incluye_alquiler')->default(true);
            $table->boolean('incluye_luz')->default(true);
            $table->boolean('incluye_agua')->default(true);
            $table->boolean('incluye_seguridad')->default(true);
            $table->boolean('incluye_pasadizo')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lectura_medidor_id');
            $table->dropColumn(['incluye_alquiler', 'incluye_luz', 'incluye_agua', 'incluye_seguridad', 'incluye_pasadizo']);
            $table->dropUnique(['locacion_id', 'periodo']);
            $table->index(['locacion_id', 'periodo']);
        });
    }
};

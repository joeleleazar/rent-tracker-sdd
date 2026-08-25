<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locaciones', function (Blueprint $table) {
            // enum() en el driver pgsql se compila como varchar + CHECK constraint,
            // sin depender del tipo ENUM nativo de PostgreSQL (mismo patrón que
            // contratos.estado, ver data-model.md). Nullable: las locaciones ya
            // existentes antes de esta revisión no tienen un valor asignado
            // (specs/013-arbol-jerarquico-locaciones, research.md §8).
            $table->enum('tipo', ['galeria', 'piso', 'sector', 'pasillo', 'local'])->nullable()->after('es_alquilable');
        });
    }

    public function down(): void
    {
        Schema::table('locaciones', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};

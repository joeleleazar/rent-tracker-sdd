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
            // enum() en el driver pgsql se compila como varchar + CHECK constraint,
            // mismo patrón que contratos.estado (specs/002).
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamp('fecha_anulacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'fecha_pago', 'fecha_anulacion']);
        });
    }
};

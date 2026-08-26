<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/026 (FR-004/FR-019): eliminar un concepto de gasto fijo cuyo único uso está en
 * recibos anulados dejaba de estar realmente bloqueado por la aplicación pero seguía
 * siendo rechazado por PostgreSQL — la FK original (`restrictOnDelete()`, specs/024)
 * protegía la integridad de un recibo_concepto vigente, pero no distinguía anulados.
 * Se relaja a `nullOnDelete()`: el detalle histórico (monto, recibo) se conserva para
 * auditoría, solo pierde el nombre del concepto ya eliminado (las vistas afectadas
 * muestran "Concepto eliminado" en ese caso).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibo_conceptos', function (Blueprint $table) {
            $table->dropForeign(['concepto_gasto_fijo_id']);
        });

        Schema::table('recibo_conceptos', function (Blueprint $table) {
            $table->foreignId('concepto_gasto_fijo_id')->nullable()->change();
        });

        Schema::table('recibo_conceptos', function (Blueprint $table) {
            $table->foreign('concepto_gasto_fijo_id')->references('id')->on('conceptos_gasto_fijo')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recibo_conceptos', function (Blueprint $table) {
            $table->dropForeign(['concepto_gasto_fijo_id']);
        });

        Schema::table('recibo_conceptos', function (Blueprint $table) {
            $table->foreignId('concepto_gasto_fijo_id')->nullable(false)->change();
        });

        Schema::table('recibo_conceptos', function (Blueprint $table) {
            $table->foreign('concepto_gasto_fijo_id')->references('id')->on('conceptos_gasto_fijo')->restrictOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/044 (US3 — cobro por QR): el formulario rápido de cobro captura el
 * medio de pago (efectivo, transferencia, depósito, Yape/Plin, otro). Se
 * agrega como columna directa en `pagos`, nullable, porque el resto del flujo
 * de pagos (specs/032) no la envía y no debe verse afectado — solo el cobro
 * rápido la completa. Sin índice: no se filtra ni agrupa por este campo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('medio_pago', 60)->nullable()->after('fecha_pago');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('medio_pago');
        });
    }
};

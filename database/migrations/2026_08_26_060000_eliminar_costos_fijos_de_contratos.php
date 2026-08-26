<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/024: los 4 costos fijos de `contratos` ya se migraron a
 * `contrato_valores_concepto` (T018/backfill anterior) — `costo_luz` se
 * descarta sin backfill (research.md Decisión 5, nunca se usó para sugerir
 * el monto de luz de un recibo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn(['costo_agua', 'costo_luz', 'costo_pasadizo', 'costo_seguridad']);
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->decimal('costo_agua', 12, 2)->nullable();
            $table->decimal('costo_luz', 12, 2)->nullable();
            $table->decimal('costo_pasadizo', 12, 2)->nullable();
            $table->decimal('costo_seguridad', 12, 2)->nullable();
        });
    }
};

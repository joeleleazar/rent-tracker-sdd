<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/024: las 4 columnas `monto_*`/`incluye_*` de `recibos` ya se
 * migraron a `recibo_conceptos` (T020/backfill anterior). `monto_renta` NO
 * se elimina (research.md Decisión 2, se conserva como el monto de renta
 * propio del recibo); `incluye_alquiler` tampoco tenía backfill propio —
 * "incluye Renta" pasa a ser `monto_renta !== null`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn([
                'incluye_alquiler', 'incluye_agua', 'incluye_luz', 'incluye_pasadizo', 'incluye_seguridad',
                'monto_agua', 'monto_luz', 'monto_pasadizo', 'monto_seguridad',
            ]);
            $table->decimal('monto_renta', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->boolean('incluye_alquiler')->default(true);
            $table->boolean('incluye_agua')->default(true);
            $table->boolean('incluye_luz')->default(true);
            $table->boolean('incluye_pasadizo')->default(true);
            $table->boolean('incluye_seguridad')->default(true);
            $table->decimal('monto_agua', 12, 2)->default(0);
            $table->decimal('monto_luz', 12, 2)->default(0);
            $table->decimal('monto_pasadizo', 12, 2)->default(0);
            $table->decimal('monto_seguridad', 12, 2)->default(0);
        });
    }
};

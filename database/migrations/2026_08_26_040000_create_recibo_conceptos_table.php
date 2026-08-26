<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/024: un concepto de gasto fijo efectivamente incluido en un recibo,
 * con su monto — reemplaza `incluye_agua`/`incluye_luz`/`incluye_pasadizo`/
 * `incluye_seguridad`/`monto_agua`/`monto_luz`/`monto_pasadizo`/
 * `monto_seguridad` de `recibos`. "Renta" no tiene fila aquí — se representa
 * con `recibos.monto_renta`, que se conserva (research.md Decisión 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibo_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recibo_id')->constrained('recibos')->cascadeOnDelete();
            $table->foreignId('concepto_gasto_fijo_id')->constrained('conceptos_gasto_fijo')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->timestamps();

            $table->unique(['recibo_id', 'concepto_gasto_fijo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibo_conceptos');
    }
};
